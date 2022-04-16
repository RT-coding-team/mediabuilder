<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Stores\PackagesStore;
use Webmozart\PathUtil\Path;

/**
 * A class for retrieving a list of export media in the exports directory.
 */
class ExportMedia
{
    /**
     * Our package store
     *
     * @var PackagesStore
     */
    private $packagesStore = null;

    /**
     * Build the class
     *
     * @param PackagesStore $packagesStore Our packages store
     */
    public function __construct(
        PackagesStore $packagesStore
    ) {
        $this->packagesStore = $packagesStore;
    }

    /**
     * Gets an array of files keyed to the slug.
     *
     * @param string $exportAbsolutePath The absolute path to the directory where exports are stored
     * @param string $exportRelativePath The relative path (in public) to the directory where exports are stored
     * @param string $dateFormatSuffix The date format suffix for files
     *
     * @return array The files
     */
    public function get(string $exportAbsolutePath, string $exportRelativePath, string $dateFormatSuffix): array
    {
        $files = [];
        if (! file_exists($exportAbsolutePath)) {
            return $files;
        }
        foreach (glob($exportAbsolutePath.'/*.zip') as $filename) {
            $pieces = explode('_', basename($filename, '.zip'));
            $isSlim = false;
            $slug = '';
            if (2 === \count($pieces)) {
                $slug = $pieces[0];
                $package = $this->packagesStore->findBySlug($slug);
                $date = \DateTime::createFromFormat($dateFormatSuffix, $pieces[1]);
            } elseif (3 === \count($pieces)) {
                $slug = $pieces[1];
                $package = $this->packagesStore->findBySlug($slug);
                $date = \DateTime::createFromFormat($dateFormatSuffix, $pieces[2]);
                $isSlim = true;
            } else {
                continue;
            }
            $packageName = $package ? $package->name : '';
            if (! \array_key_exists($slug, $files)) {
                $files[$slug] = [];
            }
            $files[$slug][] = [
                'date' => $date->format('M j, Y g:i A'),
                'filename' => basename($filename),
                'filepath' => Path::join($exportRelativePath, basename($filename)),
                'is_slim' => $isSlim,
                'package' => $packageName,
                'timestamp' => $date->getTimestamp(),
            ];
        }
        $sorted = [];
        foreach ($files as $key => $val) {
            uasort($val, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });
            $sorted[$key] = $val;
        }
        ksort($sorted);

        return $sorted;
    }
}
