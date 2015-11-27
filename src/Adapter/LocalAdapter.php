<?php

namespace ActiveCollab\FileSystem\Adapter;

use InvalidArgumentException;
use RuntimeException;

/**
 * @package ActiveCollab\FileSystem\Adapter
 */
class LocalAdapter extends Adapter
{
    /**
     * Construct a new instance of local file system
     *
     * @param string|null $sandbox_path
     */
    public function __construct($sandbox_path)
    {
        $this->setSandboxPath($sandbox_path);
    }

    /**
     * List all files that are in the given path
     *
     * @param  string  $path
     * @param  boolean $include_hidden
     * @return array
     * @throws InvalidArgumentException
     */
    public function files($path = '/', $include_hidden = true)
    {
        $dir_path = $this->withSlash($this->getFullPath($path));

        if (is_dir($dir_path)) {
            $files = $this->filesWithFullPaths($dir_path, $include_hidden);

            if (count($files)) {
                foreach ($files as $k => $path) {
                    $files[ $k ] = mb_substr($path, $this->getSandboxPathLength());
                }
            }

            sort($files);

            return $files;
        } else {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * Return a list of files from a directory
     *
     * This function ignores hidden folders!
     *
     * @param  string  $dir
     * @param  boolean $include_hidden
     * @param  boolean $recursive
     * @return array
     */
    private function filesWithFullPaths($dir, $include_hidden = true, $recursive = false)
    {
        $dir = $this->withSlash($dir);

        $result = [];

        if ($dirstream = opendir($dir)) {
            while (false !== ($filename = readdir($dirstream))) {
                $path = $dir . $filename;

                if ($filename != '.' && $filename != '..') {
                    if (is_dir($path)) {
                        if ($recursive) {
                            $files_from_subdir = $this->filesWithFullPaths($path, $recursive);

                            if (is_array($files_from_subdir)) {
                                $result = array_merge($result, $files_from_subdir);
                            }
                        }
                    } else {
                        if (is_file($path) || (is_link($path) && is_file(readlink($path)))) {
                            if (mb_substr($filename, 0, 1) == '.' && !$include_hidden) {
                                continue;
                            }

                            $result[] = $path;
                        }
                    }
                }
            }
        }

        closedir($dirstream);

        return $result;
    }

    /**
     * List all subdirs that are in the given path
     *
     * @param  string $path
     * @return array
     * @throws InvalidArgumentException
     */
    public function subdirs($path = '/')
    {
        $dir_path = $this->withSlash($this->getFullPath($path));

        if (is_dir($dir_path)) {
            $subdirs = $this->subdirsWithFullPaths($dir_path);

            if (count($subdirs)) {
                foreach ($subdirs as $k => $path) {
                    $subdirs[ $k ] = mb_substr($path, $this->getSandboxPathLength());
                }
            }

            sort($subdirs);

            return $subdirs;
        } else {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * Return the folder list in subfolders from $dir
     *
     * This function ignores hidden folders!
     *
     * @param  string  $dir
     * @param  boolean $recursive
     * @return array
     */
    private function subdirsWithFullPaths($dir, $recursive = false)
    {
        $dir = $this->withSlash($dir);

        $result = [];

        if ($dirstream = opendir($dir)) {
            while (false !== ($filename = readdir($dirstream))) {
                $path = $dir . $filename;
                if ($filename != '.' && $filename != '..' && is_dir($path)) {
                    $result[] = $path;

                    if ($recursive) {
                        $sub_dirs = $this->subdirsWithFullPaths($path, $recursive);
                        if (is_array($sub_dirs)) {
                            $result = array_merge($result, $sub_dirs);
                        }
                    }
                }
            }
        }

        closedir($dirstream);

        return $result;
    }

    /**
     * Create a link between $source and $target
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param  string $source
     * @param  string $target
     * @throws RuntimeException
     */
    public function link($source, $target)
    {
        $target_path = $this->getFullPath($target);

        if (file_exists($target_path)) {
            throw new InvalidArgumentException("$target already exists");
        }

        if (!symlink($source, $target_path)) {
            throw new RuntimeException("Failed to link $source to $target");
        }
    }

    /**
     * Create a new file with the given data and optionally chmod it
     *
     * @param  string       $path
     * @param  string       $data
     * @param  integer|null $mode
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createFile($path, $data, $mode = null)
    {
        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            throw new InvalidArgumentException("File $path already exists");
        }

        if (file_put_contents($file_path, $data)) {
            if ($mode !== null) {
                $old_umask = umask(0);
                chmod($file_path, $mode);
                umask($old_umask);
            }
        } else {
            throw new RuntimeException("Failed to write to $path");
        }
    }

    /**
     * Write to a file. If file does not exist it will be created
     *
     * @param  string       $path
     * @param  string       $data
     * @param  integer|null $mode
     * @throws RuntimeException
     */
    public function writeFile($path, $data, $mode = null)
    {
        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            if (file_put_contents($file_path, $data)) {
                if ($mode !== null) {
                    $old_umask = umask(0);
                    chmod($file_path, $mode);
                    umask($old_umask);
                }
            } else {
                throw new RuntimeException("Failed to write to $path");
            }
        } else {
            $this->createFile($path, $data, $mode);
        }
    }

    /**
     * Replace values in a text file
     *
     * @param string $path
     * @param array  $search_and_replace
     */
    public function replaceInFile($path, array $search_and_replace)
    {
        $file_path = $this->getFullPath($path);

        if (is_file($file_path)) {
            if (!file_put_contents($file_path, str_replace(array_keys($search_and_replace), $search_and_replace, file_get_contents($file_path)))) {
                throw new RuntimeException("Failed to write to $path");
            }
        } else {
            throw new InvalidArgumentException("File $path already exists");
        }
    }

    /**
     * Copy $source file to $target
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string       $source
     * @param string       $target
     * @param integer|null $mode
     */
    public function copyFile($source, $target, $mode = null)
    {
        $target_path = $this->getFullPath($target);

        if (file_exists($target_path)) {
            throw new InvalidArgumentException("$target already exists");
        }

        if (copy($source, $target_path)) {
            if ($mode !== null) {
                $old_umask = umask(0);
                chmod($target_path, $mode);
                umask($old_umask);
            }
        } else {
            throw new RuntimeException("Failed to link $source to $target");
        }
    }

    /**
     * Create a new directory
     *
     * @param  string  $path
     * @param  int     $mode
     * @param  boolean $recursive
     * @return boolean
     */
    public function createDir($path, $mode = 0777, $recursive = true)
    {
        $dir_path = $this->getFullPath($path);

        if (!is_dir($dir_path)) {
            $old_umask = umask(0);
            $dir_created = mkdir($dir_path, $mode, $recursive);
            umask($old_umask);

            return $dir_created;
        }

        return true;
    }

    /**
     * Copy a directory content from $source to $target
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param  string     $source
     * @param  string     $target
     * @param  bool|false $empty_target
     * @throws InvalidArgumentException
     */
    public function copyDir($source, $target, $empty_target = false)
    {
        $source = $this->withSlash($source);

        if (!is_dir($source)) {
            throw new InvalidArgumentException("Source path $source is not a directory");
        }

        $target_path = $this->getFullPath($target);

        if (is_dir($target_path)) {
            if ($empty_target) {
                $this->emptyDir($target);
            }
        } else {
            $this->createDir($target);
        }

        if ($dir_handle = dir($source)) {
            $target = $this->withSlash($target);

            while (false !== ($entry = $dir_handle->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                if (is_link("{$source}{$entry}")) {
                    $this->link(readlink("{$source}{$entry}"), "{$target}{$entry}");
                } else {
                    if (is_dir("{$source}{$entry}")) {
                        $this->copyDir("{$source}{$entry}", "{$target}{$entry}");
                    } else {
                        if (is_file("{$source}{$entry}")) {
                            $this->copyFile("{$source}{$entry}", "{$target}{$entry}", 0777);
                        }
                    }
                }
            }

            $dir_handle->close();
        }
    }

    /**
     * Remove a directory
     *
     * @param  string $path
     * @param  array  $exclude
     * @throws InvalidArgumentException
     */
    public function emptyDir($path = '/', array $exclude = [])
    {
        $dir_path = $this->getFullPath($path);

        if (is_dir($dir_path)) {
            if (count($exclude)) {
                foreach ($exclude as $k => $v) {
                    $exclude[ $k ] = $this->getFullPath($v);
                }
            }

            $this->deleteDirByFullPath($dir_path, false, $exclude);
        } else {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * Remove a file
     *
     * @param string $path
     */
    public function delete($path = '/')
    {
        $full_path = $this->getFullPath($path);

        if (is_link($full_path) && is_file(readlink($full_path))) {
            unlink($full_path);
        } else {
            if (!is_link($full_path) && is_file($full_path)) {
                unlink($full_path);
            } else {
                throw new InvalidArgumentException('$path is not a file (or link to a file)');
            }
        }
    }

    /**
     * Remove a directory
     *
     * @param  string $path
     * @throws InvalidArgumentException
     */
    public function deleteDir($path = '/')
    {
        $dir_path = $this->getFullPath($path);

        if (is_dir($dir_path)) {
            $this->deleteDirByFullPath($dir_path);
        } else {
            throw new InvalidArgumentException('$path is not a directory');
        }
    }

    /**
     * Delete directory by full path
     *
     * @param string  $path
     * @param boolean $delete_self
     * @param array   $exclude
     */
    private function deleteDirByFullPath($path, $delete_self = true, array $exclude = [])
    {
        $dir = $this->withSlash($path);

        if ($dh = opendir($dir)) {
            while ($file = readdir($dh)) {
                if (($file != '.') && ($file != '..')) {
                    $fullpath = $dir . $file;

                    if (!$delete_self && in_array($fullpath, $exclude)) {
                        continue;
                    }

                    if (is_link($fullpath)) {
                        unlink($fullpath);
                    } else {
                        if (is_dir($fullpath)) {
                            $this->deleteDirByFullPath($fullpath);
                        } else {
                            unlink($fullpath);
                        }
                    }
                }
            }

            closedir($dh);
        }

        if ($delete_self) {
            rmdir($dir);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function changePermissions($path, $mode = 0777)
    {
        $path = $this->getFullPath($path);
        return chmod($path, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function isDir($path = '/')
    {
        $path = $this->getFullPath($path);
        return is_dir($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isFile($path = '/')
    {
        $path = $this->getFullPath($path);
        return is_file($path);
    }

    /**
     * {@inheritdoc}
     */
    public function isLink($path = '/')
    {
        $path = $this->getFullPath($path);
        return is_link($path);
    }
}
