<?php

namespace Texditor\Blockify;

use Cleup\Helpers\Arr;

class Config
{
    /**
     * Array of registered block models
     * 
     * @var BlockModel[]
     */
    private $blockModels = [];

    /**
     * Development mode flag
     * 
     * @var bool
     */
    private $dev = false;

    /**
     * CSS name class prefix
     * 
     * @var string
     */
    private $blockCssPrefix = 'blockify';

    /** 
     * Replacing standard tag names ['b' => 'strong', 'i' => 'em']
     * 
     * @var array 
     */
    protected array $renderTagNames = [];

    /** 
     * Replacing the standard block output names ['p' => 'div', 'code' => 'pre']
     * 
     * @var array 
     */
    protected array $renderBlockNames = [];

    /**
     * Add one or more block models to the configuration
     *
     * @param BlockModel ...$models
     * @return self
     */
    public function addModels(...$models)
    {
        foreach ($models as $model) {
            if ($model instanceof BlockModel) {
                $name = $model->getInputName();
                $model->setConfig($this);
                $model->onLoad();
       
                if (!empty($name))
                    $this->blockModels[$name] = $model;
            }
        }

        return $this;
    }

    /**
     * Get all the available block models
     *
     * @return array Array of registered BlockModel instances
     */
    public function getModels(): array
    {
        return $this->blockModels;
    }

    /**
     * Get a specific block model by its input name
     *
     * @param string $inputName The name of the model to retrieve
     * @return BlockModel|null The requested model or null if not found
     */
    public function getModel(string $inputName): ?BlockModel
    {
        return $this->blockModels[$inputName] ?? null;
    }

    /**
     * Set development mode status
     *
     * @param bool $status Whether to enable development mode (default: true)
     * @return self
     */
    public function setDev($status = true): self
    {
        $this->dev = $status;

        return $this;
    }

    /**
     * Check if development mode is enabled
     *
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->dev;
    }

    /**
     * Set the CSS class prefix for blocks
     *
     * @param string $prefix The prefix to use for CSS classes
     * @return self
     */
    public function setBlockCssPrefix(string $prefix): self
    {
        $this->blockCssPrefix = $prefix;

        return $this;
    }

    /**
     * Get the current CSS class prefix for blocks
     *
     * @return string
     */
    public function getBlockCssPrefix(): string
    {
        return $this->blockCssPrefix;
    }

    /**
     * Set the tag name replacements for rendering
     *
     * @param array $names Associative array of tag name replacements
     * @return self
     */
    public function setRenderTagNames(array $names): self
    {
        if (Arr::isAssoc($names))
            $this->renderTagNames = $names;

        return $this;
    }

    /**
     * Get the current tag name replacements
     *
     * @return array Associative array of tag name replacements
     */
    public function getRenderTagNames(): array
    {
        return $this->renderTagNames;
    }

    /**
     * Set the block name replacements for rendering
     *
     * @param array $names Associative array of block name replacements
     * @return self
     */
    public function setRenderBlockNames(array $names): self
    {
        if (Arr::isAssoc($names))
            $this->renderBlockNames = $names;

        return $this;
    }

    /**
     * Get the current block name replacements
     *
     * @return array Associative array of block name replacements
     */
    public function getRenderBlockNames(): array
    {
        return $this->renderBlockNames;
    }
}
