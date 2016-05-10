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

/**
 * @package ActiveCollab\FileSystem\Test
 */
class RenameTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage New file name is required
     */
    public function testRenameFileNewNameIsRequired()
    {
        $this->filesystem->renameFile('file-to-be-renamed.txt', '');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage File file-to-be-renamed.txt does not exist
     */
    public function testRenameFileThatDoesNotExist()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/file-to-be-renamed.txt');
        $this->filesystem->renameFile('file-to-be-renamed.txt', 'new-file-name.txt');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to rename file-to-be-renamed.txt to existing-file.txt, existing-file.txt already exists
     */
    public function testRenameFileToAnExistingFile()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/file-to-be-renamed.txt');
        $this->assertFileNotExists(__DIR__ . '/sandbox/existing-file.txt');

        $this->filesystem->writeFile('file-to-be-renamed.txt', 'File content', 0777);
        $this->filesystem->writeFile('existing-file.txt', 'File content', 0777);

        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-renamed.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/existing-file.txt');

        $this->filesystem->renameFile('file-to-be-renamed.txt', 'existing-file.txt');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Rename option can't be used to move file to a different directory
     */
    public function testRenameFileToDifferentDirectory()
    {
        $this->filesystem->createDir('subdir');
        $this->filesystem->createDir('subdir/subsubdir');

        $this->assertFileNotExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');
        $this->filesystem->writeFile('subdir/file-to-be-renamed.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');

        $this->filesystem->renameFile('subdir/file-to-be-renamed.txt', 'subsubdir/new-file-name.txt');
    }

    /**
     * Test successful file rename.
     */
    public function testRenameFile()
    {
        $this->filesystem->createDir('subdir');

        $this->assertFileNotExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');
        $this->filesystem->writeFile('subdir/file-to-be-renamed.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');

        $this->filesystem->renameFile('subdir/file-to-be-renamed.txt', 'new-file-name.txt');

        $this->assertFileNotExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/subdir/new-file-name.txt');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage New directory name is required
     */
    public function testRenameDirNewNameIsRequired()
    {
        $this->filesystem->renameDir('tmp', '');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Directory tmp does not exist
     */
    public function testRenameDirThatDoesNotExist()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/tmp');
        $this->filesystem->renameDir('tmp', 'tmp2');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Failed to rename subdir to subdir2, subdir2 exists
     */
    public function testRenameDirToAnExistingDir()
    {
        $this->filesystem->createDir('subdir');
        $this->filesystem->createDir('subdir2');

        $this->filesystem->renameDir('subdir', 'subdir2');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Rename option can't be used to move a directory to a different directory
     */
    public function testRenameDirToDifferentDirectory()
    {
        $this->filesystem->createDir('subdir');
        $this->filesystem->createDir('subdir2');
        $this->filesystem->createDir('subdir2/subsubdir');

        $this->filesystem->renameDir('subdir', 'subdir2/subdir');
    }

    /**
     * Test successful file rename.
     */
    public function testRenameDir()
    {
        $this->filesystem->createDir('subdir');

        $this->assertFileNotExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');
        $this->filesystem->writeFile('subdir/file-to-be-renamed.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');

        $this->filesystem->renameDir('subdir', 'subdir-xyz');

        $this->assertFileNotExists(__DIR__ . '/sandbox/subdir/file-to-be-renamed.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/subdir-xyz/file-to-be-renamed.txt');
    }
}
