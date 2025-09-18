<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class CodeBlock extends BlockModel
{
    protected string $inputName = 'code';
    protected string $outputName = 'pre';
    protected array $allowedTags = [];
    protected bool $isPreformatted = true;
}
