<?php
namespace App\Exporter\Commands;

use App\Exporter\ExporterDefaults;
use App\Exporter\Models\Language;
use App\Exporter\Stores\CollectionsStore;
use App\Exporter\Stores\PackagesStore;
use App\Exporter\Stores\SinglesStore;
use App\Exporter\Utilities\Config;
use App\Exporter\Utilities\ContentExporter;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Bolt\Repository\TaxonomyRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * A command to export the content to the MM Interface
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
     * Our configuration class
     *
     * @var Config
     */
    private $config = null;

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
     * Our packages store
     *
     * @var PackagesStore
     * @access private
     */
    private $packagesStore = null;

    /**
     * Our singles store
     *
     * @var SinglesStore
     * @access private
     */
    private $singlesStore = null;

    /**
     * Build the class
     *
     * @param ContentRepository         $contentRepository      The content repository
     * @param RelationRepository        $relationRepository     The relation repository
     * @param TaxonomyRepository        $taxonomyRepository     The taxonomy repository
     *
     */
    public function __construct(
        ContentRepository $contentRepository,
        RelationRepository $relationRepository,
        TaxonomyRepository $taxonomyRepository
    )
    {
        parent::__construct();
        $this->config = new Config();
        $publicPath = $this->config->get('exporter/public_path');
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
        $this->packagesStore = new PackagesStore($taxonomyRepository);
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
        try {
            $this->export($output);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Export the content
     * @param  OutputInterface $output The output interface
     *
     * @access private
     */
    private function export(OutputInterface $output): void
    {
        $filePrefix = $this->config->get('exporter/file_prefix');
        if (!$filePrefix) {
            $filePrefix = ExporterDefaults::FILE_PREFIX;
        }
        $fileDateSuffix = $this->config->get('exporter/file_date_suffix');
        if (!$fileDateSuffix) {
            $fileDateSuffix = ExporterDefaults::FILE_DATE_SUFFIX;
        }
        $supported = $this->config->get('exporter/supported_languages');
        if (!$supported) {
            $supported = ExporterDefaults::SUPPORTED_LANGUAGES;
        }
        $this->contentExporter->start($filePrefix, $fileDateSuffix);
        foreach ($supported as $lang) {
            $language = new Language(
                $lang['codes'],
                $lang['text'],
                boolval($lang['default'])
            );
            $interface = $this->config->get('exporter/interface/' . $lang['bolt_locale_code']);
            if (!$interface) {
                $interface = $this->config->get('exporter/interface/en');
            }
            $this->contentExporter->startLocale($lang['bolt_locale_code'], $interface);
            $this->contentExporter->addLanguage($language);
            $collections = $this->collectionsStore->findAll($lang['bolt_locale_code']);
            foreach ($collections as $collection) {
                $this->contentExporter->addCollection($collection);
            }
            $singles = $this->singlesStore->findAll($lang['bolt_locale_code']);
            foreach ($singles as $single) {
                $this->contentExporter->addSingle($single);
            }
            $this->contentExporter->finishLocale();
        }
        $this->contentExporter->finish();
    }

}
