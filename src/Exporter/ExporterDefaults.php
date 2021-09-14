<?php
namespace App\Exporter;

/**
 * Default values for the exporter
 */
class ExporterDefaults
{
    /**
     * The public path to where exports are stored
     *
     * @var string
     */
    public const PUBLIC_PATH = '/files/exports/';
    /**
     * The prefix used for all file archives
     *
     * @var string
     */
    public const FILE_PREFIX = 'export';
    /**
     * A date suffix appended to all file archives.
     *
     * @var string
     * @link https://www.php.net/manual/en/datetime.format.php
     */
    public const FILE_DATE_SUFFIX = 'm-d-Y-H-i';
}
