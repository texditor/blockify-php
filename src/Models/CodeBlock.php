<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class CodeBlock extends BlockModel
{
    /** @var string The input field name for the block type identifier */
    protected string $inputName = 'code';

    /** @var string The output HTML tag name for rendering */
    protected string $outputName = 'pre';

    /** @var array List of allowed HTML tags (empty = no restrictions) */
    protected array $allowedTags = [];

    /** @var bool Whether the block content is preformatted text */
    protected bool $isPreformatted = true;

    /**
     * Default list of supported programming languages and file formats.
     * Used as fallback when no custom languages are set.
     *
     * @var array<int, string>
     */
    private $defaultLanguages = [
        'bash',
        'c',
        'clojure',
        'cmake',
        'cpp',
        'csharp',
        'css',
        'csv',
        'dart',
        'diff',
        'dockerfile',
        'elixir',
        'erlang',
        'fsharp',
        'go',
        'graphql',
        'groovy',
        'haskell',
        'hcl',
        'html',
        'ini',
        'java',
        'javascript',
        'json',
        'julia',
        'kotlin',
        'latex',
        'less',
        'lua',
        'makefile',
        'markdown',
        'matlab',
        'nginx',
        'objectivec',
        'perl',
        'php',
        'plaintext',
        'powershell',
        'python',
        'r',
        'ruby',
        'rust',
        'scala',
        'scss',
        'shell',
        'solidity',
        'sql',
        'swift',
        'svelte',
        'toml',
        'typescript',
        'vim',
        'vue',
        'wasm',
        'xml',
        'yaml',
    ];

    /**
     * Custom list of languages set by the user.
     * If empty, the default list will be used.
     *
     * @var array<int, string>
     */
    private $languages = [];

    /**
     * Initialize the block structure on model load.
     *
     * Sets up the required fields: type, data, and optional language selector
     * with values from the configured languages list.
     *
     * @return void
     */
    public function onLoad(): void
    {
        $this->addBlockAttribute('lang', [
            'type' => 'string',
            'text' => true,
            'values' => $this->getLanguages(),
            'render' => true
        ]);
    }

    /**
     * Override the default languages list with a custom one.
     *
     * @param array<int, string> $languages Custom list of supported languages
     * @return self Returns the instance for method chaining
     */
    public function setLanguages(array $languages): self
    {
        $this->languages = $languages;

        return $this;
    }

    /**
     * Get the list of supported languages.
     *
     * Returns custom languages if set, otherwise falls back to the default list.
     *
     * @return array<int, string> List of supported language identifiers
     */
    public function getLanguages(): array
    {
        return !empty($this->languages) ? $this->languages : $this->defaultLanguages;
    }
}
