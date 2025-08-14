<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class HeaderBlock extends BlockModel
{
    protected string $inputName = 'h1';
    protected string $outputName = 'h1';
    protected array $allowedTags = ['a', 'sub', 'sup'];
}
