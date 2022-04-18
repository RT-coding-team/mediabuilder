<?php

declare(strict_types=1);

namespace App\Utilities;

/**
 * Recursively zip a directory. To use:
 * ExtendedZip::zipTree('/foo/bar', '/tmp/archive.zip', ZipArchive::CREATE);
 *
 * @see https://stackoverflow.com/a/21044047/4638563
 */
class ExtendedZip extends \ZipArchive
{
    /**
     * Member function to add a whole file system subtree to the archive
     *
     * @param string $dirname The directory to compress
     * @param string $localname A local directory you want to store it at
     */
    public function addTree(string $dirname, string $localname = ''): void
    {
        if ($localname) {
            $this->addEmptyDir($localname);
        }
        $this->_addTree($dirname, $localname);
    }

    /**
     * Internal function, to recurse
     *
     * @param string $dirname The directory to compress
     * @param string $localname A local directory you want to store it at
     */
    protected function _addTree($dirname, $localname): void
    {
        $dir = opendir($dirname);
        while ($filename = readdir($dir)) {
            // Discard . and ..
            if ('.' === $filename || '..' === $filename) {
                continue;
            }
            // Proceed according to type
            $path = $dirname.'/'.$filename;
            $localpath = $localname ? ($localname.'/'.$filename) : $filename;
            if (is_dir($path)) {
                // Directory: add & recurse
                $this->addEmptyDir($localpath);
                $this->_addTree($path, $localpath);
            } elseif (is_file($path)) {
                // File: just add
                $this->addFile($path, $localpath);
            }
        }
        closedir($dir);
    }

    /**
     * Helper function
     *
     * @param string $dirname The directory to compress
     * @param string $zipFilename The final file name
     * @param int $flags ZipArchive flags (default: 0)
     * @param string $localname A local directory you want to store it at (default: '')
     */
    public static function zipTree(
        string $dirname,
        string $zipFilename,
        $flags = 0,
        $localname = ''
    ): void {
        $zip = new self();
        $zip->open($zipFilename, $flags);
        $zip->addTree($dirname, $localname);
        $zip->close();
    }
}
