<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\Package;
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
     * Delete all files associated to a slug
     *
     * @param string $slug The package slug
     *
     * @return bool Did it remove the files?
     */
    public function destroy(string $slug): bool
    {
        $exports = $this->findBySlug($slug);
        foreach ($exports as $export) {
            unlink($export->absolutePath);
        }

        return empty($this->findBySlug($slug));
    }

    /**
     * Delete all the exports
     *
     * @return bool Did it remove all the files?
     */
    public function destroyAll(): bool
    {
        $exports = $this->findAll();
        foreach ($exports as $export) {
            unlink($export->absolutePath);
        }

        return empty($this->findAll());
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
            $export = $this->buildExport($filename);
            if ($export) {
                $exports[] = $export;
            }
        }

        return $exports;
    }

    /**
     * Find based on the provided slug
     *
     * @param string $slug The slug of the package
     *
     * @return array The packaged exports
     */
    public function findBySlug(string $slug): array
    {
        if (! file_exists($this->absoluteDir)) {
            return [];
        }
        $exports = [];
        foreach ($this->findAll() as $export) {
            if ($export->package->slug === $slug) {
                $exports[] = $export;
            }
        }

        return $exports;
    }

    /**
     * Update the slug to match the new slug
     *
     * @param string $old The old slug
     * @param string $new The new slug
     */
    public function updateSlug(string $old, string $new): void
    {
        $exports = $this->findBySlug($old);
        foreach ($exports as $export) {
            $newFilename = self::getFilename($new, $this->fileDateFormat, $export->isSlim);
            $newPath = Path::join($this->absoluteDir, $newFilename);
            rename($export->absolutePath, $newPath);
        }
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

    /**
     * Build the package export
     *
     * @param string $filename The absolute path to the file
     *
     * @return PackageExport The export package
     */
    private function buildExport(string $filename): ?PackageExport
    {
        $pieces = explode('_', basename($filename, '.zip'));
        $isSlim = false;
        if (2 === \count($pieces)) {
            $slug = $pieces[0];
            $exportedOn = \DateTime::createFromFormat($this->fileDateFormat, $pieces[1]);
        } elseif (3 === \count($pieces)) {
            $slug = $pieces[1];
            $exportedOn = \DateTime::createFromFormat($this->fileDateFormat, $pieces[2]);
            $isSlim = true;
        } else {
            return null;
        }
        $package = $this->packagesStore->findBySlug($slug);
        if (empty($package)) {
            $package = new Package($slug, $slug);
        }

        return new PackageExport(
            $filename,
            $exportedOn,
            $isSlim,
            $package,
            Path::join($this->relativeToPublicDir, basename($filename))
        );
    }
}
