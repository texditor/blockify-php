<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class CodeBlock extends BlockModel
{
    protected string $inputName = 'code';
    protected string $outputName = 'code';
    protected array $allowedTags = [];
}
