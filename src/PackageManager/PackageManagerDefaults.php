<?php

declare(strict_types=1);

namespace App\PackageManager;

/**
 * Default values for the package manager
 */
class PackageManagerDefaults
{
    /**
     * The required permission to access this extension
     *
     * @var string
     */
    public const REQUIRED_PERMISSION = 'ROLE_EDITOR';
}
