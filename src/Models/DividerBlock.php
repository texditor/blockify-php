<?php

namespace Texditor\Blockify\Models;

use Texditor\Blockify\BlockModel;

class DividerBlock extends BlockModel
{
    protected string $inputName = 'divider';
    protected string $outputName = 'div';
    protected bool $noData = true;

    public function onLoad(): void
    {
        $className = $this->getRenderAttribute('class');

        if (!$className) {
            $this->setRenderAttribute(
                'class',
                $this->getCssName()
            );
        }
    }
}
