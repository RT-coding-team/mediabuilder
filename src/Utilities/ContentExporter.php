<?php
namespace App\Utilities;

use App\Models\Collection;
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
     * @param   string  $locale         The locale we are working with (default: en)
     * @param   string  $name           The name to append to the archive (default: export)
     * @param   string  $dateFormat     A date format to append to the end of the archive (default: m-d-Y-H-i)
     * @return void
     *
     * @link https://www.php.net/manual/en/datetime.format.php
     */
    public function start(
        string $locale = 'en',
        string $name = 'export',
        string $dateFormat = 'm-d-Y-H-i'
    )
    {
        $today = new \DateTime();
        $this->mainData = [
            'itemName'  =>  'Exported Data',
            'content'   =>  []
        ];
        $this->exportFilename = $name . '-' . $today->format($dateFormat);
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
     * Finish up the exporting
     *
     * @return void
     */
    public function finish()
    {
        $mainPath = Path::join($this->directories['export_data'], 'main.json');
        file_put_contents($mainPath, json_encode($this->mainData));
        // Remove our export directory
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
