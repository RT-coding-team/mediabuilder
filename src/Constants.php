<?php

declare(strict_types=1);

namespace App;

use Webmozart\PathUtil\Path;

/**
 * Reusable constants
 */
class Constants
{
    /**
     * A date suffix appended to all file archives.
     *
     * @var string
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    public const DEFAULT_FILE_DATE_FORMAT = 'm-d-Y-H-i';

    /**
     * The default supported languages
     *
     * @var array
     */
    public const DEFAULT_SUPPORTED_LANGUAGES = [
        [
            'text' => 'English',
            'bolt_locale_code' => 'en',
            'codes' => ['en-US', 'en'],
            'default' => true,
        ],
    ];

    /**
     * The required permission to access the exporter functionality
     *
     * @var string
     */
    public const EXPORTER_REQUIRED_PERMISSION = 'managefiles:config';

    /**
     * The public path to where exports are stored
     *
     * @var string
     */
    public const EXPORTS_PUBLIC_PATH = '/files/exports/';

    /**
     * The required permission to access the package manager
     *
     * @var string
     */
    public const PACKAGE_MANAGER_REQUIRED_PERMISSION = 'ROLE_EDITOR';
}
