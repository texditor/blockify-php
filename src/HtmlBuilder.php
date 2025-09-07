<?php

namespace Texditor\Blockify;

use Cleup\Guard\Purifier\Utils\Scrub;
use Texditor\Blockify\Interfaces\BlockModelInterface;
use Texditor\Blockify\Interfaces\ConfigInterface;
use Texditor\Blockify\Interfaces\HtmlBuilderInterface;

class HtmlBuilder implements HtmlBuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Initialize HtmlBuilder with configuration
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
     * Render complete block structure to HTML
     *
     * @param array $blocks Prepared block data
     * @return string
     */
    public function render(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            if (!empty($block['type']) && !empty($block['data'])) {
                $model = $this->config()->getModel($block['type']);

                if ($model instanceof BlockModelInterface) {
                    $html .= $model->renderBlock($block);
                }
            }
        }

        return $html;
    }

    /**
     * Render only specified block types
     *
     * @param array $blocks Prepared block data
     * @param array $allowedTypes Array of block types to include
     * @param bool $asText Converting content to plain text
     * @return string
     */
    public function renderOnly(array $blocks, array $allowedTypes, $asText = false): string
    {
        $filteredBlocks = array_filter($blocks, function ($block) use ($allowedTypes) {
            return !empty($block['type']) && in_array($block['type'], $allowedTypes);
        });

        return !$asText
            ? $this->render($filteredBlocks)
            : $this->renderAsText($filteredBlocks);
    }

    /**
     * Render blocks as plain text without HTML tags
     * Extracts and concatenates text content from block data
     *
     * @param array $blocks Prepared block data
     * @return string Plain text content
     */
    public function renderAsText(array $blocks): string
    {
        $textContent = '';

        foreach ($blocks as $block) {
            if (!empty($block['data'])) {
                $textContent .= $this->extractTextFromData($block['data']) . ' ';
            }
        }

        return trim(Scrub::text($textContent));
    }

    /**
     * Recursively extract text content from block data
     *
     * @param array|string $data Block data to process
     * @return string Extracted text content
     */
    protected function extractTextFromData($data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (is_array($data)) {
            $text = '';
            foreach ($data as $item) {
                if (is_string($item)) {
                    $text .= $item . ' ';
                } elseif (is_array($item) && isset($item['data'])) {
                    $text .= $this->extractTextFromData($item['data']) . ' ';
                } elseif (is_array($item)) {
                    $text .= $this->extractTextFromData($item) . ' ';
                }
            }
            return trim($text);
        }

        return '';
    }
}
