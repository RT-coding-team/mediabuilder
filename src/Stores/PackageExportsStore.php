<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\PackageExport;
use Webmozart\PathUtil\Path;

/**
 * A file based store for package exports
 */
class PackageExportsStore
{
    /**
     * The absolute directory where app packages are stored.
     *
     * @var string
     */
    private $absoluteDir = '';

    /**
     * The date format used for files.
     *
     * @var string
     */
    private $fileDateFormat = '';

    /**
     * The packages store
     *
     * @var PackagesStore
     */
    private $packagesStore = '';

    /**
     * The path relative to the public directory
     *
     * @var string
     */
    private $relativeToPublicDir = '';

    public function __construct(
        string $absoluteDir,
        string $fileDateFormat,
        PackagesStore $packagesStore,
        string $relativeToPublicDir
    ) {
        $this->absoluteDir = $absoluteDir;
        $this->fileDateFormat = $fileDateFormat;
        $this->packagesStore = $packagesStore;
        $this->relativeToPublicDir = $relativeToPublicDir;
    }

    /**
     * Find all packaged exports
     *
     * @return array The packaged exports
     */
    public function findAll(): array
    {
        if (! file_exists($this->absoluteDir)) {
            return [];
        }
        $exports = [];
        foreach (glob($this->absoluteDir.'/*.zip') as $filename) {
            $pieces = explode('_', basename($filename, '.zip'));
            $isSlim = false;
            if (2 === \count($pieces)) {
                $package = $this->packagesStore->findBySlug($pieces[0]);
                $exportedOn = \DateTime::createFromFormat($this->fileDateFormat, $pieces[1]);
            } elseif (3 === \count($pieces)) {
                $package = $this->packagesStore->findBySlug($pieces[1]);
                $exportedOn = \DateTime::createFromFormat($this->fileDateFormat, $pieces[2]);
                $isSlim = true;
            } else {
                continue;
            }
            $exports[] = new PackageExport(
                $filename,
                $exportedOn,
                $isSlim,
                $package,
                Path::join($this->relativeToPublicDir, basename($filename))
            );
        }

        return $exports;
    }

    /**
     * Get the filename for the package.
     *
     * @param string $packageSlug The package in slug format
     * @param string $fileDateFormat The preferred date format
     * @param bool $isSlim is this a slim package?
     *
     * @return string The package filename
     */
    public static function getFilename(string $packageSlug, string $fileDateFormat, bool $isSlim = false): string
    {
        $today = new \DateTime();
        $filename = '';
        if ($isSlim) {
            $filename = 'slim_';
        }
        $filename .= $packageSlug.'_'.$today->format($fileDateFormat).'.zip';

        return $filename;
    }
}
