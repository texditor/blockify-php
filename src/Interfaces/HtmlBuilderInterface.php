<?php

namespace Texditor\Blockify\Interfaces;

use Texditor\Blockify\Interfaces\ConfigInterface;

interface HtmlBuilderInterface
{
    /**
     * Gets the current configuration instance.
     * Provides access to registered block models and rendering settings.
     *
     * @return ConfigInterface
     */
    public function config(): ConfigInterface;

    /**
     * Renders complete block structure to HTML.
     * Processes an array of prepared blocks and converts them to HTML string.
     *
     * @param array $blocks Prepared block data from Blockify processor
     * @return string Rendered HTML output
     * 
     * @example
     * $html = $builder->render([
     *     ['type' => 'p', 'data' => ['Hello world']],
     *     ['type' => 'h1', 'data' => ['Welcome']]
     * ]);
     */
    public function render(array $blocks): string;

    /**
     * Render only specified block types, filtering out others.
     *
     * @param array $blocks Prepared block data
     * @param array $allowedTypes Array of block types to include (e.g., ['p', 'h1'])
     * @param bool  $asText Converting content to plain text
     * @return string Rendered HTML output containing only specified block types
     * 
     * @example
     * $html = $builder->renderOnly($blocks, ['p', 'h1']);
     */
    public function renderOnly(array $blocks, array $allowedTypes, bool $asText): string;

    /**
     * Render blocks as plain text without HTML tags.
     * Extracts and concatenates text content from block data, ignoring all HTML markup.
     *
     * @param array $blocks Prepared block data
     * @return string Plain text content with all HTML tags stripped
     * 
     * @example
     * $text = $builder->renderAsText($blocks);
     * // Result: "Hello world This is a paragraph Welcome to our site"
     */
    public function renderAsText(array $blocks): string;
}
