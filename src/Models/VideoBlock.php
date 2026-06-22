<?php

namespace Texditor\Blockify\Models;

class VideoBlock extends FileBlock
{
    protected string $inputName = 'video';

    /** 
     * Allowed image MIME types
     * 
     * @var array  
     */
    protected array $sourceMimeTypes = [
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo'
    ];

    /**
     * Video attributes
     * 
     * @var array
     */
    private array $videoAttributes = [];

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
        $itemStructure = $this->getItemStructure();

        if ($this->isLinkStrategy()) {
            $allowedHosts = $this->getSourceHosts();
            $itemStructure['poster'] = [
                'type' => 'string',
                'url' => true,
            ];

            if (!empty($allowedHosts))
                $itemStructure['poster']['allowedHost'] = $allowedHosts;
        }

        $this->setItemStructure($itemStructure);
    }

    /**
     * Render single gallery item
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
        $html .= $this->renderVideo($item);
        $html .= '</div>';

        if ($this->getMetaPosition() === 'bottom')
            $html .= $meta;

        $html .= '</a>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Render video item
     *
     * @param array $item Video data
     * @return string
     */
    protected function renderVideo(array $item): string
    {
        $cssStyle = $this->getCssName();
        $attributes = $this->getVideoAttributes();
        $attrString = '';
        $poster = $item['poster'] ?? '';

        if (!empty($poster))
            $attributes['poster'] = $poster;

        foreach ($attributes as $key  => $attribute) {
            $attrString .= sprintf(' %s="%s"', $key, $attribute);
        }

        $picture = '<video class="' . $cssStyle . '-item-video"' . $attrString . '>';
        $picture .= '<source src="' . $item['url'] . '" type="' . $item['type'] . '" />';
        $picture .= '</video>';

        return $picture;
    }

    /**
     * Set HTML attributes for videos
     *
     * @param array $attributes Attributes
     * @return self
     */
    public function setVideoAttributes(array $attributes): self
    {
        $this->videoAttributes = $attributes;

        return $this;
    }

    /**
     * Get HTML attributes of a video
     *
     * @return array
     */
    public function getVideoAttributes(): array
    {
        return $this->videoAttributes;
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
