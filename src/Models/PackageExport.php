<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A single export package
 */
class PackageExport
{
    /**
     * The absolute path to the file
     *
     * @var string
     */
    public $absolutePath = '';

    /**
     * The date the export was made
     *
     * @var string
     */
    public $exportDate = '';

    /**
     * The timestamp the export was made
     *
     * @var string
     */
    public $exportTimestamp = '';

    /**
     * The file name
     *
     * @var string
     */
    public $filename = '';

    /**
     * Is it a slim package?
     *
     * @var bool
     */
    public $isSlim = false;

    /**
     * The package of the export
     *
     * @var string
     */
    public $package = '';

    /**
     * The path in the public Directory
     *
     * @var string
     */
    public $publicPath = '';

    /**
     * Build the Model
     *
     * @param string $absolutePath The absolute path to the file
     * @param DateTime $exportedOn The date it was exported
     * @param Package $package The package the export belongs to
     * @param string $publicPath The path to the file in the public directory
     */
    public function __construct(
        string $absolutePath,
        \DateTime $exportedOn,
        bool $isSlim,
        Package $package,
        string $publicPath
    ) {
        $this->absolutePath = $absolutePath;
        $this->exportDate = $exportedOn->format('M j, Y g:i A');
        $this->exportTimestamp = $exportedOn->getTimestamp();
        $this->isSlim = $isSlim;
        $this->filename = basename($this->absolutePath);
        $this->package = $package;
        $this->publicPath = $publicPath;
    }
}
