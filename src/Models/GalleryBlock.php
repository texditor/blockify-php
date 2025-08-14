<?php

namespace Texditor\Blockify\Models;

class GalleryBlock extends FilesBlock
{
    protected string $inputName = 'gallery';

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
    private array $imageTypes = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
    ];

    /** 
     * Allowed video MIME types 
     * 
     * @var array 
     */
    private array $videoTypes = [
        'video/mp4',
        'video/webm',
        'video/ogg',
    ];

    private array $videoAttributes = [];

    /**
     * Called after file structures are created
     */
    protected function onCreatedFilesStructure(): void
    {
        $this->setSourceMimeTypes(
            array_merge(
                $this->getImageTypes(),
                $this->getVideoTypes()
            )
        );

        $structure = $this->getBlockStructure();
        $structure['style'] = [
            'type' => 'string',
            'values' => ['grid', 'slider', 'single'],
        ];

        $this->setBlockStructure($structure);

        $allowedHosts = $this->getSourceHosts();
        $itemStructure = $this->getItemStructure();

        $itemStructure['thumbnail'] = [
            'type' => 'string',
            'url' => true,
        ];

        if (!empty($allowedHosts))
            $itemStructure['thumbnail']['allowedHost'] = $allowedHosts;

        $this->setItemStructure($itemStructure);
    }

    /**
     * Render gallery block with style-specific classes
     *
     * @param array $block Gallery block data
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
     * Render single gallery item
     *
     * @param array $item Item data containing url and type
     * @return string
     */
    protected function renderFileItem(array $item): string
    {
        $url = $item['url'] ?? '';
        $type = $item['type'] ?? '';

        if (empty($url) || empty($type)) {
            return '';
        }

        $isImage = in_array($type, $this->getImageTypes());
        $cssStyle = $this->getCssName() . '-item';
        $html = '<div class="' . $cssStyle . ' ' . $cssStyle . '-' . str_replace('/', '-', $type) . '">';

        if ($isImage)
            $html .= '<a href="' . $url . '" class="' . $cssStyle . '-link">';

        $html .= '<div class="' . $cssStyle . '-source">';

        if ($isImage) {
            $html .= $this->renderImage($item);
        } else {
            $html .= $this->renderVideo($item);
        }

        $html .= '</div>';

        if ($isImage)
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
        $picture = '<picture class="' . $cssStyle . '-picture">';

        if (isset($item['thumbnail'])) {
            $picture .= '<source srcset="' . $item['url'] . '" media="(min-width: ' . $this->getThumbnailBreakpoint() . 'px)" />';
            $picture .= '<img class="' . $cssStyle . '-image" src="' . $item['thumbnail'] . '" alt="' . ($item['caption'] ?? '') . '" />';
        } else {
            $picture .= '<img class="' . $cssStyle . '-image" src="' . $item['url'] . '" alt="' . ($item['caption'] ?? '') . '" />';
        }

        $picture .= '</picture>';

        return $picture;
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

        foreach ($attributes as $key  => $attribute) {
            $attrString .= sprintf(' "%s"="%s"', $key, $attribute);
        }

        $picture = '<video class="' . $cssStyle . '-video"' . $attrString . '>';
        $picture .= '<source src="' . $item['url'] . '" type="' . $item['type'] . '" />';
        $picture .= '</video>';

        return $picture;
    }

    /**
     * Set allowed image MIME types
     *
     * @param array $types Array of image MIME types
     * @return self
     */
    public function setImageTypes(array $types): self
    {
        $this->imageTypes = $types;

        return $this;
    }

    /**
     * Get allowed image MIME types
     *
     * @return array
     */
    public function getImageTypes(): array
    {
        return $this->imageTypes;
    }

    /**
     * Set allowed video MIME types
     *
     * @param array $types Array of video MIME types
     * @return self
     */
    public function setVideoTypes(array $types): self
    {
        $this->videoTypes = $types;
        return $this;
    }

    /**
     * Get allowed video MIME types
     *
     * @return array
     */
    public function getVideoTypes(): array
    {
        return $this->videoTypes;
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
}
