<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Constants;
use App\Models\Collection;
use App\Models\Language;
use App\Models\Package;
use App\Models\Single;
use App\Stores\PackageExportsStore;
use Webmozart\PathUtil\Path;

/**
 * Exports a package of content
 */
class PackageExporter
{
    /**
     * Our library for retrieving configuration settings.
     *
     * @var Config
     */
    private $config = null;

    /**
     * The current locale
     *
     * @var string
     */
    private $currentLocale = 'en';

    /**
     * The directories we use for exporting
     *
     * @var array
     */
    private $directories = [
        'export_root' => '',
        'locale_root' => '',
        'export_data' => '',
        'export_images' => '',
        'export_media' => '',
    ];

    /**
     * The directory where exports are stored.
     *
     * @var string
     */
    private $exportsDir = '';

    /**
     * Our file logger
     *
     * @var FileLogger
     */
    private $fileLogger = null;

    /**
     * Do we want to make a slim package? A slim package includes URLs for all
     * the resources instead of adding them into the package.
     *
     * @var bool
     */
    private $isSlim = false;

    /**
     * The main data for main.json
     *
     * @var array
     */
    private $mainData = [];

    /**
     * The language data saved to the languages.json
     *
     * @var array
     */
    private $languageData = [];

    /**
     * An array of locales provided by the user
     *
     * @var array
     */
    private $providedLocales = [];

    /**
     * The path to the site logo file.
     *
     * @var string
     */
    private $siteLogoPath = '';

    /**
     * An array of supported languages.
     *
     * @var array
     */
    private $supportedLanguages = [];

    /**
     * Build the exporter
     *
     * @param string $publicPath The path to the public directory
     * @param string $exportsDir The exports directory
     * @param App\Exporter\Utilities\Config $config The configuration class
     * @param App\Exporter\Utilities\FileLogger $fileLogger The logger class
     */
    public function __construct(
        string $publicPath,
        string $exportsDir,
        Config $config,
        FileLogger $fileLogger
    ) {
        if (! file_exists($exportsDir)) {
            throw new \InvalidArgumentException('The exports directory does not exist!');
        }
        $this->exportsDir = $exportsDir;
        $this->config = $config;
        $this->fileLogger = $fileLogger;
        $logoPath = $this->config->get('exporter/logo_public_path');
        if ($logoPath) {
            $logo = Path::canonicalize($publicPath.$logoPath);
            if (file_exists($logo)) {
                $this->siteLogoPath = $logo;
            }
        }
        $this->supportedLanguages = $this->config->get(
            'exporter/supported_languages',
            Constants::DEFAULT_SUPPORTED_LANGUAGES
        );
    }

    /**
     * export the provided package
     *
     * @param Package $package The package to export
     * @param bool $isSlim do you want to slim down the package? Media files
     * are removed and download URLs are added
     */
    public function export(
        Package $package,
        bool $isSlim = false
    ): void {
        $this->isSlim = $isSlim;
        $this->start($package->name, $package->slug);
        foreach ($this->supportedLanguages as $lang) {
            if (! $package->hasContentForLocale($lang['bolt_locale_code'])) {
                // We have no content for this locale so move along.
                continue;
            }
            $language = new Language(
                $lang['codes'],
                $lang['text'],
                (bool) $lang['default']
            );
            $interface = $this->config->get('exporter/interface/'.$lang['bolt_locale_code']);
            if (! $interface) {
                $interface = $this->config->get('exporter/interface/en');
            }
            $this->startLocale($lang['bolt_locale_code'], $interface);
            $this->addLanguage($language);

            $collections = $package->getCollectionsByLocale($lang['bolt_locale_code']);
            foreach ($collections as $collection) {
                $this->addCollection($collection);
            }

            $singles = $package->getSinglesByLocale($lang['bolt_locale_code']);
            foreach ($singles as $single) {
                $this->addSingle($single);
            }

            $this->finishLocale();
        }
        $this->finish();
    }

    /**
     * Start the export process.
     *
     * @param string $packageName The package name for this export
     * @param string $packageSlug The slug of the package
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    private function start(
        string $packageName,
        string $packageSlug
    ): void {
        $this->log('Export started!');
        $this->mainData = [
            'itemName' => $packageName,
            'content' => [],
        ];
        $this->languageData = [];
        $dateFormat = $this->config->get(
            'exporter/file_date_suffix',
            Constants::DEFAULT_FILE_DATE_FORMAT
        );
        $exportFilename = PackageExportsStore::getFilename($packageSlug, $dateFormat, $this->isSlim);
        $this->directories['export_root'] = Path::join($this->exportsDir, basename($exportFilename, '.zip'));
        if (! file_exists($this->directories['export_root'])) {
            mkdir($this->directories['export_root'], 0777, true);
        }
        if ('' !== $this->siteLogoPath) {
            copy(
                $this->siteLogoPath,
                Path::join(
                    $this->directories['export_root'],
                    basename($this->siteLogoPath)
                )
            );
        }
        $this->log('Setup complete.');
    }

    /**
     * Start a new locale which sets up the required folder structure
     *
     * @param string $locale The locale
     * @param array $interface The data stored in the interface file
     */
    private function startLocale(
        $locale = 'en',
        $interface = []
    ): void {
        $this->log('Start Locale: '.$locale);
        $this->currentLocale = $locale;
        $interface['APP_LOGO'] = 'content/'.basename($this->siteLogoPath);
        if (! \in_array($this->currentLocale, $this->providedLocales, true)) {
            $this->providedLocales[] = $this->currentLocale;
        }
        $this->directories['locale_root'] = Path::join($this->directories['export_root'], $this->currentLocale);
        if (! file_exists($this->directories['locale_root'])) {
            mkdir($this->directories['locale_root'], 0777, true);
        }
        $this->directories['export_data'] = Path::join($this->directories['locale_root'], 'data');
        if (! file_exists($this->directories['export_data'])) {
            mkdir($this->directories['export_data']);
        }
        // Write the interface file
        $interfacePath = Path::join($this->directories['export_data'], 'interface.json');
        file_put_contents($interfacePath, json_encode($interface, \JSON_UNESCAPED_UNICODE));
        if ($this->isSlim) {
            // We do not need the directories
            $this->directories['export_images'] = '';
            $this->directories['export_media'] = '';
            $this->log('Locale set up.');

            return;
        }
        $this->directories['export_images'] = Path::join($this->directories['locale_root'], 'images');
        if (! file_exists($this->directories['export_images'])) {
            mkdir($this->directories['export_images']);
        }
        $this->directories['export_media'] = Path::join($this->directories['locale_root'], 'media');
        if (! file_exists($this->directories['export_media'])) {
            mkdir($this->directories['export_media']);
        }
        $this->log('Locale set up.');
    }

    /**
     * Add a collection to the export package.
     *
     * @param Collection $collection The collection to add
     */
    private function addCollection(Collection $collection): void
    {
        // Add data file
        $this->log('Adding a new collection: '.$collection->title);
        $this->log('Creating data file: '.$collection->slug.'.json');
        $dataFilePath = Path::join($this->directories['export_data'], $collection->slug.'.json');
        file_put_contents($dataFilePath, $collection->asJson(false, $this->isSlim));
        // Add to main data
        $this->mainData['content'][] = $collection->asArray(true, $this->isSlim);
        if ($this->isSlim) {
            $this->log('Collection added!');

            return;
        }
        // Add media files
        $this->log('Adding media files to package.');
        $this->log('Copying file: '.$collection->image);
        copy($collection->localImage, Path::join($this->directories['export_images'], $collection->image));
        foreach ($collection->episodes as $episode) {
            // Store episode files
            $this->log('Adding a new episode: '.$episode->title);
            $this->log('Copying file: '.$episode->image);
            copy($episode->localImage, Path::join($this->directories['export_images'], $episode->image));
            $this->log('Copying file: '.$episode->filename);
            copy($episode->localFilename, Path::join($this->directories['export_media'], $episode->filename));
        }
        $this->log('Collection added!');
    }

    /**
     * Add a supported language
     *
     * @param Language $language The language to add
     */
    private function addLanguage(Language $language): void
    {
        $exists = false;
        foreach ($this->languageData as $lang) {
            if ($lang->text === $language->text) {
                $exists = true;
            }
        }
        if ($exists) {
            return;
        }
        $this->languageData[] = $language;
    }

    /**
     * Add a Single to the export package.
     *
     * @param Single $single The single to add
     */
    private function addSingle(Single $single): void
    {
        // Add data file
        $this->log('Adding a new single: '.$single->title);
        $this->log('Creating data file: '.$single->slug.'.json');
        $dataFilePath = Path::join($this->directories['export_data'], $single->slug.'.json');
        file_put_contents($dataFilePath, $single->asJson($this->isSlim));
        $this->mainData['content'][] = $single->asArray($this->isSlim);
        if ($this->isSlim) {
            $this->log('Single added!');

            return;
        }
        // Add media files
        $this->log('Adding media files to package.');
        $this->log('Copying file: '.$single->image);
        copy($single->localImage, Path::join($this->directories['export_images'], $single->image));
        $this->log('Copying file: '.$single->filename);
        copy($single->localFilename, Path::join($this->directories['export_media'], $single->filename));
        $this->log('Single added!');
    }

    /**
     * Finish the locale
     */
    private function finishLocale(): void
    {
        $this->log('Completing the current locale: '.$this->currentLocale);
        $this->log('Creating data file: main.json');
        $mainPath = Path::join($this->directories['export_data'], 'main.json');
        file_put_contents($mainPath, json_encode($this->mainData, \JSON_UNESCAPED_UNICODE));
        $this->mainData = [];
        $this->log('Locale completed.');
    }

    /**
     * Finish up the exporting
     */
    private function finish(): void
    {
        $this->log('Completing export!');
        $this->log('Creating languages file: languages.json');
        $langPath = Path::join($this->directories['export_root'], 'languages.json');
        file_put_contents($langPath, json_encode($this->languageData, \JSON_UNESCAPED_UNICODE));
        $this->log('Zipping up the archive.');
        ExtendedZip::zipTree(
            $this->directories['export_root'],
            $this->directories['export_root'].'.zip',
            \ZipArchive::CREATE,
            'content'
        );
        if (file_exists($this->directories['export_root'].'.zip')) {
            if ($this->fileLogger) {
                $this->fileLogger->increaseCounter();
            }
            $this->log('Archive has been zipped up. Doing some clean up.');
        } else {
            $this->logError('For some reason the zip was not created: '.$this->directories['export_root'].'.zip');
        }
        //Remove our export directory
        foreach ($this->providedLocales as $locale) {
            $this->removeDirectory(Path::join($this->directories['export_root'], $locale));
        }
        $this->removeDirectory($this->directories['export_root']);
        $this->log('Completed export.');
    }

    /**
     * Log a message
     *
     * @param string $message The message to log
     * @param bool $isError Are we dealing with an error? (default: false)
     */
    private function log(string $message, $isError = false): void
    {
        if (! $this->fileLogger) {
            echo $message."\r\n";

            return;
        }
        if ($isError) {
            $this->fileLogger->logError($message);
        } else {
            $this->fileLogger->log($message);
        }
    }

    /**
     * Remove the directory recursuvely.
     *
     * @param string $directory The directory to remove
     */
    private function removeDirectory(string $directory): void
    {
        if (! file_exists($directory)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($directory);
    }
}
