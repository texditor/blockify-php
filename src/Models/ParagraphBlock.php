<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class ParagraphBlock extends BlockModel
{
    protected string $inputName = 'p';
    protected string $outputName = 'p';
    protected bool $isRemoveControlCharacters = true;
}
