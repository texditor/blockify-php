<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class FilesBlock extends BlockModel
{
    protected string $inputName = 'files';
    protected string $outputName = 'div';
    protected array $allowedTags = [];
    protected bool $isCustomBlockStructure = true;
    protected bool $isCustomItemStructure = true;
    protected bool $isCustomRenderBlock = true;
    protected bool $mergeSimilar = false;

    /** @var array Allowed MIME types for file uploads */
    protected array $sourceMimeTypes = [
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/webm',
        'video/ogg',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo'
    ];

    /** @var bool Whether files should be downloadable */
    private bool $isDownloadable = true;

    /**
     * Called when the block is loaded
     * Initializes block and item structures
     */
    public function onLoad()
    {
        $this->createBlockStructure();
        $this->createItemStructure();
        $this->onCreatedFilesStructure();
    }

    /**
     * Creates the basic block structure validation rules
     */
    private function createBlockStructure()
    {
        $this->setBlockStructure([
            'type' => [
                'type' => 'string',
                'values' => $this->getInputName(),
                'required' => true,
            ],
            'data' => 'type:array;required'
        ]);
    }

    /**
     * Creates the item structure validation rules
     */
    private function createItemStructure()
    {
        $allowedHosts = $this->getSourceHosts();
        $allowedProtocols = $this->getSourceProtocols();
        $regex = $this->getSourceRegex();
        $urlRule =  ['required' => true];

        if (!empty($allowedHosts) && !empty($allowedProtocols)) {
            $urlRule['url'] = true;

            if (!empty($allowedHosts))
                $urlRule['allowedProtocol'] = $allowedProtocols;

            if (!empty($allowedHosts))
                $urlRule['allowedHost'] = $allowedHosts;

        }

        if (!empty($regex)) {
            $urlRule['before'] = function ($data) use ($regex) {
                foreach ($regex as $reg) {
                    if (!!preg_match($reg, $data))
                        return $data;
                    break;
                }

                return false;
            };
        }


        $this->setItemStructure([
            'url' => $urlRule,
            'type' => [
                'required' => true,
                'values' => $this->getSourceMimeTypes()
            ],
            'size' => 'type:integer',
            'caption' => 'type:string;',
            'desc' => 'type:string;'
        ]);
    }

    /**
     * Hook called after file structures are created
     * Can be overridden by child classes
     */
    protected function onCreatedFilesStructure(): void {}

    /**
     * Processes each item before rendering
     *
     * @param array|string $item The item to process
     * @return array|string The processed item
     */
    public function eachCustomItem(array|string $item): array|string
    {
        if (isset($item['caption'])) {
            if (is_not_empty($item['caption']))
                $item['caption'] = escape(trim($item['caption']));
            else
                unset($item['caption']);
        }

        if (isset($item['desc'])) {
            if (is_not_empty($item['desc']))
                $item['desc'] = escape(trim($item['desc']));
            else
                unset($item['desc']);
        }

        return $item;
    }

    /**
     * Custom block rendering implementation
     *
     * @param array $block The block data to render
     * @return string The rendered HTML
     */
    protected function renderCustomBlock(array $block): string
    {
        return $this->renderFiles($block);
    }

    /**
     * Render files block
     *
     * @param array $block Files block data
     * @param string $additionalCss Additional CSS classes
     * @return string Rendered HTML
     */
    protected function renderFiles(array $block, string $additionalCss = ''): string
    {
        $cssClasses = $this->getCssClasses();
        $cssPrefix = $this->getCssName();
        $itemsHtml = array_map(function ($item) {
            return $this->renderFileItem($item);
        }, $block['data']);

        $class = $cssPrefix .
            (!empty($cssClasses) ? ' ' . trim($cssClasses) : '') .
            (!empty($additionalCss) ? ' ' . trim($additionalCss) : '');

        $content = implode('', $itemsHtml);

        return '<div class="' . $class . '">' .
            '<div class="' . $cssPrefix . '-list">' . $content . '</div>' .
            '</div>';
    }

    /**
     * Render single file item
     *
     * @param array $item Item data
     * @return string Rendered HTML
     */
    protected function renderFileItem(array $item): string
    {
        $url = $item['url'] ?? '';

        if (empty($url))
            return '';

        $fileExtension = pathinfo($url, PATHINFO_EXTENSION);
        $cssStyle = $this->getCssName() . '-item';
        $caption = !empty($item['caption'])
            ? $item['caption']
            : '.' . $fileExtension;

        $desc = $item['desc'] ?? '';
        $type = $item['type'] ?? 'application/octet-stream';
        $size = (isset($item['size']) && (
            is_numeric($item['size']) ||
            is_int($item['size'])
        )) ? intval($item['size']) : 0;
        $size = $this->formatFileSize($size);
        $downloadAttr = $this->isDownloadable() ? ' download="' . $url . '"' : '';
        $html = '<div class="' . $cssStyle . ' ' . $cssStyle . '-' . str_replace('/', '-', $type) . '">';
        $html .= '<div class="' . $cssStyle . '-ext">' . $fileExtension . '</div>';
        $html .= '<a href="' . $url . '" class="' . $cssStyle . '-link"' . ($downloadAttr) . '>';
        $html .= '<div class="' . $cssStyle . '-info">';
        $html .= '<div class="' . $cssStyle . '-name">' . $caption . '</div>';
        $html .= '<div class="' . $cssStyle . '-meta">';
        if (!empty($desc))
            $html .= '<div class="' . $cssStyle . '-desc">' . $desc . '</div>';
        if ($size) {
            $html .= '<div class="' . $cssStyle . '-size">' . $size . '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</a>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Format file size in human-readable format
     *
     * @param int $bytes File size in bytes
     * @return string Formatted size with unit
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Set whether files should be downloadable
     *
     * @param bool $status Downloadable status
     * @return self
     */
    public function setIsDownloadable(bool $status): self
    {
        $this->isDownloadable = $status;

        return $this;
    }

    /**
     * Check if files are downloadable
     *
     * @return bool Downloadable status
     */
    public function isDownloadable(): bool
    {
        return $this->isDownloadable;
    }
}
