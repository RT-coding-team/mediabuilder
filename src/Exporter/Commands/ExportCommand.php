<?php

declare(strict_types=1);

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
     */
    private $collectionsStore = null;

    /**
     * An array of our directories.
     *
     * @var array
     */
    private $directories = [
        'exports' => '',
        'public' => '',
    ];

    /**
     * The content exporter
     *
     * @var ContentExporter
     */
    private $contentExporter = null;

    /**
     * Our packages store
     *
     * @var PackagesStore
     */
    private $packagesStore = null;

    /**
     * Our singles store
     *
     * @var SinglesStore
     */
    private $singlesStore = null;

    /**
     * Build the class
     *
     * @param ContentRepository $contentRepository The content repository
     * @param RelationRepository $relationRepository The relation repository
     * @param TaxonomyRepository $taxonomyRepository The taxonomy repository
     */
    public function __construct(
        ContentRepository $contentRepository,
        RelationRepository $relationRepository,
        TaxonomyRepository $taxonomyRepository
    ) {
        parent::__construct();

        $this->config = new Config();
        $publicPath = $this->config->get('exporter/public_path');
        if (! $publicPath) {
            $publicPath = ExporterDefaults::PUBLIC_PATH;
        }
        $this->directories['public'] = Path::canonicalize(\dirname(__DIR__, 3).'/public/');
        $this->directories['exports'] = Path::canonicalize($this->directories['public'].$publicPath);
        if (! file_exists($this->directories['exports'])) {
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
     */
    protected function configure(): void
    {
        $this->setDescription('Package the content to be used with the MM Interface.');
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The status based on Command enum
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        set_time_limit(0);
        $supported = $this->config->get('exporter/supported_languages');
        if (! $supported) {
            $supported = ExporterDefaults::SUPPORTED_LANGUAGES;
        }
        $this->contentExporter->setOutput($output);
        try {
            $packages = $this->buildPackages($supported);
            $this->export($output, $packages, $supported);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Build the packages to send to the exporter
     *
     * @param array $supported The supported languages
     *
     * @return array The array of packages
     */
    private function buildPackages(array $supported): array
    {
        $results = [];
        $packages = $this->packagesStore->findAll();
        foreach ($packages as $package) {
            foreach ($supported as $lang) {
                $localeCode = $lang['bolt_locale_code'];
                $collections = $this->collectionsStore->findAll($localeCode);
                // Find collections that belong to the given package
                foreach ($collections as $collection) {
                    if ($collection->belongsTo($package->slug)) {
                        $package->addCollection($localeCode, $collection);
                    }
                }
                $singles = $this->singlesStore->findAll($localeCode);
                // Find singles that belong to the given package
                foreach ($singles as $single) {
                    if ($single->belongsTo($package->slug)) {
                        $package->addSingle($localeCode, $single);
                    }
                }
            }
            // Remove empty packages
            if (! $package->isEmpty()) {
                $results[] = $package;
            }
        }

        return $results;
    }

    /**
     * Export the packages
     *
     * @param OutputInterface $output The output interface
     * @param array $packages The packages to export
     * @param array $supported The supported languages
     */
    private function export(OutputInterface $output, array $packages, array $supported): void
    {
        $fileDateSuffix = $this->config->get('exporter/file_date_suffix');
        if (! $fileDateSuffix) {
            $fileDateSuffix = ExporterDefaults::FILE_DATE_SUFFIX;
        }
        $logo = '';
        $logoPath = $this->config->get('exporter/logo_public_path');
        if ($logoPath) {
            $logo = Path::canonicalize($this->directories['public'].$logoPath);
            if (! file_exists($logo)) {
                $logo = '';
            }
        }
        foreach ($packages as $package) {
            $output->writeln('Creating package: '.$package->title);
            $this->contentExporter->start($package->title, $package->slug, $fileDateSuffix, $logo);
            foreach ($supported as $lang) {
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
                $this->contentExporter->startLocale($lang['bolt_locale_code'], $interface);
                $this->contentExporter->addLanguage($language);

                $collections = $package->getCollectionsByLocale($lang['bolt_locale_code']);
                foreach ($collections as $collection) {
                    $this->contentExporter->addCollection($collection);
                }

                $singles = $package->getSinglesByLocale($lang['bolt_locale_code']);
                foreach ($singles as $single) {
                    $this->contentExporter->addSingle($single);
                }

                $this->contentExporter->finishLocale();
            }
            $this->contentExporter->finish();
            $output->writeln('Completed package: '.$package->title);
        }
    }
}
