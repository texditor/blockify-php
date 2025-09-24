<?php

namespace Texditor\Blockify;

use Texditor\Blockify\Interfaces\BlockModelInterface;
use Texditor\Blockify\Interfaces\ConfigInterface;

class BlockModel implements BlockModelInterface
{
    /** 
     * Internal name used to identify this model in input data
     * 
     * @var string 
     */
    protected string $inputName = '';

    /** 
     * Name used when outputting this block type 
     * 
     * @var string 
     */
    protected string $outputName = '';

    /** 
     * Whether to automatically escape text content for HTML safety 
     * 
     * @var bool 
     */
    protected bool $escapeText = true;

    /** 
     * Whether to merge adjacent similar items (text or elements) 
     * 
     * @var bool
     */
    protected bool $mergeSimilar = true;

    /** 
     * List of allowed HTML tags within this block's content 
     * 
     * @var array 
     */
    protected array $allowedTags = ['b', 'a', 'i', 'u', 's', 'sub', 'sup', 'code', 'mark'];

    /** List of primary child element types that have special processing 
     * 
     * @var array 
     */
    protected array $primaryChilds = [];

    /** 
     * Whether this block uses a non-standard structure definition
     * 
     * @var bool
     */
    protected bool $isCustomBlockStructure = false;

    /** 
     * Whether items within this block use custom structure
     * 
     * @var bool 
     */
    protected bool $isCustomItemStructure = false;

    /** 
     * Definition of custom item structure (when isCustomItemStructure=true)
     * 
     * @var array 
     */
    protected array $customItemStructure = [];

    /**
     * Allowed URL protocols for external resources (links, media)
     * 
     * @var array
     */
    protected array $sourceProtocols = ['https', 'http', 'ftp'];


    /**
     * Regular expressions of the source
     * 
     * @var array
     */
    protected array $sourceRegex = [];

    /** 
     * Allowed hostnames for external resources (empty allows all)
     * 
     * @var array */
    protected array $sourceHosts = [];

    /** 
     * Allowed MIME types for embedded content
     * 
     * @var array
     */
    protected array $sourceMimeTypes = [];

    /** 
     * Additional CSS classes for the block
     * @var string 
     */
    protected string $cssClasses = '';

    /** 
     * Validation rules for individual content items
     * 
     * @var array 
     */
    protected array $itemStructure = [];

    /**
     * Rules for checking attributes for each tag
     * 
     *  @var array 
     */
    protected array $tagAttributeRules = [
        'a' => [
            'href' => 'required;url;allowedProtocol:https|http|ftp',
            'target' => 'values:_blank'
        ]
    ];

    /** 
     * Validation rules for the block's overall structure
     * 
     * @var array 
     */
    protected array $blockStructure = [
        'type' => 'required',
        'data' => 'type:array;required',
        'attr' => 'type:array'
    ];

    /**
     * Flag for custom block rendering
     * 
     * @var bool
     */
    protected bool $isCustomRenderBlock = false;

    /** 
     * Allow deletion of control characters.
     * 
     * @var bool
     */
    protected bool $isRemoveControlCharacters = false;

    /** 
     * Preformatted block (<pre>)
     * 
     * @var bool
     */
    protected bool $isPreformatted = false;

    private ?ConfigInterface $config = null;

    /**
     * Get current configuration
     * 
     * @return ConfigInterface
     */
    public function config(): ConfigInterface
    {
        if ($this->config === null) {
            throw new \RuntimeException('Config not set. Call setConfig() first.');
        }

        return $this->config;
    }

    /**
     * Set current configuration
     * 
     * @param ConfigInterface $config
     * @return self
     */
    public function setConfig(ConfigInterface $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Constructor that triggers the onLoad hook for model initialization
     */
    public function __construct() {}

    /**
     * Hook method for model initialization (can be overridden in child classes)
     */
    public function onLoad(): void {}

    /**
     * Set whether text content should be HTML-escaped
     *
     * @param bool $status True to enable escaping, false to disable
     * @return self
     */
    public function setEscapeText(bool $status): self
    {
        $this->escapeText = $status;

        return $this;
    }

    /**
     * Check if text escaping is enabled
     *
     * @return bool
     */
    public function isEscapeText(): bool
    {
        return $this->escapeText;
    }

    /**
     * Set allowed tags for this model
     *
     * @param array $tags Array of allowed tag names
     * @return self
     */
    public function setAllowedTags(array $tags): self
    {
        $this->allowedTags = $tags;

        return $this;
    }

    /**
     * Get all allowed tags
     *
     * @return array
     */
    public function getAllowedTags(): array
    {
        return $this->allowedTags;
    }

    /**
     * Set primary child elements for this model
     *
     * @param array $tags Array of primary child tag names
     * @return self
     */
    public function setPrimaryChilds(array $tags): self
    {
        $this->primaryChilds = $tags;

        return $this;
    }

    /**
     * Get all primary child elements
     *
     * @return array
     */
    public function getPrimaryChilds(): array
    {
        return $this->primaryChilds;
    }

    /**
     * Set the input name identifier for this model
     *
     * @param string $inputName Unique identifier for the model
     * @return self
     */
    public function setInputName(string $inputName): self
    {
        $this->inputName = $inputName;

        return $this;
    }

    /**
     * Get the input name identifier
     *
     * @return string
     */
    public function getInputName(): string
    {
        return $this->inputName;
    }

    /**
     * Set the output name for this model
     *
     * @param string $outputName Name used in output
     * @return self
     */
    public function setOutputName(string $outputName): self
    {
        $this->outputName = $outputName;

        return $this;
    }

    /**
     * Get the output name
     *
     * @return string
     */
    public function getOutputName(): string
    {
        return $this->outputName;
    }

    /**
     * Set the block structure validation rules
     *
     * @param array $structure Validation rules array
     * @return self
     */
    public function setBlockStructure(array $structure): self
    {
        $this->blockStructure = $structure;

        return $this;
    }

    /**
     * Get the block structure validation rules
     *
     * @return array
     */
    public function getBlockStructure(): array
    {
        return $this->blockStructure;
    }

    /**
     * Set the item structure validation rules
     *
     * @param array $structure Validation rules array
     * @return self
     */
    public function setItemStructure(array $structure): self
    {
        $this->itemStructure = $structure;

        return $this;
    }

    /**
     * Get the item structure validation rules
     *
     * @return array
     */
    public function getItemStructure(): array
    {
        return $this->itemStructure;
    }

    /**
     * Set whether similar items should be merged
     *
     * @param bool $status True to enable merging
     * @return self
     */
    public function setMergeSimilar(bool $status): self
    {
        $this->mergeSimilar = $status;

        return $this;
    }

    /**
     * Check if similar items merging is enabled
     *
     * @return bool
     */
    public function isMergeSimilar(): bool
    {
        return $this->mergeSimilar;
    }

    /**
     * Set whether this model uses custom block structure
     *
     * @param bool $status True to enable custom structure
     * @return self
     */
    public function setIsCustomBlockStructure(bool $status): self
    {
        $this->isCustomBlockStructure = $status;

        return $this;
    }

    /**
     * Check if custom block structure is enabled
     *
     * @return bool
     */
    public function isCustomBlockStructure(): bool
    {
        return $this->isCustomBlockStructure;
    }

    /**
     * Set whether this model uses custom item structure
     *
     * @param bool $status True to enable custom structure
     * @return self
     */
    public function setIsCustomItemStructure(bool $status): self
    {
        $this->isCustomItemStructure = $status;

        return $this;
    }

    /**
     * Check if custom item structure is enabled
     *
     * @return bool 
     */
    public function isCustomItemStructure(): bool
    {
        return $this->isCustomItemStructure;
    }

    /**
     * Specify whether this model removes the control special characters.
     *
     * @param bool $status Set the value to true to allow deletion.
     * @return self
     */
    public function setIsRemoveControlCharacters(bool $status): self
    {
        $this->isRemoveControlCharacters = $status;

        return $this;
    }

    /**
     * Check if the removal of managed special characters is enabled.
     *
     * @return bool
     */
    public function isRemoveControlCharacters(): bool
    {
        return $this->isRemoveControlCharacters;
    }

    /**
     * The block will be preformatted.
     *
     * @param bool $status Set the value to true to allow preformatting.
     * @return self
     */
    public function setIsPreformatted(bool $status): self
    {
        $this->isPreformatted = $status;

        return $this;
    }

    /**
     * Check if preformatting is enabled.
     *
     * @return bool
     */
    public function isPreformatted(): bool
    {
        return $this->isPreformatted;
    }

    /**
     * Get attribute validation rules for a specific tag
     *
     * @param string $tag The tag name
     * @return array
     */
    public function getTagAttributeRules(string $tag): array
    {
        return $this->tagAttributeRules[$tag] ?? [];
    }

    /**
     * Get allowed source protocols
     *
     * @return array
     */
    public function getSourceProtocols(): array
    {
        return $this->sourceProtocols;
    }

    /**
     * Set allowed source protocols
     *
     * @param array $protocols List of allowed protocols
     * @return self
     */
    public function setSourceProtocols(array $protocols): self
    {
        $this->sourceProtocols = $protocols;

        return $this;
    }

    /**
     * Get source regular expressions
     *
     * @return array
     */
    public function getSourceRegex(): array
    {
        return $this->sourceRegex;
    }

    /**
     * Set source regular expressions
     *
     * @param array $protocols List of regular expressions
     * @return self
     */
    public function setSourceRegex(array $regex = []): self
    {
        $this->sourceRegex = $regex;

        return $this;
    }

    /**
     * Get allowed source hosts
     *
     * @return array
     */
    public function getSourceHosts(): array
    {
        return $this->sourceHosts;
    }

    /**
     * Set allowed source hosts
     *
     * @param array $hosts List of allowed hosts
     * @return self
     */
    public function setSourceHosts(array $hosts): self
    {
        $this->sourceHosts = $hosts;

        return $this;
    }

    /**
     * Get allowed source MIME types
     *
     * @return array
     */
    public function getSourceMimeTypes(): array
    {
        return $this->sourceMimeTypes;
    }

    /**
     * Set allowed source MIME types
     *
     * @param array $types List of allowed MIME types
     * @return self
     */
    public function setSourceMimeTypes(array $types): self
    {
        $this->sourceMimeTypes = $types;

        return $this;
    }

    /**
     * Set additional CSS classes for the block
     *
     * @param string $cssClasses CSS classes to add
     * @return self
     */
    public function setCssClasses(string $cssClasses): self
    {
        $this->cssClasses = $cssClasses;

        return $this;
    }

    /**
     * Get additional CSS classes for the block
     *
     * @return string
     */
    public function getCssClasses(): string
    {
        return $this->cssClasses;
    }

    /**
     * Custom item processing hook (can be overridden in child classes)
     *
     * @param array|string $item The item to process
     * @return array|string
     */
    public function eachCustomItem(array|string $item): array|string
    {
        return $item;
    }

    /**
     * Enable or disable custom block rendering
     *
     * @param bool $status Whether to enable custom rendering (true = enabled, false = disabled)
     * @return self
     */
    public function setIsCustomRenderBlock(bool $status): self
    {
        $this->isCustomRenderBlock = $status;

        return $this;
    }

    /**
     * Check if custom block rendering is enabled
     *
     * @return bool 
     */
    public function isCustomRenderBlock(): bool
    {
        return $this->isCustomRenderBlock;
    }

    /**
     * Get the base CSS name for the block
     * 
     * @return string
     */
    public function getCssName(): string
    {
        return $this->config()
            ->getBlockCssPrefix() . '-' .
            $this->getInputName();
    }

    /**
     * Render the entire block with its contents
     *
     * @param array $block The block data to render
     * @return string
     */
    public function renderBlock(array $block): string
    {
        if ($this->isCustomRenderBlock()) {
            return $this->renderCustomBlock($block);
        }

        $tagName = $this->getOutputName();
        $renderBlockNames = $this->config()->getRenderBlockNames();
        $tagName = isset($renderBlockNames[$tagName])
            ? $renderBlockNames[$tagName]
            : $tagName;

        $items = $this->renderItems($block['data'] ?? []);
        $attrs = $this->renderAttributes($block['attr'] ?? []);

        return $this->renderTags($tagName, $items, $attrs);
    }

    /**
     * Render custom block types
     *
     * @param array $block Block data
     * @return string
     */
    protected function renderCustomBlock(array $block): string
    {
        return '';
    }

    /**
     * Render all items within a block
     *
     * @param array $items Array of items to render
     * @return string
     */
    protected function renderItems(array $items): string
    {
        $result = '';

        foreach ($items as $item) {
            if (is_string($item)) {
                $result .= $item;
            } elseif (is_array($item) && isset($item['type'])) {
                $result .= $this->renderNestedItem($item);
            }
        }

        return $result;
    }

    /**
     * Render a nested block item
     *
     * @param array $item Item data
     * @return string
     */
    protected function renderNestedItem(array $item): string
    {
        return $this->renderTags(
            $item['type'],
            $this->renderItems($item['data'] ?? []),
            $this->renderAttributes($item['attr'] ?? [])
        );
    }

    /**
     * Render HTML attributes from an array
     *
     * @param array $attributes Attributes to render
     * @return string
     */
    protected function renderAttributes(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $result = [];

        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value)
                    $result[] = $name;
            } elseif (is_scalar($value)) {
                $result[] = $name . '="' . $value . '"';
            }
        }

        return $result ? ' ' . implode(' ', $result) : '';
    }

    /**
     * Render HTML tags with content and attributes
     *
     * @param string $tagName HTML tag name
     * @param string $content Tag content
     * @param string $attributes Tag attributes
     * @return string
     */
    protected function renderTags(
        string $tagName,
        string $content,
        string $attributes = ''
    ): string {
        if (empty($tagName)) {
            return $content;
        }

        $renderTagNames = $this->config()->getRenderTagNames();
        $tagName = isset($renderTagNames[$tagName])
            ? $renderTagNames[$tagName]
            : $tagName;

        return sprintf(
            '<%s%s>%s</%s>',
            $tagName,
            $attributes,
            $content,
            $tagName
        );
    }
}
