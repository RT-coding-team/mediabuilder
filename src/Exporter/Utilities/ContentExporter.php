<?php

declare(strict_types=1);

namespace App\Exporter\Utilities;

use App\Exporter\ExporterDefaults;
use App\Exporter\Models\Collection;
use App\Exporter\Models\Language;
use App\Exporter\Models\Single;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Exports the content
 */
class ContentExporter
{
    /**
     * The current locale
     *
     * @var string
     */
    private $currentLocale = 'en';

    /**
     * The directory where exports are stored.
     *
     * @var string
     */
    private $exportsDir = '';

    /**
     * The name of the file we are working on.
     *
     * @var string
     */
    private $exportFilename = '';

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
     * The path to the logo file
     *
     * @var string
     */
    private $logoPath = '';

    /**
     * An output interface to printing progress
     *
     * @var OutputInterface
     */
    private $output = null;

    /**
     * An array of locales provided by the user
     *
     * @var array
     */
    private $providedLocales = [];

    /**
     * Build the exporter
     *
     * @param string $exportsDir The exports directory
     */
    public function __construct(string $exportsDir)
    {
        if (! file_exists($exportsDir)) {
            throw new \InvalidArgumentException('The exports directory does not exist!');
        }
        $this->exportsDir = $exportsDir;
    }

    /**
     * Set the output interface to retrieve progress updates.
     *
     * @param OutputInterface $output The interface that conforms to OutputInterface
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Start the export process.
     *
     * @param string $itemName The item name for this export
     * @param string $filePrefix The name to append to the archive
     * @param string $fileDateSuffix A date format to append to the end of the archive (default: ExporterDefaults::FILE_DATE_SUFFIX)
     * @param string $logo The path to the current logo
     *
     * @see https://www.php.net/manual/en/datetime.format.php
     */
    public function start(
        string $itemName,
        string $filePrefix,
        string $fileDateSuffix = ExporterDefaults::FILE_DATE_SUFFIX,
        string $logo = ''
    ): void {
        $this->log('Export started!');
        $today = new \DateTime();
        $this->mainData = [
            'itemName' => $itemName,
            'content' => [],
        ];
        $this->languageData = [];
        $this->exportFilename = $filePrefix.'-'.$today->format($fileDateSuffix);
        $this->directories['export_root'] = Path::join($this->exportsDir, $this->exportFilename);
        if (! file_exists($this->directories['export_root'])) {
            mkdir($this->directories['export_root'], 0777, true);
        }
        if ('' !== $logo && (file_exists($logo))) {
            copy($logo, Path::join($this->directories['export_root'], basename($logo)));
            $this->logoPath = 'content/'.basename($logo);
        }
        $this->log('Setup complete.');
    }

    /**
     * Start a new locale which sets up the required folder structure
     *
     * @param string $locale The locale
     * @param array $interface The data stored in the interface file
     */
    public function startLocale($locale = 'en', $interface = []): void
    {
        $this->log('Start Locale: '.$locale);
        $this->currentLocale = $locale;
        $interface['APP_LOGO'] = $this->logoPath;
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
    public function addCollection(Collection $collection): void
    {
        // Add data file
        $this->log('Adding a new collection: '.$collection->title);
        $clone = clone $collection;
        unset($clone->localImage);
        if (! $clone->recommended) {
            unset($clone->recommended);
        }
        foreach ($clone->episodes as $episode) {
            // Store episode files
            $this->log('Adding a new episode: '.$episode->title);
            $this->log('Copying file: '.$episode->image);
            copy($episode->localImage, Path::join($this->directories['export_images'], $episode->image));
            $this->log('Copying file: '.$episode->filename);
            copy($episode->localFilename, Path::join($this->directories['export_media'], $episode->filename));
            unset($episode->localImage);
            unset($episode->localFilename);
        }
        $this->log('Creating data file: '.$clone->slug.'.json');
        $dataFilePath = Path::join($this->directories['export_data'], $clone->slug.'.json');
        file_put_contents($dataFilePath, json_encode($clone, \JSON_UNESCAPED_UNICODE));
        // Store files
        $this->log('Copying file: '.$collection->image);
        copy($collection->localImage, Path::join($this->directories['export_images'], $collection->image));
        // Add to main data
        $mainClone = clone $collection;
        unset($mainClone->localImage);
        unset($mainClone->episodes);
        if (! $mainClone->recommended) {
            unset($mainClone->recommended);
        }
        $this->mainData['content'][] = $mainClone;
        $this->log('Collection added!');
    }

    /**
     * Add a supported language
     *
     * @param Language $language The language to add
     */
    public function addLanguage(Language $language): void
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
    public function addSingle(Single $single): void
    {
        // Add data file
        $this->log('Adding a new single: '.$single->title);
        $clone = clone $single;
        unset($clone->localImage);
        unset($clone->localFilename);
        if (! $clone->recommended) {
            unset($clone->recommended);
        }
        $this->log('Creating data file: '.$clone->slug.'.json');
        $dataFilePath = Path::join($this->directories['export_data'], $clone->slug.'.json');
        file_put_contents($dataFilePath, json_encode($clone, \JSON_UNESCAPED_UNICODE));
        // Store files
        $this->log('Copying file: '.$single->image);
        copy($single->localImage, Path::join($this->directories['export_images'], $single->image));
        $this->log('Copying file: '.$single->filename);
        copy($single->localFilename, Path::join($this->directories['export_media'], $single->filename));
        // Add to main data
        $this->mainData['content'][] = $clone;
        $this->log('Single added!');
    }

    /**
     * Finish the locale
     */
    public function finishLocale(): void
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
    public function finish(): void
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
        $this->log('Archive has been zipped up. Doing some clean up.');
        //Remove our export directory
        foreach ($this->providedLocales as $locale) {
            $this->removeDirectory(Path::join($this->directories['export_root'], $locale));
        }
        $this->removeDirectory($this->directories['export_root']);
        $this->log('Done!');
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

    /**
     * Log a message
     *
     * @param string $message The message to log
     */
    private function log(string $message): void
    {
        if (! $this->output) {
            echo $message."\r\n";

            return;
        }
        $this->output->writeln($message);
    }
}
