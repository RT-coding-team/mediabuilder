<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\PackageExport;

/**
 * A file based store for package exports
 */
class PackageExportsStore
{
    /**
     * Get the filename for the package.
     *
     * @param string $packageSlug The package in slug format
     * @param string $dateFormat The preferred date format
     * @param bool $isSlim is this a slim package?
     *
     * @return string The package filename
     */
    public static function getFilename(string $packageSlug, string $dateFormat, bool $isSlim = false): string
    {
        $today = new \DateTime();
        $filename = '';
        if ($isSlim) {
            $filename = 'slim_';
        }
        $filename .= $packageSlug.'_'.$today->format($dateFormat).'.zip';

        return $filename;
    }
}
