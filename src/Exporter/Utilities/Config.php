<?php
namespace App\Exporter\Utilities;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
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
        $configFile = Path::canonicalize(dirname(__DIR__, 3) . '/config/bolt/exporter.yaml');
        if (!file_exists($configFile)) {
            throw new \InvalidArgumentException('The configuration file does not exist at ' . $configFile);
        }
        try {
            $this->config = Yaml::parseFile($configFile);
        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }
    }

    /**
     * Get the configuration using a path (ie. exporter/file_prefix)
     * @param  string $path The path to traverse
     * @return mixed        The value
     */
    public function get($path)
    {
        $pieces = explode('/', $path);
        $content = $this->config;
        foreach ($pieces as $piece) {
            if (isset($content[$piece])) {
                $content = $content[$piece];
            } else {
                $content = null;
            }
        }
        return $content;
    }
}
