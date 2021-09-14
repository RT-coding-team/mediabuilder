<?php
namespace App\Exporter\Utilities;

use App\Exporter\ExporterDefaults;
use App\Exporter\Models\Collection;
use App\Exporter\Models\Single;
use App\Exporter\Utilities\ExtendedZip;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Exports the content
 */
class ContentExporter
{

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
     * @access private
     */
    private $exportFilename = '';

    /**
     * The directories we use for exporting
     *
     * @var array
     * @access private
     */
    private $directories = [
        'export_root'   =>  '',
        'locale_root'   =>  '',
        'export_data'   =>  '',
        'export_images' =>  '',
        'export_media'  =>  '',
    ];

    /**
     * The main data for main.json
     *
     * @var array
     * @access private
     */
    private $mainData = [];

    /**
     * An output interface to printing progress
     *
     * @var OutputInterface
     */
    private $output = null;

    public function __construct(string $exportsDir)
    {
        if (!file_exists($exportsDir)) {
            throw new \InvalidArgumentException('The exports directory does not exist!');
        }
        $this->exportsDir = $exportsDir;
    }

    /**
     * Set the output interface to retrieve progress updates.
     *
     * @param OutputInterface $output The interface that conforms to OutputInterface
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Start the export process.
     *
     * @param   string  $locale             The locale we are working with (default: en)
     * @param   string  $filePrefix         The name to append to the archive (default: ExporterDefaults::FILE_PREFIX)
     * @param   string  $fileDateSuffix     A date format to append to the end of the archive (default: ExporterDefaults::FILE_DATE_SUFFIX)
     * @return void
     *
     * @link https://www.php.net/manual/en/datetime.format.php
     */
    public function start(
        string $locale = 'en',
        string $filePrefix = ExporterDefaults::FILE_PREFIX,
        string $fileDateSuffix = ExporterDefaults::FILE_DATE_SUFFIX
    )
    {
        $this->log('Export started!');
        $today = new \DateTime();
        $this->mainData = [
            'itemName'  =>  'Exported Data',
            'content'   =>  []
        ];
        $this->log('Setting up the directories.');
        $this->exportFilename = $filePrefix . '-' . $today->format($fileDateSuffix);
        $this->directories['export_root'] = Path::join($this->exportsDir, $this->exportFilename);
        $this->directories['locale_root'] = Path::join($this->directories['export_root'], $locale);
        if (!file_exists($this->directories['export_root'])) {
            mkdir($this->directories['export_root'], 0777, true);
        }
        // Set up the directories
        $this->directories['export_data'] = Path::join($this->directories['export_root'], 'data');
        if (!file_exists($this->directories['export_data'])) {
            mkdir($this->directories['export_data']);
        }
        $this->directories['export_images'] = Path::join($this->directories['export_root'], 'images');
        if (!file_exists($this->directories['export_images'])) {
            mkdir($this->directories['export_images']);
        }
        $this->directories['export_media'] = Path::join($this->directories['export_root'], 'media');
        if (!file_exists($this->directories['export_media'])) {
            mkdir($this->directories['export_media']);
        }
        $this->log('Setup complete.');
    }

    /**
     * Add a collection to the export package.
     *
     * @param Collection $collection The collection to add
     */
    public function addCollection(Collection $collection)
    {
        // Add data file
        $this->log('Adding a new collection: ' . $collection->title);
        $clone = clone $collection;
        unset($clone->localImage);
        if (!$clone->recommended) {
            unset($clone->recommended);
        }
        foreach ($clone->episodes as $episode) {
            // Store episode files
            $this->log('Adding a new episode: ' . $episode->title);
            $this->log('Copying file: ' . $episode->image);
            copy($episode->localImage, Path::join($this->directories['export_images'], $episode->image));
            $this->log('Copying file: ' . $episode->filename);
            copy($episode->localFilename, Path::join($this->directories['export_media'], $episode->filename));
            unset($episode->localImage);
            unset($episode->localFilename);
        }
        $this->log('Creating data file: ' . $clone->slug . '.json');
        $dataFilePath = Path::join($this->directories['export_data'], $clone->slug . '.json');
        file_put_contents($dataFilePath, json_encode($clone));
        // Store files
        $this->log('Copying file: ' . $collection->image);
        copy($collection->localImage, Path::join($this->directories['export_images'], $collection->image));
        // Add to main data
        $mainClone = clone $collection;
        unset($mainClone->localImage);
        unset($mainClone->episodes);
        if (!$mainClone->recommended) {
            unset($mainClone->recommended);
        }
        $this->mainData['content'][] = $mainClone;
        $this->log('Collection added!');
    }

    /**
     * Add a Single to the export package.
     *
     * @param Single $single The single to add
     */
    public function addSingle(Single $single)
    {
        // Add data file
        $this->log('Adding a new single: ' . $single->title);
        $clone = clone $single;
        unset($clone->localImage);
        unset($clone->localFilename);
        if (!$clone->recommended) {
            unset($clone->recommended);
        }
        $this->log('Creating data file: ' . $clone->slug . '.json');
        $dataFilePath = Path::join($this->directories['export_data'], $clone->slug . '.json');
        file_put_contents($dataFilePath, json_encode($clone));
        // Store files
        $this->log('Copying file: ' . $single->image);
        copy($single->localImage, Path::join($this->directories['export_images'], $single->image));
        $this->log('Copying file: ' . $single->filename);
        copy($single->localFilename, Path::join($this->directories['export_media'], $single->filename));
        // Add to main data
        $this->mainData['content'][] = $clone;
        $this->log('Single added!');
    }

    /**
     * Finish up the exporting
     *
     * @return void
     */
    public function finish()
    {
        $this->log('Completing export!');
        $this->log('Creating data file: main.json');
        $mainPath = Path::join($this->directories['export_data'], 'main.json');
        file_put_contents($mainPath, json_encode($this->mainData));
        $this->log('Zipping up the archive.');
        ExtendedZip::zipTree(
            $this->directories['export_root'],
            $this->directories['export_root'] . '.zip',
            \ZipArchive::CREATE,
            'content'
        );
        $this->log('Archive has been zipped up. Doing some clean up.');
        //Remove our export directory
        $this->removeExportRoot();
        $this->log('Done!');
    }

    /**
     * Remove the export_root directory.
     *
     * @return  void
     * @access  private
     */
    private function removeExportRoot()
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->directories['export_root'],
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($this->directories['export_root']);
    }

    /**
     * Log a message
     *
     * @param  string $message The message to log
     * @return void
     * @access private
     */
    private function log(string $message)
    {
        if (!$this->output) {
            echo $message . "\r\n";
            return;
        }
        $this->output->writeln($message);
    }
}
