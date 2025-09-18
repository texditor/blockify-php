<?php

namespace Texditor\Blockify\Interfaces;

use Texditor\Blockify\Config;

interface BlockModelInterface
{
    /**
     * Sets the configuration instance for the model.
     * This method is called by the Blockify processor after model instantiation.
     *
     * @param ConfigInterface $config
     * @return self
     */
    public function setConfig(ConfigInterface $config): self;

    /**
     * Gets the current configuration instance.
     * Used by the model to access global settings and helpers.
     *
     * @return ConfigInterface
     */
    public function config(): ConfigInterface;

    /**
     * Sets the unique identifier for this model used in input data ('type' field).
     *
     * @param string $inputName
     * @return self
     */
    public function setInputName(string $inputName): self;

    /**
     * Gets the unique identifier for this model used in input data ('type' field).
     * The Blockify processor uses this to match raw data blocks to their respective models.
     *
     * @return string
     */
    public function getInputName(): string;

    /**
     * Sets the name used for output (e.g., HTML tag name like 'div', 'p', 'span').
     *
     * @param string $outputName
     * @return self
     */
    public function setOutputName(string $outputName): self;

    /**
     * Gets the name used for output (e.g., HTML tag name like 'div', 'p', 'span').
     *
     * @return string
     */
    public function getOutputName(): string;

    /**
     * Sets the validation rules for the overall block structure.
     *
     * @param array $structure
     * @return self
     */
    public function setBlockStructure(array $structure): self;

    /**
     * Gets the validation rules for the overall block structure.
     * Defines the required and optional keys for a block of this type (e.g., 'type', 'data', 'attr').
     * Used by the `filterDataWithRules` method in Blockify.
     *
     * @return array
     * @example ['type' => 'required', 'data' => 'type:array;required', 'attr' => 'type:array']
     */
    public function getBlockStructure(): array;

    /**
     * Sets the validation rules for individual content items within the block's 'data' array.
     *
     * @param array $structure
     * @return self
     */
    public function setItemStructure(array $structure): self;

    /**
     * Gets the validation rules for individual content items within the block's 'data' array.
     * Used for processing items when custom item structure is enabled.
     *
     * @return array
     */
    public function getItemStructure(): array;

    /**
     * Gets the attribute validation rules for a specific HTML tag.
     * Used to validate and filter attributes like 'href' for 'a' tags.
     *
     * @param string $tag The HTML tag name (e.g., 'a', 'img', 'b').
     * @return array The validation rules for the tag's attributes.
     * @example For 'a' tag: ['href' => 'required;url', 'target' => 'values:_blank']
     */
    public function getTagAttributeRules(string $tag): array;

    /**
     * Sets whether text content should be HTML-escaped for safety.
     *
     * @param bool $status
     * @return self
     */
    public function setEscapeText(bool $status): self;

    /**
     * Checks if text content should be HTML-escaped for safety.
     *
     * @return bool
     */
    public function isEscapeText(): bool;

    /**
     * Sets whether adjacent similar items should be merged.
     *
     * @param bool $status
     * @return self
     */
    public function setMergeSimilar(bool $status): self;

    /**
     * Checks if adjacent similar items (text strings or identical array items) should be merged.
     *
     * @return bool
     */
    public function isMergeSimilar(): bool;

    /**
     * Sets whether the block uses a non-standard (custom) structure definition.
     *
     * @param bool $status
     * @return self
     */
    public function setIsCustomBlockStructure(bool $status): self;

    /**
     * Checks if the block uses a non-standard (custom) structure definition.
     * If true, the processor will use `processCustomBlock` instead of `processDefaultBlock`.
     *
     * @return bool
     */
    public function isCustomBlockStructure(): bool;

    /**
     * Sets whether items within this block use a custom structure.
     *
     * @param bool $status
     * @return self
     */
    public function setIsCustomItemStructure(bool $status): self;

    /**
     * Checks if items within this block use a custom structure.
     * If true, the processor will use the custom item structure and the `eachCustomItem` hook.
     *
     * @return bool
     */
    public function isCustomItemStructure(): bool;

    /**
     * Sets whether control characters should be removed from the data.
     *
     * @param bool $status
     * @return self
     */
    public function setIsRemoveControlCharacters(bool $status): self;

    /**
     * Checks if control characters (Unicode formatting marks, etc.) should be removed from the data.
     *
     * @return bool
     */
    public function isRemoveControlCharacters(): bool;

    /**
     * Sets whether the block uses a custom rendering method.
     *
     * @param bool $status
     * @return self
     */
    public function setIsCustomRenderBlock(bool $status): self;

    /**
     * The block will be preformatted.
     *
     * @param bool $status Set the value to true to allow preformatting.
     * @return self
     */
    public function setIsPreformatted(bool $status): self;

    /**
     * Check if preformatting is enabled.
     *
     * @return bool
     */
    public function isPreformatted(): bool;

    /**
     * Checks if the block uses a custom rendering method.
     * If true, the processor will call `renderBlock()` on this model instead of using the default renderer.
     *
     * @return bool
     */
    public function isCustomRenderBlock(): bool;

    /*****************************************************************
     * CONTENT DEFINITION
     ****************************************************************/

    /**
     * Sets the list of allowed HTML tags within this block's content.
     *
     * @param array $tags
     * @return self
     */
    public function setAllowedTags(array $tags): self;

    /**
     * Gets the list of allowed HTML tags within this block's content.
     * Tags not in this list will be filtered out during processing.
     *
     * @return array
     * @example ['b', 'a', 'i', 'u', 's']
     */
    public function getAllowedTags(): array;

    /**
     * Sets the list of primary child element types.
     * WARNING: This was protected in the original class. Consider if it should be part of the public contract.
     * Alternatively, you could only include the getter in the interface.
     *
     * @param array $tags
     * @return self
     */
    public function setPrimaryChilds(array $tags): self;

    /**
     * Gets the list of primary child element types that require special processing.
     * These are top-level items within 'data' that are treated as structured objects rather than simple content.
     *
     * @return array
     */
    public function getPrimaryChilds(): array;

    /*****************************************************************
     * EXTERNAL RESOURCE VALIDATION (For links, media, etc.)
     ****************************************************************/

    /**
     * Sets the list of allowed URL protocols for external resources.
     *
     * @param array $protocols
     * @return self
     */
    public function setSourceProtocols(array $protocols): self;

    /**
     * Gets the list of allowed URL protocols for external resources (e.g., 'https', 'http').
     * Used to validate `src` and `href` attributes.
     *
     * @return array
     */
    public function getSourceProtocols(): array;

    /**
     * Sets the list of allowed hostnames for external resources.
     *
     * @param array $hosts
     * @return self
     */
    public function setSourceHosts(array $hosts): self;

    /**
     * Gets the list of allowed hostnames for external resources.
     * If empty, all hosts are allowed (subject to protocol rules).
     *
     * @return array
     */
    public function getSourceHosts(): array;

    /**
     * Sets the list of regular expressions for validating external resource URLs.
     *
     * @param array $regex
     * @return self
     */
    public function setSourceRegex(array $regex): self;

    /**
     * Gets the list of regular expressions for validating external resource URLs.
     *
     * @return array
     */
    public function getSourceRegex(): array;

    /**
     * Sets the list of allowed MIME types for embedded content or file blocks.
     *
     * @param array $types
     * @return self
     */
    public function setSourceMimeTypes(array $types): self;

    /**
     * Gets the list of allowed MIME types for embedded content or file blocks.
     *
     * @return array
     * @example ['image/jpeg', 'image/png', 'video/mp4']
     */
    public function getSourceMimeTypes(): array;

    /**
     * Sets additional CSS classes for the block's output.
     *
     * @param string $cssClasses
     * @return self
     */
    public function setCssClasses(string $cssClasses): self;

    /**
     * Gets additional CSS classes for the block's output.
     *
     * @return string
     */
    public function getCssClasses(): string;

    /**
     * Processes a single custom item before rendering.
     * This hook is called for each item in the block's data array if `isCustomItemStructure()` returns true.
     *
     * @param array|string $item The raw item data to process.
     * @return array|string The processed item data.
     */
    public function eachCustomItem(array|string $item): array|string;

    /**
     * Renders the entire block to its HTML representation.
     * This method is called if `isCustomRenderBlock()` returns true.
     *
     * @param array $block The processed block data (with 'type', 'data', and optionally 'attr').
     * @return string The rendered HTML string.
     */
    public function renderBlock(array $block): string;

    /**
     * A hook method called after model instantiation and configuration.
     * Can be used by implementing classes to set up rules, structures, or other properties dynamically.
     * This is preferable to using the constructor for complex setup.
     */
    public function onLoad(): void;
}
