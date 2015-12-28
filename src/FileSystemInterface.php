<?php

namespace ActiveCollab\FileSystem;

use ActiveCollab\FileSystem\Adapter\AdapterInterface;

/**
 * @package ActiveCollab\FileSystem
 */
interface FileSystemInterface extends AdapterInterface
{
    /**
     * Return user Adapter instance
     *
     * @return \ActiveCollab\FileSystem\Adapter\AdapterInterface
     */
    public function &getAdapter();
}
