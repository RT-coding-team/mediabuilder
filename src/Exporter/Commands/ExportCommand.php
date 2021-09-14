<?php
namespace App\Exporter\Commands;

use App\Exporter\ExporterDefaults;
use App\Exporter\Stores\CollectionsStore;
use App\Exporter\Stores\SinglesStore;
use App\Exporter\Utilities\ContentExporter;
use Bolt\Configuration\Config;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * A command to export the content to the MM Interface
 *
 * TODO: Handle all the locales. Can we get the supported locals dynamically
 * TODO: Handle languges.json file
 */
class ExportCommand extends Command
{

    /**
     * The command for this script.
     *
     * @var string
     */
    protected static $defaultName = 'exporter:export';

    /**
     * Our collections store
     *
     * @var CollectionsStore
     * @access private
     */
    private $collectionsStore = null;

    /**
     * An array of our directories.
     *
     * @var array
     * @access private
     */
    private $directories = [
        'exports'   =>  '',
        'public'    =>  ''
    ];

    /**
     * The content exporter
     * @var ContentExporter
     * @access private
     */
    private $contentExporter = null;

    /**
     * Our singles store
     *
     * @var SinglesStore
     * @access private
     */
    private $singlesStore = null;

    /**
     * The configuration class
     *
     * @var Config
     */
    private $siteConfig = null;

    /**
     * Build the class
     *
     * @param ContentRepository         $contentRepository      The content repository
     * @param RelationRepository        $relationRepository     The relation repository
     */
    public function __construct(
        Config $config,
        ContentRepository $contentRepository,
        RelationRepository $relationRepository
    )
    {
        parent::__construct();
        $this->siteConfig = $config;
        $publicPath = $this->siteConfig->get('general/exporter/public_path');
        if (!$publicPath) {
            $publicPath = ExporterDefaults::PUBLIC_PATH;
        }
        $this->directories['public'] = Path::canonicalize(dirname(__DIR__, 3) . '/public/');
        $this->directories['exports'] = Path::canonicalize($this->directories['public'] . $publicPath);
        if (!file_exists($this->directories['exports'])) {
            mkdir($this->directories['exports'], 0777, true);
        }
        $this->collectionsStore = new CollectionsStore(
            $contentRepository,
            $relationRepository,
            $this->directories['public']
        );
        $this->singlesStore = new SinglesStore(
            $contentRepository,
            $relationRepository,
            $this->directories['public']
        );
        $this->contentExporter = new ContentExporter($this->directories['exports']);
    }

    /**
     * Configure the command
     *
     * @access protected
     */
    protected function configure(): void
    {
        $this->setDescription('Package the content to be used with the MM Interface.');
    }

    /**
     * Execute the command
     *
     * @param  InputInterface  $input  The input interface
     * @param  OutputInterface $output The output interface
     * @return int                     The status based on Command enum
     * @access private
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        $this->contentExporter->setOutput($output);
        $this->export();
        return Command::SUCCESS;
    }

    /**
     * Export the content
     *
     * @access private
     */
    private function export(): void
    {
        $filePrefix = $this->siteConfig->get('general/exporter/file_prefix');
        if (!$filePrefix) {
            $filePrefix = ExporterDefaults::FILE_PREFIX;
        }
        $fileDateSuffix = $this->siteConfig->get('general/exporter/file_date_suffix');
        if (!$fileDateSuffix) {
            $fileDateSuffix = ExporterDefaults::FILE_DATE_SUFFIX;
        }
        $this->contentExporter->start('en', $filePrefix, $fileDateSuffix);
        $collections = $this->collectionsStore->findAll();
        foreach ($collections as $collection) {
            $this->contentExporter->addCollection($collection);
        }
        // $singles = $this->singlesStore->findAll();
        // foreach ($singles as $single) {
        //     $this->contentExporter->addSingle($single);
        // }
        $this->contentExporter->finish();
    }

}
