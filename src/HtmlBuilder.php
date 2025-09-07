<?php

namespace Texditor\Blockify;

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
}
