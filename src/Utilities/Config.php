<?php

declare(strict_types=1);

namespace App\Utilities;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

/**
 * A class for retrieving configuration settings from exporter.yml file.
 */
class Config
{
    /**
     * The configuration settings
     *
     * @var array
     */
    public $config = [];

    /**
     * Build the class
     */
    public function __construct()
    {
        $configFile = Path::canonicalize(\dirname(__DIR__, 2).'/config/bolt/exporter.yaml');
        if (! file_exists($configFile)) {
            throw new \InvalidArgumentException('The configuration file does not exist at '.$configFile);
        }
        try {
            $this->config = Yaml::parseFile($configFile);
        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }
    }

    /**
     * Get the configuration using a path (ie. exporter/file_prefix)
     *
     * @param string $path The path to traverse
     * @param mixed $default The default value to return if it is not found (default: null)
     *
     * @return mixed The value
     */
    public function get($path, $default = null)
    {
        $pieces = explode('/', $path);
        $content = $this->config;
        foreach ($pieces as $piece) {
            if (isset($content[$piece])) {
                $content = $content[$piece];
            } else {
                $content = $default;
            }
        }

        return $content;
    }
}
