<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class OrderedListBlock extends BlockModel
{
    protected string $inputName = 'ol';
    protected string $outputName = 'ol';
    protected array $primaryChildren = ['li'];
    protected bool $isRemoveControlCharacters = true;
}
