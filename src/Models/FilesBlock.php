<?php

namespace Texditor\Blockify\Models;

/**
 * @deprecated since version 2.1.0
 */
class FilesBlock extends FileBlock
{
    protected string $inputName = 'files';
    protected string $outputName = 'div';
    protected bool $isLinkStrategy = true;
}
