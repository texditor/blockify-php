<?php

namespace Texditor\Blockify\Interfaces;

interface FileBlockInterface extends BlockModelInterface
{
    /**
     * Set whether files should be downloadable.
     *
     * @param bool $status Downloadable status
     * @return self
     */
    public function setIsDownloadable(bool $status): self;

    /**
     * Check if files are downloadable.
     *
     * @return bool Downloadable status
     */
    public function isDownloadable(): bool;

    /**
     * Set whether to use link strategy for files.
     *
     * @param bool $status Link strategy status
     * @return self
     */
    public function setIsLinkStrategy(bool $status): self;

    /**
     * Check if link strategy is enabled.
     *
     * @return bool Link strategy status
     */
    public function isLinkStrategy(): bool;
}
