<?php
  namespace ActiveCollab\Filesystem;

  use ActiveCollab\Filesystem\Adapter\Adapter;

  /**
   * @package ActiveCollab\Filesystem
   */
  class Filesystem implements Adapter
  {
    /**
     * @var \ActiveCollab\Filesystem\Adapter\Adapter
     */
    private $adapter;

    /**
     * Constructor
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
      $this->adapter = $adapter;
    }

    /**
     * Return user Adapter instance
     *
     * @return \ActiveCollab\Filesystem\Adapter\Adapter
     */
    public function &getAdapter()
    {
      return $this->adapter;
    }

    /**
     * List all files that are in the given path
     *
     * @param  string  $path
     * @param  boolean $include_hidden
     * @return array
     */
    function files($path = '/', $include_hidden = true)
    {
      return $this->adapter->files($path, $include_hidden);
    }

    /**
     * List all subdirs that are in the given path
     *
     * @param  string $path
     * @return array
     */
    public function subdirs($path = '/')
    {
      return $this->adapter->subdirs($path);
    }

    /**
     * Create a link between $source and $target
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string $source
     * @param string $target
     */
    public function link($source, $target)
    {
      $this->adapter->link($source, $target);
    }

    /**
     * Create a new file with the given data and optionally chmod it
     *
     * @param string       $path
     * @param string       $data
     * @param integer|null $mode
     */
    public function createFile($path, $data, $mode = null)
    {
      $this->adapter->createFile($path, $data, $mode);
    }

    /**
     * Write to a file. If file does not exist it will be created
     *
     * @param  string       $path
     * @param  string       $data
     * @param  integer|null $mode
     */
    public function writeFile($path, $data, $mode = null)
    {
      $this->adapter->writeFile($path, $data, $mode);
    }

    /**
     * Replace values in a text file
     *
     * @param string $path
     * @param array  $search_and_replace
     */
    public function replaceInFile($path, array $search_and_replace)
    {
      $this->adapter->replaceInFile($path, $search_and_replace);
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
      $this->adapter->copyFile($source, $target, $mode);
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
      return $this->adapter->createDir($path, $mode, $recursive);
    }

    /**
     * Copy a directory content from $source to $target
     *
     * Note: Source needs to be absolute path, not relative to sanbox
     *
     * @param string     $source
     * @param string     $target
     * @param bool|false $empty_target
     */
    public function copyDir($source, $target, $empty_target = false)
    {
      $this->adapter->copyDir($source, $target, $empty_target);
    }

    /**
     * Remove a directory
     *
     * @param string $path
     * @param array  $exclude
     */
    public function emptyDir($path = '/', array $exclude = [])
    {
      $this->adapter->emptyDir($path, $exclude);
    }

    /**
     * Remove a file
     *
     * @param string $path
     */
    public function delete($path = '/')
    {
      return $this->adapter->delete($path);
    }

    /**
     * Remove a directory
     *
     * @param string $path
     */
    public function deleteDir($path = '/')
    {
      $this->adapter->deleteDir($path);
    }

    /**
     * Return full path from sanbox path and $path
     *
     * @param  string $path
     * @return string
     */
    public function getFullPath($path = '/')
    {
      return $this->adapter->getFullPath($path);
    }
  }