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
     *     ['type' => 'paragraph', 'data' => ['Hello world']],
     *     ['type' => 'heading', 'data' => ['Welcome']]
     * ]);
     */
    public function render(array $blocks): string;
}
