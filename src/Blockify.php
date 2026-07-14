<?php

namespace Texditor\Blockify;

use Cleup\Guard\Purifier\Utils\Valid;
use Cleup\Guard\Purifier\Validation;
use Cleup\Helpers\Arr;
use Texditor\Blockify\Exceptions\InvalidJsonDataException;
use Texditor\Blockify\Interfaces\BlockifyInterface;
use Texditor\Blockify\Interfaces\BlockModelInterface;
use Texditor\Blockify\Interfaces\ConfigInterface;

class Blockify implements BlockifyInterface
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
     * @var callable|null
     */
    private $filterCallback = null;

    /**
     * @var callable|null
     */
    private $dataFilterCallback = null;

    /**
     * @var callable|null
     */
    private $itemDataFilterCallback = null;

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
        if (
            !(
                isset($item['type']) &&
                is_string($item['type']) &&
                $this->config()->getModel($item['type']) !== null &&
                !empty($item['data']) &&
                is_array($item['data']) &&
                Arr::isList($item['data'])
            )
        ) {
            return false;
        }

        return true;
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
     * Normalizes input string by removing invisible characters and handling different modes
     * 
     * @param string $input The input string to normalize
     * @param string $mode Processing mode: 'text' for regular text or 'preformatted' for preserving formatting
     * @return string
     */
    public function normalizeInput(string $input, string $mode = 'text'): string
    {
        if (empty($input)) {
            return $input;
        }

        $text = $input;

        if (substr($text, 0, 3) === "\xEF\xBB\xBF") {
            $text = substr($text, 3);
        }

        if ($mode === 'preformatted') {
            return $this->escapeUnicodeCharsForPre($text);
        } else {
            $text = $this->removeInvisibleChars($text);
            $text = preg_replace('/[\x{00A0}\x{202F}]/u', ' ', $text);
            $text = preg_replace('/\s+/', ' ', $text);

            return $text;
        }
    }

    /**
     * Escapes specific Unicode control characters in preformatted text
     * 
     * @param string $text Input text to process
     * @return string
     */
    public function escapeUnicodeCharsForPre(string $text): string
    {
        $result = '';
        $length = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            $codePoint = mb_ord($char, 'UTF-8');

            if (
                $codePoint >= 0x200B && $codePoint <= 0x200F ||
                $codePoint >= 0x202A && $codePoint <= 0x202E ||
                $codePoint == 0x2060
            ) {
                $result .= sprintf('\u{%04X}', $codePoint);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Removes invisible Unicode control characters from input string
     * 
     * @param string $input Input string to clean
     * @return string
     */
    public function removeInvisibleChars(string $input): string
    {
        $patterns = [
            '/[\x{200B}-\x{200D}]/u',    // Zero Width Space, Non-Joiner, Joiner
            '/[\x{2060}]/u',              // Word Joiner
            '/[\x{200E}\x{200F}]/u',      // Direction marks
            '/[\x{202A}-\x{202E}]/u',     // Directional embeddings
        ];

        $text = $input;

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text);
        }

        return $text;
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
            && empty($model->getPrimaryChildren());
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

        foreach ($rawData as $blockIndex => $block) {

            if ($this->isValidBlock($block)) {
                $model = $this->config()->getModel($block['type']);
                $structureKeys = array_keys($model->getBlockStructure());
                $block = $this->filterDataWithRules($block, $model->getBlockStructure());

                // Keep only keys that exist in block structure
                foreach ($block as $blockKey => $blockValue) {
                    if (!in_array($blockKey, $structureKeys)) {
                        unset($block[$blockKey]);
                    }
                }

                $output[] = $block;
            } else {
                if (empty($block['type'])) {
                    $this->addError('type', [
                        'code' => 'type_required',
                        'message' => 'The type field is required',
                        'data' => $block,
                        'index' =>  $blockIndex
                    ]);
                }

                if (empty($block['data'])) {
                    $this->addError('data', [
                        'code' => 'data_required',
                        'message' => 'The data field is required',
                        'data' => $block,
                        'index' =>  $blockIndex
                    ]);
                }
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

            $dataFilterCallback = $this->dataFilterCallback;

            if (
                $dataFilterCallback &&
                is_callable($dataFilterCallback) &&
                !empty($processedBlock['data'])
            ) {
                $saveBlock = $processedBlock;
                $processedBlock['data'] = [];

                foreach ($saveBlock['data'] as $itemIndex => $item) {
                    if ($dataFilterCallback(
                        $item,
                        $itemIndex,
                        $saveBlock,
                        $key,
                        $model
                    )) {
                        $processedBlock['data'][] = $item;
                    }
                }
            }

            if (empty($processedBlock['data'])) {
                continue;
            } else {
                $processedBlock['data'] = $this->mergeSimilarItems(
                    $processedBlock['data'],
                    $model
                );
            }

            $callback = $this->filterCallback;
            $transformItemCallback = $model->getTransformItemCallback();

            if (
                $transformItemCallback &&
                is_callable($transformItemCallback) &&
                !empty($processedBlock['data']) &&
                is_array($processedBlock['data'])
            ) {
                foreach ($processedBlock['data'] as $itemKey => $item) {
                    $preparedItem = $transformItemCallback(
                        $item,
                        $itemKey,
                        $model
                    );

                    if (!empty($preparedItem))
                        $processedBlock['data'][$itemKey] = $preparedItem;
                }
            }

            if ($callback && is_callable($callback)) {
                if ($callback($block, $key, $model)) {
                    $output[$key] = $processedBlock;
                }
            } else
                $output[$key] = $processedBlock;
        }

        return array_values($output);
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
            $primaryChildren = $model->getPrimaryChildren();

            if (!empty($primaryChildren)) {
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
     * @param mixed $item The item data to process
     * @param BlockModelInterface $model The model defining processing rules
     * @param array &$output Reference to the output array to store results
     * @return void
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
                $model->getPrimaryChildren()
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

    /**
     * Process item data by type — delegates to text or array handler
     *
     * @param array|string $itemData
     * @param BlockModelInterface $model
     * @return array|string|null
     */
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
        $globEscape = $this->config()->isEscape();

        $itemDataFilterCallback = $this->itemDataFilterCallback;

        if (
            $itemDataFilterCallback &&
            is_callable($itemDataFilterCallback) &&
            !empty($text)
        ) {
            $text = $itemDataFilterCallback($text, $model);
        }

        if (empty($text))
            return null;

        $text = $model->isEscapeText()
            ? ($globEscape
                ? escape($text)
                : $text
            )
            : $text;

        $type = 'text';

        if ($model->isPreformatted()) {
            $type = 'preformatted';
        }

        if (is_not_empty($text) || preg_match('/^\s+$/', $text)) {
            return $this->normalizeInput($text, $type);
        }

        return null;
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

        $itemDataFilterCallback = $this->itemDataFilterCallback;

        if (
            $itemDataFilterCallback &&
            is_callable($itemDataFilterCallback) &&
            !empty($itemData)
        ) {
            $itemData = $itemDataFilterCallback($itemData, $model);
        }

        if (empty($itemData))
            return null;

        $result = ['type' => $itemData['type']];

        if (isset($itemData['attr']) && is_array($itemData['attr'])) {
            $result['attr'] = $this->applyAttributeRules($itemData, $model);

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

        if (!empty($input) && Arr::isList($input)) {
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

        $convert = is_string($data)
            ? $data
            : json_encode($data, JSON_UNESCAPED_UNICODE);

        return json_decode(
            $convert,
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
     * Filters and validates item attributes using model-defined rules
     *
     * @param array $item Reference to item data containing 'attr' and 'type' keys
     * @param BlockModelInterface $model Model providing attribute validation rules
     * @return array
     */
    public function applyAttributeRules(array &$item, BlockModelInterface $model): array
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
     * Filter of data elements.
     * 
     * @param callable $filter The function being called
     * @return self
     */
    public function dataFilter(callable $filter): self
    {
        $this->dataFilterCallback = $filter;

        return $this;
    }

    /**
     * Filter of individual data items within each element.
     * 
     * @param callable $filter The function being called
     * @return self
     */
    public function itemDataFilter(callable $filter): self
    {
        $this->itemDataFilterCallback = $filter;

        return $this;
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

    /**
     * Block filtering
     * 
     * @param callable $filter The function being called
     * @return self
     */
    public function filter(callable $filter): self
    {
        $this->filterCallback = $filter;

        return $this;
    }

    /**
     * If the structure does not contain errors
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add an error grouped by error code.
     *
     * @param string $code Error code identifier
     * @param array $data Additional error context
     * @return void
     */
    public function addError(string $code, array $data = []): void
    {
        if (empty($this->errors[$code])) {
            $this->errors[$code] = [];
        }

        $this->errors[$code][] = $data;
    }
}
