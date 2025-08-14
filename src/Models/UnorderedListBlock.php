<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class UnorderedListBlock extends BlockModel
{
    protected string $inputName = 'ul';
    protected string $outputName = 'ul';
    protected array $primaryChilds = ['li'];
}
