<?php

namespace Texditor\Blockify;

class HtmlBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Initialize Blockify processor with configuration
     * 
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get current configuration
     * 
     * @return Config
     */
    public function config(): Config
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
                $html .= $model->renderBlock($block);
            }
        }

        return $html;
    }
}
