<?php

namespace Texditor\Blockify\Interfaces;

interface ConfigInterface
{
    /**
     * Registers one or more block models with the configuration.
     * Models are instantiated, configured, and made available for processing.
     *
     * @param BlockModelInterface ...$models The block models to register
     * @return self
     */
    public function addModels(BlockModelInterface ...$models): self;

    /**
     * Retrieves all registered block models.
     *
     * @return BlockModelInterface[] Array of registered block models
     */
    public function getModels(): array;

    /**
     * Retrieves a specific block model by its input name identifier.
     *
     * @param string $inputName The model's unique identifier (e.g., 'paragraph', 'heading')
     * @return BlockModelInterface|null The requested model or null if not found
     */
    public function getModel(string $inputName): ?BlockModelInterface;

    /**
     * Enables or disables development mode.
     * In development mode, additional errors and exceptions might be thrown.
     *
     * @param bool $status True to enable development mode, false to disable
     * @return self
     */
    public function setDev(bool $status = true): self;

    /**
     * Checks if development mode is currently enabled.
     *
     * @return bool True if development mode is enabled, false otherwise
     */
    public function isDev(): bool;

    /**
     * Sets the CSS class prefix used for generated block classes.
     *
     * @param string $prefix The prefix to use (e.g., 'blockify' generates 'blockify-paragraph')
     * @return self
     */
    public function setBlockCssPrefix(string $prefix): self;

    /**
     * Gets the current CSS class prefix for blocks.
     *
     * @return string The current CSS prefix
     */
    public function getBlockCssPrefix(): string;

    /**
     * Sets tag name replacements for HTML output.
     * Allows semantic replacement of tags during rendering (e.g., 'b' → 'strong').
     *
     * @param array $names Associative array of tag replacements ['original' => 'replacement']
     * @return self
     * @example ['b' => 'strong', 'i' => 'em']
     */
    public function setRenderTagNames(array $names): self;

    /**
     * Gets the current tag name replacements configuration.
     *
     * @return array Associative array of tag name replacements
     */
    public function getRenderTagNames(): array;

    /**
     * Sets block name replacements for HTML output.
     * Allows changing block-level element tags during rendering.
     *
     * @param array $names Associative array of block replacements ['original' => 'replacement']
     * @return self
     * @example ['p' => 'div', 'code' => 'pre']
     */
    public function setRenderBlockNames(array $names): self;

    /**
     * Gets the current block name replacements configuration.
     *
     * @return array Associative array of block name replacements
     */
    public function getRenderBlockNames(): array;
}