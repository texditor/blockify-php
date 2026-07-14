<?php

namespace Texditor\Blockify\Interfaces;

interface BlockifyInterface
{
    /**
     * Get current configuration
     *
     * @return \Texditor\Blockify\Interfaces\ConfigInterface
     */
    public function config(): ConfigInterface;

    /**
     * Normalizes input string by removing invisible characters
     *
     * @param string $input
     * @param string $mode 'text' or 'preformatted'
     * @return string
     */
    public function normalizeInput(string $input, string $mode = 'text'): string;

    /**
     * Escapes Unicode control characters in preformatted text
     *
     * @param string $text
     * @return string
     */
    public function escapeUnicodeCharsForPre(string $text): string;

    /**
     * Removes invisible Unicode control characters
     *
     * @param string $input
     * @return string
     */
    public function removeInvisibleChars(string $input): string;

    /**
     * Set and process input data
     *
     * @param array|string $data
     * @return self
     * @throws \Texditor\Blockify\Exceptions\InvalidJsonDataException
     */
    public function setData($data): self;

    /**
     * Get processed data
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Completely removes control characters and special Unicode formatting marks
     *
     * @param string $input
     * @return string
     */
    public function removeControlCharacters(string $input): string;

    /**
     * Filters and validates item attributes using model-defined rules
     *
     * @param array $item
     * @param \Texditor\Blockify\Interfaces\BlockModelInterface $model
     * @return array
     */
    public function applyAttributeRules(array &$item, BlockModelInterface $model): array;

    /**
     * Filter of data elements
     *
     * @param callable $filter
     * @return self
     */
    public function dataFilter(callable $filter): self;

    /**
     * Filter of individual data items within each element.
     * 
     * @param callable $filter The function being called
     * @return self
     */
    public function itemDataFilter(callable $filter): self;

    /**
     * Filter data according to validation rules
     *
     * @param array $data
     * @param array $rules
     * @return array|null
     */
    public function filterDataWithRules(array $data, array $rules);

    /**
     * Block filtering
     *
     * @param callable $filter
     * @return self
     */
    public function filter(callable $filter): self;

    /**
     * Whether the structure contains no errors
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Get all validation errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Add an error grouped by error code
     *
     * @param string $code
     * @param array $data
     * @return void
     */
    public function addError(string $code, array $data = []): void;
}
