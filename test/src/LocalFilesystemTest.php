<?php

namespace ActiveCollab\FileSystem\Test;

use ActiveCollab\FileSystem\FilesystemInterface;
use ActiveCollab\FileSystem\FileSystem;
use ActiveCollab\FileSystem\Adapter\Local;
use InvalidArgumentException;

/**
 * Class LocalFilesystemTest
 */
class LocalFilesystemTest extends TestCase
{
    /**
     * @var FileSystemInterface
     */
    private $filesystem;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        parent::setUp();

        $this->filesystem = new FileSystem(new Local(__DIR__ . '/sandbox'));
    }

    /**
     * Tear down test environment
     */
    public function tearDown()
    {
        $this->filesystem->emptyDir('/', ['.gitignore']);

        $this->assertEquals([], $this->filesystem->subdirs());

        parent::tearDown();
    }

    /**
     * Check if we properly set local adapter
     */
    public function testAdapterIsLocal()
    {
        /** @var \ActiveCollab\FileSystem\Adapter\Local $adapter */
        $adapter = $this->filesystem->getAdapter();

        $this->assertInstanceOf('\ActiveCollab\FileSystem\Adapter\Local', $adapter);
        $this->assertEquals(__DIR__ . '/sandbox/', $adapter->getSandboxPath());
    }

    /**
     * Test if getFullPath is working correctly
     */
    public function testFullPath()
    {
        $this->assertEquals(__DIR__ . '/sandbox/.gitignore', $this->filesystem->getFullPath('.gitignore'));
        $this->assertEquals(__DIR__ . '/sandbox/.gitignore', $this->filesystem->getFullPath('/.gitignore'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFullPathThrowsAnExceptionWhenPathIsOutsideOfTheSandbox()
    {
        $this->filesystem->getFullPath('../../password-file');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSubdirsThrowAnExceptionOnFilePath()
    {
        $this->filesystem->subdirs('.gitignore');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSubdirsThrowAnExceptionMissingDir()
    {
        $this->filesystem->subdirs('this-directory-does-not-exist');
    }

    public function testFiles()
    {
        mkdir(__DIR__ . '/sandbox/subdirectory1', 0777);

        file_put_contents(__DIR__ . '/sandbox/subdirectory1/file1.txt', '123');
        file_put_contents(__DIR__ . '/sandbox/subdirectory1/file2.txt', '456');
        file_put_contents(__DIR__ . '/sandbox/subdirectory1/.file3.txt', '789');

        $this->assertEquals(['.gitignore'], $this->filesystem->files());
        $this->assertEquals([], $this->filesystem->files('/', false));
        $this->assertEquals(['subdirectory1/.file3.txt', 'subdirectory1/file1.txt', 'subdirectory1/file2.txt'], $this->filesystem->files('subdirectory1'));
        $this->assertEquals(['subdirectory1/file1.txt', 'subdirectory1/file2.txt'], $this->filesystem->files('subdirectory1', false));
    }

    /**
     * Test subdirs
     */
    public function testSubdirs()
    {
        mkdir(__DIR__ . '/sandbox/subdirectory1/subsubdirectory1', 0777, true);
        mkdir(__DIR__ . '/sandbox/subdirectory2');
        mkdir(__DIR__ . '/sandbox/.hidden');

        $this->assertEquals(['.hidden', 'subdirectory1', 'subdirectory2'], $this->filesystem->subdirs());
        $this->assertEquals(['subdirectory1/subsubdirectory1'], $this->filesystem->subdirs('subdirectory1'));
        $this->assertEquals([], $this->filesystem->subdirs('subdirectory2'));
    }

    /**
     * Test if files are properly deleted
     */
    public function testDeleteAFile()
    {
        file_put_contents(__DIR__ . '/sandbox/a-file.txt', '123');
        $this->assertFileExists(__DIR__ . '/sandbox/a-file.txt');

        $this->filesystem->delete('a-file.txt');
        $this->assertFileNotExists(__DIR__ . '/sandbox/a-file.txt');
    }

    /**
     * Test if linked files are properly unlinked
     */
    public function testDeleteALinkToAFile()
    {
        file_put_contents(__DIR__ . '/sandbox/a-file.txt', '123');
        $this->assertFileExists(__DIR__ . '/sandbox/a-file.txt');

        symlink(__DIR__ . '/sandbox/a-file.txt', __DIR__ . '/sandbox/a-new-file.txt');

        $this->assertFileExists(__DIR__ . '/sandbox/a-new-file.txt');
        $this->assertTrue(is_link(__DIR__ . '/sandbox/a-new-file.txt'));
        $this->assertEquals(__DIR__ . '/sandbox/a-file.txt', readlink(__DIR__ . '/sandbox/a-new-file.txt'));

        $this->filesystem->delete('a-new-file.txt');

        $this->assertFileNotExists(__DIR__ . '/sandbox/a-new-file.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/a-file.txt');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDeleteExceptionWhenTryingToDeleteANonExistingFile()
    {
        $this->filesystem->deleteDir('file-that-does-not-exist.txt');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDeleteExceptionWhenDirIsLinked()
    {
        mkdir(__DIR__ . '/sandbox/subdirectory2');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory2');

        symlink(__DIR__ . '/sandbox/subdirectory2', __DIR__ . '/sandbox/subdirectory3');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory3');
        $this->assertTrue(is_link(__DIR__ . '/sandbox/subdirectory3'));
        $this->assertEquals(__DIR__ . '/sandbox/subdirectory2', readlink(__DIR__ . '/sandbox/subdirectory3'));

        $this->filesystem->delete('subdirectory3');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testDeleteExceptionWhenTrygingToDeleteADir()
    {
        mkdir(__DIR__ . '/sandbox/subdirectory2');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory2');

        $this->filesystem->delete('subdirectory2');
    }

    /**
     * Test create a new file
     */
    public function testCreateFile()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/file-to-be-created.txt');
        $this->filesystem->createFile('file-to-be-created.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-created.txt');
        $this->assertEquals('File content', file_get_contents(__DIR__ . '/sandbox/file-to-be-created.txt'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testCreateFileExceptionOnExistingFile()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/file-to-be-created.txt');
        $this->filesystem->createFile('file-to-be-created.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-created.txt');
        $this->filesystem->createFile('file-to-be-created.txt', 'File content', 0777);
    }

    /**
     * Test if write file creates a file it is missing
     */
    public function testWriteFileCreatesAMissingFile()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/file-to-be-created.txt');

        $this->filesystem->writeFile('file-to-be-created.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-created.txt');
        $this->assertEquals('File content', file_get_contents(__DIR__ . '/sandbox/file-to-be-created.txt'));
    }

    /**
     * Test if write file updates a file
     */
    public function testWriteFileUpdatesAnExistingFile()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/file-to-be-updated.txt');

        $this->filesystem->createFile('file-to-be-updated.txt', 'One!', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-updated.txt');
        $this->assertEquals('One!', file_get_contents(__DIR__ . '/sandbox/file-to-be-updated.txt'));

        $this->filesystem->writeFile('file-to-be-updated.txt', 'Two!', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-updated.txt');
        $this->assertEquals('Two!', file_get_contents(__DIR__ . '/sandbox/file-to-be-updated.txt'));
    }

    /**
     * Test replace in file
     */
    public function testReplaceInFile()
    {
        $this->filesystem->createFile('file.txt', 'One!', 0777);

        $this->assertFileExists(__DIR__ . '/sandbox/file.txt');
        $this->assertEquals('One!', file_get_contents(__DIR__ . '/sandbox/file.txt'));

        $this->filesystem->replaceInFile('file.txt', ['One' => 'Two']);

        $this->assertFileExists(__DIR__ . '/sandbox/file.txt');
        $this->assertEquals('Two!', file_get_contents(__DIR__ . '/sandbox/file.txt'));
    }

    /**
     * Test copy file
     */
    public function testCopyFile()
    {
        $this->filesystem->createFile('file-to-be-copied.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-copied.txt');
        $this->filesystem->copyFile($this->filesystem->getFullPath('file-to-be-copied.txt'), 'file-copy.txt', 0777);

        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-copied.txt');
        $this->assertEquals('File content', file_get_contents(__DIR__ . '/sandbox/file-to-be-copied.txt'));

        $this->assertFileExists(__DIR__ . '/sandbox/file-copy.txt');
        $this->assertEquals('File content', file_get_contents(__DIR__ . '/sandbox/file-copy.txt'));
    }

    /**
     * Test link file
     */
    public function testLinkFile()
    {
        $this->filesystem->createFile('file-to-be-linked.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-linked.txt');

        $this->filesystem->link($this->filesystem->getFullPath('file-to-be-linked.txt'), 'linked-file.txt');

        $this->assertFileExists(__DIR__ . '/sandbox/linked-file.txt');
        $this->assertTrue(is_link(__DIR__ . '/sandbox/linked-file.txt'));
        $this->assertEquals(__DIR__ . '/sandbox/file-to-be-linked.txt', readlink(__DIR__ . '/sandbox/linked-file.txt'));
    }

    /**
     * Test directory creation
     */
    public function testCreateDir()
    {
        $this->assertFileNotExists(__DIR__ . '/sandbox/subdirectory1/subsubdirectory1');
        $this->filesystem->createDir('subdirectory1/subsubdirectory1');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory1/subsubdirectory1');
    }

    /**
     * Test copy directory
     */
    public function testCopyDir()
    {
        $this->filesystem->createFile('file-to-be-linked.txt', 'File content', 0777);
        $this->assertFileExists(__DIR__ . '/sandbox/file-to-be-linked.txt');

        $this->filesystem->createDir('dir-to-be-copied');
        $this->filesystem->createDir('dir-to-be-copied/subfolder');

        $this->filesystem->createFile('dir-to-be-copied/file.txt', 'File #1', 0777);
        $this->filesystem->createFile('dir-to-be-copied/subfolder/file.txt', 'File #2', 0777);

        $this->assertFileExists(__DIR__ . '/sandbox/dir-to-be-copied/file.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/dir-to-be-copied/subfolder/file.txt');

        $this->filesystem->link($this->filesystem->getFullPath('file-to-be-linked.txt'), 'dir-to-be-copied/linked-file.txt');

        $this->assertFileExists(__DIR__ . '/sandbox/dir-to-be-copied/linked-file.txt');
        $this->assertTrue(is_link(__DIR__ . '/sandbox/dir-to-be-copied/linked-file.txt'));
        $this->assertEquals(__DIR__ . '/sandbox/file-to-be-linked.txt', readlink(__DIR__ . '/sandbox/dir-to-be-copied/linked-file.txt'));

        $this->filesystem->copyDir($this->filesystem->getFullPath('dir-to-be-copied'), 'dir-copy');

        // Folders
        $this->assertFileExists(__DIR__ . '/sandbox/dir-copy');
        $this->assertFileExists(__DIR__ . '/sandbox/dir-copy/subfolder');

        // Files
        $this->assertFileExists(__DIR__ . '/sandbox/dir-copy/file.txt');
        $this->assertEquals('File #1', file_get_contents(__DIR__ . '/sandbox/dir-copy/file.txt'));

        $this->assertFileExists(__DIR__ . '/sandbox/dir-copy/subfolder/file.txt');
        $this->assertEquals('File #2', file_get_contents(__DIR__ . '/sandbox/dir-copy/subfolder/file.txt'));

        // Link
        $this->assertFileExists(__DIR__ . '/sandbox/dir-copy/linked-file.txt');
        $this->assertTrue(is_link(__DIR__ . '/sandbox/dir-copy/linked-file.txt'));
        $this->assertEquals(__DIR__ . '/sandbox/file-to-be-linked.txt', readlink(__DIR__ . '/sandbox/dir-copy/linked-file.txt'));
    }

    /**
     * Test empty directory
     */
    public function testEmptyDir()
    {
        mkdir(__DIR__ . '/sandbox/subdirectory1/subsubdirectory1', 0777, true);
        mkdir(__DIR__ . '/sandbox/subdirectory2');
        mkdir(__DIR__ . '/sandbox/.hidden');

        file_put_contents(__DIR__ . '/sandbox/subdirectory1/subsubdirectory1/a-file.txt', '123');
        file_put_contents(__DIR__ . '/sandbox/.hidden/a-file-2.txt', '123');

        symlink(__FILE__, __DIR__ . '/sandbox/.hidden/' . basename(__FILE__));

        $this->assertFileExists(__DIR__ . '/sandbox/.gitignore');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory1/subsubdirectory1/a-file.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory2');
        $this->assertFileExists(__DIR__ . '/sandbox/.hidden');

        $this->assertFileExists(__DIR__ . '/sandbox/.hidden/' . basename(__FILE__));
        $this->assertTrue(is_link(__DIR__ . '/sandbox/.hidden/' . basename(__FILE__)));
        $this->assertEquals(__FILE__, readlink(__DIR__ . '/sandbox/.hidden/' . basename(__FILE__)));

        $this->filesystem->emptyDir('/', ['.gitignore']);

        $this->assertFileExists(__DIR__ . '/sandbox/.gitignore');
        $this->assertFileNotExists(__DIR__ . '/sandbox/subdirectory1/subsubdirectory1/a-file.txt');
        $this->assertFileNotExists(__DIR__ . '/sandbox/subdirectory2');
        $this->assertFileNotExists(__DIR__ . '/sandbox/.hidden');
    }

    /**
     * Test recursive directory delete
     */
    public function testDeleteDir()
    {
        mkdir(__DIR__ . '/sandbox/subdirectory1/folder', 0777, true);
        mkdir(__DIR__ . '/sandbox/subdirectory1/.hidden');

        file_put_contents(__DIR__ . '/sandbox/subdirectory1/folder/a-file.txt', '123');
        file_put_contents(__DIR__ . '/sandbox/subdirectory1/.hidden/a-file-2.txt', '123');

        symlink(__FILE__, __DIR__ . '/sandbox/subdirectory1/.hidden/' . basename(__FILE__));

        $this->assertFileExists(__DIR__ . '/sandbox/.gitignore');

        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory1/folder/a-file.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory1/.hidden/a-file-2.txt');
        $this->assertFileExists(__DIR__ . '/sandbox/subdirectory1/.hidden/' . basename(__FILE__));

        $this->assertTrue(is_link(__DIR__ . '/sandbox/subdirectory1/.hidden/' . basename(__FILE__)));
        $this->assertEquals(__FILE__, readlink(__DIR__ . '/sandbox/subdirectory1/.hidden/' . basename(__FILE__)));

        $this->filesystem->deleteDir('subdirectory1');

        $this->assertFileExists(__DIR__ . '/sandbox/.gitignore');
        $this->assertFileNotExists(__DIR__ . '/sandbox/subdirectory1');
    }
}