<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class TableBlock extends BlockModel
{
    /**
     * Input field name for the block type identifier
     *
     * @var string
     */
    protected string $inputName = 'table';

    /**
     * Output HTML tag name for rendering
     *
     * @var string
     */
    protected string $outputName = 'table';

    /**
     * List of allowed HTML tags at the top level of the block.
     * Empty because rows are rendered manually in renderCustomBlock().
     *
     * @var array
     */
    protected array $allowedTags = [];

    /**
     * Whether the block uses a non-standard block structure definition.
     * Enabled because rows/cells require custom multi-level processing.
     *
     * @var bool
     */
    protected bool $isCustomBlockStructure = true;

    /**
     * Whether items within the block use a custom structure.
     * Enabled to preserve nested rows/cells/inline tags as-is for manual rendering.
     *
     * @var bool
     */
    protected bool $isCustomItemStructure = true;

    /**
     * Whether the block is rendered by a custom renderer.
     * Enabled because the table requires its own recursive renderer.
     *
     * @var bool
     */
    protected bool $isCustomRenderBlock = true;

    /**
     * Whether adjacent similar items should be merged.
     * Disabled because table rows must not be merged.
     *
     * @var bool
     */
    protected bool $mergeSimilar = false;

    /**
     * Whether control characters should be removed from the data.
     *
     * @var bool
     */
    protected bool $isRemoveControlCharacters = true;

    /**
     * HTML tags allowed inside table cells (th / td content).
     * Note: 'br' is handled separately through the maxBreaks limit.
     *
     * @var array
     */
    protected array $cellAllowedTags = [
        'b',
        'a',
        'i',
        'u',
        's',
        'sub',
        'sup',
        'code',
        'mark',
    ];

    /**
     * Allowed values for the 'align' attribute of a cell (horizontal alignment).
     *
     * @var array
     */
    protected array $alignValues = ['left', 'center', 'right'];

    /**
     * Allowed values for the 'valign' attribute of a cell (vertical alignment).
     *
     * @var array
     */
    protected array $valignValues = ['top', 'middle', 'bottom'];

    /**
     * Maximum number of consecutive <br> elements allowed inside a single cell.
     * A value of 0 disables breaks inside cells.
     *
     * @var int
     */
    protected int $maxBreaks = 2;

    /**
     * Maximum number of rows rendered from a table block.
     * Rows beyond this limit are silently dropped during rendering.
     *
     * @var int
     */
    protected int $maxRows = 100;

    /**
     * Maximum number of columns rendered per row.
     * Cells beyond this limit are silently dropped during rendering.
     *
     * @var int
     */
    protected int $maxCols = 10;

    /**
     * Initialize block structure, item structure and cell attribute rules.
     *
     * @return void
     */
    public function onLoad(): void
    {
        $this->setBlockStructure([
            'type' => [
                'type' => 'string',
                'required' => true,
                'values' => $this->getInputName(),
            ],
            'data' => [
                'type' => 'array',
                'required' => true,
            ],
        ]);

        $this->setItemStructure([
            'type' => ['type' => 'string'],
            'data' => ['type' => 'array'],
            'attr' => ['type' => 'array'],
        ]);

        // Rules for cell attributes (applied in renderCellAttributes)
        $this->tagAttributeRules = [
            'th' => [
                'align' => [
                    'type' => 'string',
                    'values' => $this->alignValues,
                ],
                'valign' => [
                    'type' => 'string',
                    'values' => $this->valignValues,
                ],
            ],
            'td' => [
                'align' => [
                    'type' => 'string',
                    'values' => $this->alignValues,
                ],
                'valign' => [
                    'type' => 'string',
                    'values' => $this->valignValues,
                ],
            ],
        ];
    }

    /**
     * Set the maximum number of rows allowed in the table.
     * A value of 0 disables rows entirely.
     *
     * @param int $count Maximum rows count
     * @return self
     */
    public function setMaxRows(int $count): self
    {
        $this->maxRows = max(0, $count);

        return $this;
    }

    /**
     * Get the maximum number of rows allowed in the table.
     *
     * @return int
     */
    public function getMaxRows(): int
    {
        return $this->maxRows;
    }

    /**
     * Set the maximum number of columns allowed per row.
     * A value of 0 disables cells entirely.
     *
     * @param int $count Maximum columns per row
     * @return self
     */
    public function setMaxCols(int $count): self
    {
        $this->maxCols = max(0, $count);

        return $this;
    }

    /**
     * Get the maximum number of columns allowed per row.
     *
     * @return int
     */
    public function getMaxCols(): int
    {
        return $this->maxCols;
    }

    /**
     * Set the list of HTML tags allowed inside table cells.
     *
     * @param array $tags List of allowed tag names (e.g. ['b', 'i', 'mark'])
     * @return self
     */
    public function setCellAllowedTags(array $tags): self
    {
        $this->cellAllowedTags = $tags;

        return $this;
    }

    /**
     * Get the list of HTML tags allowed inside table cells.
     *
     * @return array
     */
    public function getCellAllowedTags(): array
    {
        return $this->cellAllowedTags;
    }

    /**
     * Set allowed values for the 'align' attribute (horizontal alignment).
     *
     * @param array $values List of allowed values (e.g. ['left', 'center', 'right'])
     * @return self
     */
    public function setAlignValues(array $values): self
    {
        $this->alignValues = $values;

        return $this;
    }

    /**
     * Get allowed values for the 'align' attribute.
     *
     * @return array
     */
    public function getAlignValues(): array
    {
        return $this->alignValues;
    }

    /**
     * Set allowed values for the 'valign' attribute (vertical alignment).
     *
     * @param array $values List of allowed values (e.g. ['top', 'middle', 'bottom'])
     * @return self
     */
    public function setValignValues(array $values): self
    {
        $this->valignValues = $values;

        return $this;
    }

    /**
     * Get allowed values for the 'valign' attribute.
     *
     * @return array
     */
    public function getValignValues(): array
    {
        return $this->valignValues;
    }

    /**
     * Render the table block.
     *
     * @param array $block Processed block data (with 'type' and 'data')
     * @return string Rendered HTML
     */
    protected function renderCustomBlock(array $block): string
    {
        $cssName = $this->getCssName();
        $cssClasses = $this->getCssClasses();

        $class = $cssName
            . (!empty($cssClasses) ? ' ' . trim($cssClasses) : '');

        $rows = '';
        $rowsCount = 0;

        foreach ($block['data'] as $row) {
            if ($rowsCount >= $this->maxRows) {
                break;
            }

            $rows .= $this->renderRow($row);
            $rowsCount++;
        }

        return '<table class="' . $class . '">' . $rows . '</table>';
    }

    /**
     * Render a single table row (<tr>).
     * Stops after maxCols cells have been rendered.
     *
     * @param array $row Row data
     * @return string Rendered HTML
     */
    protected function renderRow(array $row): string
    {
        if (($row['type'] ?? null) !== 'tr' || empty($row['data'])) {
            return '';
        }

        $cells = '';
        $colsCount = 0;

        foreach ($row['data'] as $cell) {
            if ($colsCount >= $this->maxCols) {
                break;
            }

            $rendered = $this->renderCell($cell);

            if ($rendered === '') {
                continue;
            }

            $cells .= $rendered;
            $colsCount++;
        }

        return '<tr>' . $cells . '</tr>';
    }

    /**
     * Render a single table cell (<th> or <td>).
     * Applies align/valign attributes and clamps consecutive <br> by maxBreaks.
     *
     * @param array $cell Cell data
     * @return string Rendered HTML
     */
    protected function renderCell(array $cell): string
    {
        $type = $cell['type'] ?? null;

        if (!in_array($type, ['th', 'td'], true) || empty($cell['data'])) {
            return '';
        }

        $attributes = $this->renderCellAttributes($type, $cell['attr'] ?? []);

        $content = '';
        $breakRun = 0;

        foreach ($cell['data'] as $item) {
            if (is_string($item)) {
                $breakRun = 0;
                $content .= $this->config()->isRenderEscape()
                    ? escape($item)
                    : $item;
            } elseif (is_array($item)) {
                $isBreak = ($item['type'] ?? null) === 'br';

                if ($isBreak) {
                    if ($breakRun >= $this->maxBreaks) {
                        continue;
                    }

                    $breakRun++;
                    $content .= '<br>';
                    continue;
                }

                $breakRun = 0;
                $content .= $this->renderInlineItem($item);
            }
        }

        return '<' . $type . $attributes . '>' . $content . '</' . $type . '>';
    }

    /**
     * Validate and render cell attributes ('align' and 'valign').
     * Only allowed values from tagAttributeRules are emitted.
     *
     * @param string $type Cell type ('th' or 'td')
     * @param array $attr Raw attributes from input data
     * @return string Rendered attributes (with leading space) or empty string
     */
    protected function renderCellAttributes(string $type, array $attr): string
    {
        if (empty($attr)) {
            return '';
        }

        $rules = $this->getTagAttributeRules($type);
        $result = [];

        foreach (['align', 'valign'] as $name) {
            if (!isset($attr[$name]) || !is_string($attr[$name])) {
                continue;
            }

            $value = trim($attr[$name]);

            if (empty($rules[$name]['values'])) {
                continue;
            }

            if (!in_array($value, $rules[$name]['values'], true)) {
                continue;
            }

            $result[$name] = $value;
        }

        return $this->renderAttributes($result);
    }

    /**
     * Recursively render an inline item inside a cell (mark, b, i, a, etc.).
     * Note: 'br' at the top level is handled by renderCell().
     *
     * @param array $item Inline item data
     * @return string Rendered HTML
     */
    protected function renderInlineItem(array $item): string
    {
        $type = $item['type'] ?? null;

        if (empty($type)) {
            return '';
        }

        if (!in_array($type, $this->cellAllowedTags, true)) {
            return '';
        }

        $content = '';

        foreach ($item['data'] ?? [] as $child) {
            if (is_string($child)) {
                $content .= $this->config()->isRenderEscape()
                    ? escape($child)
                    : $child;
            } elseif (is_array($child)) {
                if (($child['type'] ?? null) === 'br') {
                    $content .= '<br>';
                    continue;
                }

                $content .= $this->renderInlineItem($child);
            }
        }

        $attributes = '';

        if (!empty($item['attr']) && is_array($item['attr'])) {
            $attributes = $this->renderAttributes($item['attr']);
        }

        $renderTagNames = $this->config()->getRenderTagNames();
        $tagName = $renderTagNames[$type] ?? $type;

        return '<' . $tagName . $attributes . '>'
            . $content
            . '</' . $tagName . '>';
    }
}
