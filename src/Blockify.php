<?php

namespace Texditor\Blockify;

use Cleup\Guard\Purifier\Utils\Valid;
use Cleup\Guard\Purifier\Validation;
use Cleup\Helpers\Arr;
use Texditor\Blockify\Exceptions\InvalidJsonDataException;
use Texditor\Blockify\Interfaces\BlockModelInterface;
use Texditor\Blockify\Interfaces\ConfigInterface;

class Blockify
{
    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Initialize Blockify processor with configuration
     * 
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Get current configuration
     * 
     * @return ConfigInterface
     */
    public function config(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Check if a block item is valid.
     *
     * @param mixed $item The block item to validate
     * @return bool 
     */
    protected function isValidBlock($item): bool
    {
        return isset($item['type']) &&
            is_string($item['type']) &&
            $this->config()->getModel($item['type']) !== null &&
            !empty($item['data']) &&
            is_array($item['data']) &&
            Arr::isList($item['data']);
    }

    /**
     * Check if an array item is valid according to the model rules
     *
     * @param array $item The item to validate
     * @param BlockModelInterface $model The model defining validation rules
     * @return bool
     */
    protected function isValidArrayItem(array $item, BlockModelInterface $model): bool
    {
        return !empty($item['type'])
            && !empty($item['data'])
            && in_array(
                $item['type'],
                $model->getAllowedTags()
            );
    }

    /**
     * Determine if two text items should be merged
     *
     * @param mixed $current Current item
     * @param mixed $next Next item
     * @return bool
     */
    protected function shouldMergeTextItems($current, $next): bool
    {
        return is_string($current) && is_string($next);
    }

    /**
     * Determine if two array items should be merged
     *
     * @param mixed $current Current item
     * @param mixed $next Next item
     * @param BlockModelInterface $model The model defining merge rules
     * @return bool
     */
    protected function shouldMergeArrayItems(
        $current,
        $next,
        BlockModelInterface $model
    ): bool {
        return is_array($current)
            && is_array($next)
            && $next['type'] === $current['type']
            && empty($current['attr'])
            && empty($next['attr'])
            && empty($model->getPrimaryChilds());
    }

    /**
     * Filter and prepare raw block data by removing invalid blocks and keeping only allowed structure keys.
     *
     * @param array $rawData The raw input data containing blocks
     * @return array
     */
    protected function prepareBlocks(array $rawData): array
    {
        $output = [];

        foreach ($rawData as $item) {
            if ($this->isValidBlock($item)) {
                $model = $this->config()->getModel($item['type']);
                $structureKeys = array_keys($model->getBlockStructure());

                // Keep only keys that exist in block structure
                foreach ($item as $itemKey => $itemValue) {
                    if (!in_array($itemKey, $structureKeys)) {
                        unset($item[$itemKey]);
                    }
                }

                $output[] = $item;
            }
        }

        return $output;
    }

    /**
     * Process prepared blocks by applying custom or default structure processing.
     *
     * @param array $blocks The prepared blocks to process
     * @return array
     */
    protected function processBlocks(array $blocks): array
    {
        $output = [];

        foreach ($blocks as $key => $block) {
            $model = $this->config()->getModel($block['type']);

            if ($model->isRemoveControlCharacters() && !empty($block['data'])) {
                $block['data'] = json_decode(
                    $this->removeControlCharacters(
                        json_encode($block['data'], JSON_UNESCAPED_UNICODE)
                    ),
                    true
                );
            }

            $processedBlock = $model->isCustomBlockStructure()
                ? $this->processCustomBlock($block, $model)
                : $this->processDefaultBlock($block, $model);

            if (empty($processedBlock['data'])) {
                continue;
            } else {
                $processedBlock['data'] = $this->mergeSimilarItems(
                    $processedBlock['data'],
                    $model
                );
            }

            $output[$key] = $processedBlock;
        }

        return $output;
    }

    /**
     * Process a block using the default structure rules.
     *
     * @param array $block The block to process
     * @param BlockModelInterface $model The model defining the processing rules
     * @return array
     */
    protected function processDefaultBlock(array $block, BlockModelInterface $model): array
    {
        $outputItem = $block;
        $outputItem['data'] = [];

        foreach ($block['data'] as $itemData) {
            $primaryChilds = $model->getPrimaryChilds();

            if (!empty($primaryChilds)) {
                $this->processPrimaryChild($itemData, $model, $outputItem);
            } else {
                $this->processRegularItem($itemData, $model, $outputItem);
            }
        }

        return $outputItem;
    }

    /**
     * Process a primary child item according to model rules.
     *
     * @param mixed $itemData The item data to process
     * @param BlockModelInterface $model The model defining processing rules
     * @param array
     */
    protected function processPrimaryChild($item, BlockModelInterface $model, array &$output): void
    {
        if ($this->isValidPrimaryChild($item, $model)) {

            $item = $this->filterDataWithRules(
                $item,
                $model->getBlockStructure()
            );

            foreach ($item['data'] as $dataKey => $dataItem) {
                $notNullData = $this->processItemData($dataItem, $model);

                if ($notNullData) {
                    $item['data'][$dataKey] = $notNullData;
                } else {
                    unset($item['data'][$dataKey]);
                }
            }

            if (!empty($item['data'])) {
                $output['data'][] = $item;
            }
        }
    }

    /**
     * Check if an item is a valid primary child.
     *
     * @param mixed $itemData The item data to validate
     * @param BlockModelInterface $model The model defining validation rules
     * @return bool
     */
    protected function isValidPrimaryChild($itemData, BlockModelInterface $model): bool
    {
        return is_array($itemData) &&
            !empty($itemData['type']) &&
            is_string($itemData['type']) &&
            in_array(
                $itemData['type'],
                $model->getPrimaryChilds()
            );
    }

    /**
     * Process a regular item according to model rules.
     *
     * @param mixed $itemData The item data to process
     * @param BlockModelInterface $model The model defining processing rules
     * @param array &$output Reference to the output array to store results
     */
    protected function processRegularItem($itemData, BlockModelInterface $model, array &$output): void
    {
        $notNullData = $this->processItemData($itemData, $model);

        if (!is_null($notNullData)) {
            $output['data'][] = $notNullData;
        }
    }

    /**
     * Process a block using custom structure rules.
     *
     * @param array $block The block to process
     * @param BlockModelInterface $model The model defining the processing rules
     * @return array
     */
    protected function processCustomBlock(array $block, BlockModelInterface $model): array
    {
        $preparedBlock = $this->filterDataWithRules(
            $block,
            $model->getBlockStructure()
        );

        if ($model->isCustomItemStructure()) {
            $preparedBlockData = [];
            foreach ($preparedBlock['data'] as $dataItemKey => $dataItemValue) {
                $notNullData = $this->filterDataWithRules(
                    $dataItemValue,
                    $model->getItemStructure()
                );

                if (!is_null($notNullData))
                    $preparedBlockData[$dataItemKey] = $model->eachCustomItem($notNullData);
            }

            $preparedBlock['data'] = $preparedBlockData;
        } else {
            $preparedBlock = $this->processDefaultBlock(
                $preparedBlock,
                $model
            );
        }

        return $preparedBlock;
    }

    protected function processItemData(array|string $itemData, BlockModelInterface $model): array|string|null
    {
        return is_string($itemData)
            ? $this->processTextItem($itemData, $model)
            : $this->processArrayItem($itemData, $model);
    }

    /**
     * Merge similar adjacent items according to model rules
     * 
     * @param array $items Items to process
     * @param BlockModelInterface $model Model defining rules
     * @return array
     */
    protected function mergeSimilarItems(array $items, BlockModelInterface $model): array
    {
        if (!$model->isMergeSimilar()) {
            return array_values($items);
        }

        $result = [];
        $i = 0;
        $count = count($items);

        while ($i < $count) {
            $current = $items[$i];
            $next = $items[$i + 1] ?? null;

            if ($this->shouldMergeTextItems($current, $next)) {
                $result[] = $current . ' ' . $next;
                $i += 2;
            } elseif ($this->shouldMergeArrayItems($current, $next, $model)) {
                $current['data'] = array_merge($current['data'], $next['data']);
                $result[] = $current;
                $i += 2;
            } else {
                $result[] = $current;
                $i++;
            }
        }

        return $result;
    }

    /**
     * Process a text item according to model rules.
     *
     * @param string $text The text to process
     * @param BlockModelInterface $model The model defining processing rules
     * @return string|null
     */
    protected function processTextItem(string $text, BlockModelInterface $model): ?string
    {
        $text = trim($model->isEscapeText()
            ? escape($text)
            : $text);

        return is_not_empty($text) ? $text : null;
    }

    /**
     * Process an array item according to model rules
     *
     * @param array $itemData The item data to process
     * @param BlockModelInterface $model The model defining processing rules
     * @return array|null
     */
    protected function processArrayItem(array $itemData, BlockModelInterface $model): ?array
    {
        if (!$this->isValidArrayItem($itemData, $model)) {
            return null;
        }

        $result = ['type' => $itemData['type']];

        if (isset($itemData['attr']) && is_array($itemData['attr'])) {
            $result['attr'] = $this->filterAttributes($itemData, $model);

            if (empty($itemData))
                $result = null;
        }

        if (isset($itemData['data']) && is_array($itemData['data'])) {
            $result['data'] = [];

            foreach ($itemData['data'] as $dataItem) {
                if (is_string($dataItem)) {
                    $text = $this->processTextItem($dataItem, $model);

                    if (!is_null($text))
                        $result['data'][] = $text;
                } elseif (is_array($dataItem)) {
                    $arrayData = $this->processArrayItem($dataItem, $model);

                    if (!is_null($arrayData))
                        $result['data'][] = $arrayData;
                }
            }
        }

        if (!empty($result['data']))
            $result['data'] = $this->mergeSimilarItems($result['data'], $model);
        else
            $result = null;

        return $result;
    }

    /**
     * Set and process input data
     * 
     * @param array|string $data Input data (array or JSON string)
     * @return self
     * @throws InvalidJsonDataException
     */
    public function setData($data): self
    {
        $input = $this->parseInputData($data);

        if (Arr::isList($input) && !empty($input)) {
            $this->data = $this->processBlocks(
                $this->prepareBlocks($input)
            );
        }

        return $this;
    }

    /**
     * Process input data that could be either array or JSON string
     *
     * @param mixed $data Input data to parse
     * @return array
     */
    protected function parseInputData($data): array
    {
        if (is_string($data)) {
            if (!Valid::json($data)) {
                if ($this->config()->isDev())
                    throw new InvalidJsonDataException('Invalid JSON data');

                return [];
            }
        }

        return json_decode(
            $this->sanitizeJson($data) ?? "[]",
            true
        );
    }

    /**
     * Get processed data
     * 
     * @return array Processed blocks data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Sanitizes JSON by removing or escaping control characters and Unicode formatting marks
     * 
     * @param mixed $json Input JSON as string or object/array
     * @return string|null Sanitized JSON string or null if parsing fails
     */
    public function sanitizeJson($json): ?string
    {
        $jsonString = is_string($json) ? $json : json_encode($json, JSON_UNESCAPED_UNICODE);

        if ($jsonString === false) {
            return null;
        }

        // Remove Unicode escape sequences first (\u200b, \u2028, etc.)
        $cleaned = preg_replace(
            '/\\\\u(200[b-f]|202[8-9a-f]|202[f]|205[f]|206[0-9a-f]|206[6-9]|feff)/i',
            '',
            $jsonString
        );
 
        $cleaned = str_replace([
            "\u{00A0}", // NO-BREAK SPACE
            "\u{202F}", // NARROW NO-BREAK SPACE
            "\u{2007}", // FIGURE SPACE
            "\u{2060}", // WORD JOINER
            "\u{FEFF}", // ZERO WIDTH NO-BREAK SPACE (BOM)
            "\u{200B}", // ZERO WIDTH SPACE
            "\u{2002}", // EN SPACE
            "\u{2003}", // EM SPACE
            "\u{2009}", // THIN SPACE
            "\u{200A}", // HAIR SPACE
        ], ' ', $cleaned);

        // First pass: remove actual control characters except tab, newline, and carriage return
        $cleaned = preg_replace_callback(
            '/[\x00-\x1F\x7F]/',
            function ($match) {
                $code = ord($match[0]);
                return ($code === 9 || $code === 10 || $code === 13) ? $match[0] : '';
            },
            $cleaned
        );

        // Remove actual Unicode control characters
        $cleaned = preg_replace(
            '/[\x{200B}-\x{200F}\x{2028}-\x{202F}\x{205F}-\x{206F}\x{FEFF}\x{2066}-\x{2069}]/u',
            '',
            $cleaned
        );

        $cleaned = preg_replace_callback(
            '/(?<!\\\\)\\\\u\\{[^}]*\\}|(?<!\\\\)\\\\[a-zA-Z]\\{[^}]*\\}/',
            function ($match) {
                return '';
            },
            $cleaned
        );

        $parsed = json_decode($cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($parsed, JSON_UNESCAPED_UNICODE);
        }

        // Final cleanup: remove any remaining non-printable characters and escape sequences
        $finalCleaned = preg_replace('/[^\x20-\x7E\t\n\r]|\\\\u(200[b-f]|202[8-9a-f]|202[f]|205[f]|206[0-9a-f]|206[6-9]|feff)/i', '', $cleaned);
        $parsedFinal = json_decode($finalCleaned, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($parsedFinal, JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    /**
     * Completely removes control characters and special Unicode formatting marks
     * 
     * @param string $input Input string to clean
     * @return string Cleaned string without control characters
     */
    public function removeControlCharacters(string $input): string
    {
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
        return preg_replace('/(?<!\\\\)\\\\([nrt])/', '', $cleaned);
    }

    /**
     * Filter attributes according to model rules
     * 
     * @param array &$item Reference to item containing attributes
     * @param BlockModelInterface $model Model defining rules
     * @return array Filtered attributes
     */
    public function filterAttributes(array &$item, BlockModelInterface $model): array
    {
        $validated = $this->filterDataWithRules(
            $item['attr'],
            $model->getTagAttributeRules($item['type'])
        );

        if (is_null($validated)) {
            $item = [];
            return [];
        }

        return $validated ?? [];
    }

    /**
     * Filter data according to validation rules
     * 
     * @param array $data Data to filter
     * @param array $rules Validation rules
     * @return array|null
     */
    public function filterDataWithRules(array $data, array $rules)
    {
        $validator = new Validation($rules);
        $validator->validate($data);
        $errors = $validator->getErrors();
        $verified = array_intersect_key($data, $rules);
        $isRequiredInvalid = false;

        foreach ($errors as $name => $errorList) {
            foreach ($errorList as $error) {

                $rule = $validator->normalizeRule($rules[$name] ?? []);

                if (empty($this->errors[$name]))
                    $this->errors[$name] = [];

                $saveError = $error;
                $saveError['rule'] = $rule;
                $saveError['item'] = $verified[$name] ?? '';
                $saveError['data'] = $verified ?? '';
                $this->errors[$name][] = $saveError;

                if ($error['code'] === 'field_required' || ($rule['required'] ?? false)) {
                    $isRequiredInvalid = true;
                    break 2;
                }

                unset($verified[$name]);
            }
        }

        return $isRequiredInvalid ? null : $verified;
    }


    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
