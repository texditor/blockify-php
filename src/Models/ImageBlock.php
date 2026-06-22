<?php

namespace Texditor\Blockify\Models;

class ImageBlock extends FileBlock
{
    protected string $inputName = 'image';

    /** 
     * Screen width breakpoint for thumbnail switching (in pixels)
     * 
     * @var int  
     */
    private int $thumbnailBreakpoint = 768;

    /** 
     * Allowed image MIME types
     * 
     * @var array  
     */
    protected array $sourceMimeTypes = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];

    /**
     * The position of the meta section (only render).
     * 
     * @var string
     */
    private string $metaPosition = 'bottom';

    /**
     * Determines whether the meta section is enabled or disabled (only render).
     * 
     * @var bool
     */
    private bool $isMeta = true;

    /**
     * Determines whether the meta caption (title) section is enabled or disabled (only render).
     * 
     * @var bool
     */
    private bool $isMetaCaption = true;

    /**
     * Determines whether the meta description section is enabled or disabled (only render).
     * 
     * @var bool
     */
    private bool $isMetaDesc = true;

    /**
     * Called after file structures are created
     */
    protected function onCreatedFilesStructure(): void
    {
        $structure = $this->getBlockStructure();
        $structure['style'] = [
            'type' => 'string',
            'values' => ['grid', 'slider', 'single'],
        ];

        $this->setBlockStructure($structure);

        $allowedHosts = $this->getSourceHosts();
        $itemStructure = $this->getItemStructure();

        if ($this->isLinkStrategy()) {
            $itemStructure['thumbnail'] = [
                'type' => 'string',
                'url' => true,
            ];

            if (!empty($allowedHosts))
                $itemStructure['thumbnail']['allowedHost'] = $allowedHosts;
        }

        $this->setItemStructure($itemStructure);
    }

    /**
     * Render image block with style-specific classes
     *
     * @param array $block Image block data
     * @param string $additionalCss Additional CSS classes
     * @return string
     */
    protected function renderFiles(array $block, string $additionalCss = ''): string
    {
        $itemsStyle = $block['style'] ?? '';

        if (!empty($itemsStyle))
            $itemsStyle = $this->getCssName() . '-' . $itemsStyle;

        return parent::renderFiles($block, $itemsStyle);
    }

    /**
     * Render single image item
     *
     * @param array $item Item data containing url and type
     * @return string
     */
    protected function renderFileItem(array $item): string
    {
        $url = $item['url'] ?? '';
        $type = $item['type'] ?? '';
        $caption = $item['caption'] ?? '';
        $desc = $item['desc'] ?? '';
        $desc = $this->config()->isRenderEscape() ? escape($desc) : $desc;
        $caption = $this->config()->isRenderEscape() ? escape($caption) : $caption;

        if (empty($url) || empty($type)) {
            return '';
        }

        $cssStyle = $this->getCssName() . '-item';
        $ccsType = $this->getCssName() . '-type-' . str_replace('/', '-', $type);
        $meta = '';

        if ($this->isMeta()) {
            $meta .= '<div class="' . $cssStyle . '-meta">';

            if ($this->isMetaCaption())
                $meta .= '<div class="' . $cssStyle . '-meta-caption">' . $caption . '</div>';

            if ($this->isMetaDesc())
                $meta .= '<div class="' . $cssStyle . '-meta-desc">' . $desc . '</div>';

            $meta .= '</div>';
        }

        $html = '<div class="' . $cssStyle . ' ' . $ccsType . '">';
        $html .= '<a href="' . $url . '" class="' . $cssStyle . '-link">';

        if ($this->getMetaPosition() === 'top')
            $html .= $meta;

        $html .= '<div class="' . $cssStyle . '-source">';
        $html .= $this->renderImage($item);
        $html .= '</div>';

        if ($this->getMetaPosition() === 'bottom')
            $html .= $meta;

        $html .= '</a>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render image item with responsive thumbnail support
     *
     * @param array $item Image data including url and optional thumbnail
     * @return string
     */
    protected function renderImage(array $item): string
    {
        $cssStyle = $this->getCssName();
        $picture = '<picture class="' . $cssStyle . '-item-picture">';

        if (isset($item['thumbnail'])) {
            $picture .= '<source srcset="' . $item['url'] . '" media="(min-width: ' . $this->getThumbnailBreakpoint() . 'px)" />';
            $picture .= '<img class="' . $cssStyle . '-item-picture-image" src="' . $item['thumbnail'] . '" alt="' . ($item['caption'] ?? '') . '" />';
        } else {
            $picture .= '<img class="' . $cssStyle . '-item-picture-image" src="' . $item['url'] . '" alt="' . ($item['caption'] ?? '') . '" />';
        }

        $picture .= '</picture>';

        return $picture;
    }

    /**
     * Set screen size threshold for thumbnail switching
     *
     * @param int $size Breakpoint width in pixels
     * @return self
     */
    public function setThumbnailBreakpoint(int $size): self
    {
        $this->thumbnailBreakpoint = $size;

        return $this;
    }

    /**
     * Get current thumbnail screen size threshold
     *
     * @return int
     */
    public function getThumbnailBreakpoint(): int
    {
        return $this->thumbnailBreakpoint;
    }

    /**
     * Sets the meta position and returns the instance for method chaining.
     *
     * @param string $position The position of the meta element (e.g., "top", "bottom").
     * @return self
     */
    public function setMetaPosition(string $position): self
    {
        $this->metaPosition = in_array(
            $position,
            ['top', 'bottom']
        ) ? $position : 'bottom';

        return $this;
    }

    /**
     * Retrieves the current meta position.
     *
     * @return string
     */
    public function getMetaPosition(): string
    {
        return $this->metaPosition;
    }

    /**
     * Enables or disables the meta section entirely.
     *
     * @param bool $status Activity status
     * @return self 
     */
    public function setIsMeta(bool $status): self
    {
        $this->isMeta = $status;

        return $this;
    }

    /**
     * Checks whether the meta section is enabled.
     *
     * @return bool
     */
    public function isMeta(): bool
    {
        return $this->isMeta;
    }

    /**
     * Enables or disables the meta caption (title) section.
     *
     * @param bool $status Activity status
     * @return self 
     */
    public function setIsMetaCaption(bool $status): self
    {
        $this->isMetaCaption = $status;

        return $this;
    }

    /**
     * Checks whether the meta caption (title) is enabled.
     *
     * @return bool
     */
    public function isMetaCaption(): bool
    {
        return $this->isMetaCaption;
    }

    /**
     * Enables or disables the meta description section.
     *
     * @param bool $status Activity status
     * @return self
     */
    public function setIsMetaDesc(bool $status): self
    {
        $this->isMetaDesc = $status;

        return $this;
    }

    /**
     * Checks whether the meta description is enabled.
     *
     * @return bool
     */
    public function isMetaDesc(): bool
    {
        return $this->isMetaDesc;
    }
}
