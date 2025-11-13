<?php

namespace Texditor\Blockify;

use Cleup\Helpers\Arr;
use Texditor\Blockify\Interfaces\BlockModelInterface;
use Texditor\Blockify\Interfaces\ConfigInterface;

class Config implements ConfigInterface
{
    /**
     * Array of registered block models
     * 
     * @var BlockModelInterface[]
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
     * Escaping when receiving data
     * 
     * @var bool 
     */
    private bool $escape = false;

    /** 
     * Escaping during rendering
     * 
     * @var bool 
     */
    private bool $renderEscape = true;

    /**
     * Add one or more block models to the configuration
     *
     * @param BlockModelInterface ...$models
     * @return self
     */
    public function addModels(BlockModelInterface ...$models): self
    {
        foreach ($models as $model) {
            $name = $model->getInputName();
            $model->setConfig($this);
            $model->onLoad();

            if (!empty($name))
                $this->blockModels[$name] = $model;
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
     * @return BlockModelInterface|null The requested model or null if not found
     */
    public function getModel(string $inputName): ?BlockModelInterface
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

    /**
     * Escaping when receiving data
     * 
     * @param bool $status - Status
     * @return self
     */
    public function setEscape(bool $status = true): self
    {
        $this->escape = $status;

        return $this;
    }

    /**
     * Data escape status
     * 
     * @return bool
     */
    public function isEscape(): bool
    {
        return $this->escape;
    }

    /**
     * Escaping during rendering
     * 
     * @param bool $status - Status
     * @return self
     */
    public function setRenderEscape(bool $status = true): self
    {
        $this->renderEscape = $status;

        return $this;
    }

    /**
     * The status of escaping during rendering
     * 
     * @return bool
     */
    public function isRenderEscape(): bool
    {
        return $this->renderEscape;
    }
}
