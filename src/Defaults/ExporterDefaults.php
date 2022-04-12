<?php

declare(strict_types=1);

namespace App\Defaults;

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
     * A date suffix appended to all file archives.
     *
     * @var string
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    public const FILE_DATE_SUFFIX = 'm-d-Y-H-i';

    /**
     * The required permission to access this extension
     *
     * @var string
     */
    public const REQUIRED_PERMISSION = 'managefiles:config';

    /**
     * The default supported languages
     *
     * @var array
     */
    public const SUPPORTED_LANGUAGES = [
        [
            'text' => 'English',
            'bolt_locale_code' => 'en',
            'codes' => ['en-US', 'en'],
            'default' => true,
        ],
    ];
}
