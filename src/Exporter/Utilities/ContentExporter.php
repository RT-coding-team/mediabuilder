<?php
namespace App\Exporter\Utilities;

use App\Exporter\ExporterDefaults;
use App\Exporter\Models\Collection;
use App\Exporter\Models\Single;
use App\Exporter\Utilities\ExtendedZip;
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

    public function __construct(string $exportsDir)
    {
        if (!file_exists($exportsDir)) {
            throw new \InvalidArgumentException('The exports directory does not exist!');
        }
        $this->exportsDir = $exportsDir;
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
        $today = new \DateTime();
        $this->mainData = [
            'itemName'  =>  'Exported Data',
            'content'   =>  []
        ];
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
    }

    /**
     * Add a collection to the export package.
     *
     * @param Collection $collection The collection to add
     */
    public function addCollection(Collection $collection)
    {
        // Add data file
        $clone = clone $collection;
        unset($clone->localImage);
        if (!$clone->recommended) {
            unset($clone->recommended);
        }
        foreach ($clone->episodes as $episode) {
            // Store episode files
            copy($episode->localImage, Path::join($this->directories['export_images'], $episode->image));
            copy($episode->localFilename, Path::join($this->directories['export_media'], $episode->filename));
            unset($episode->localImage);
            unset($episode->localFilename);
        }
        $dataFilePath = Path::join($this->directories['export_data'], $clone->slug . '.json');
        file_put_contents($dataFilePath, json_encode($clone));
        // Store files
        copy($collection->localImage, Path::join($this->directories['export_images'], $collection->image));
        // Add to main data
        $mainClone = clone $collection;
        unset($mainClone->localImage);
        unset($mainClone->episodes);
        if (!$mainClone->recommended) {
            unset($mainClone->recommended);
        }
        $this->mainData['content'][] = $mainClone;
    }

    /**
     * Add a Single to the export package.
     *
     * @param Single $single The single to add
     */
    public function addSingle(Single $single)
    {
        // Add data file
        $clone = clone $single;
        unset($clone->localImage);
        unset($clone->localFilename);
        if (!$clone->recommended) {
            unset($clone->recommended);
        }
        $dataFilePath = Path::join($this->directories['export_data'], $clone->slug . '.json');
        file_put_contents($dataFilePath, json_encode($clone));
        // Store files
        copy($single->localImage, Path::join($this->directories['export_images'], $single->image));
        copy($single->localFilename, Path::join($this->directories['export_media'], $single->filename));
        // Add to main data
        $this->mainData['content'][] = $clone;
    }

    /**
     * Finish up the exporting
     *
     * @return void
     */
    public function finish()
    {
        $mainPath = Path::join($this->directories['export_data'], 'main.json');
        file_put_contents($mainPath, json_encode($this->mainData));
        ExtendedZip::zipTree(
            $this->directories['export_root'],
            $this->directories['export_root'] . '.zip',
            \ZipArchive::CREATE,
            'content'
        );
        //Remove our export directory
        $this->removeExportRoot();
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
}
