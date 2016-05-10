<?php

/*
 * This file is part of the Active Collab File System.
 *
 * (c) A51 doo <info@activecollab.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace ActiveCollab\FileSystem\Test;

use ActiveCollab\FileSystem\Adapter\LocalAdapter;
use ActiveCollab\FileSystem\FileSystem;
use ActiveCollab\FileSystem\FileSystemInterface;

/**
 * @package ActiveCollab\Memories\Test
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FileSystemInterface
     */
    protected $filesystem;

    /**
     * Set up test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->filesystem = new FileSystem(new LocalAdapter(__DIR__ . '/sandbox'));
    }

    /**
     * Tear down test environment.
     */
    public function tearDown()
    {
        $this->filesystem->emptyDir('/', ['.gitignore']);

        $this->assertEquals([], $this->filesystem->subdirs());

        parent::tearDown();
    }
}
