<?php

declare(strict_types=1);

namespace App\Commands;

use App\Constants;
use App\Stores\CollectionsStore;
use App\Stores\PackageExportsStore;
use App\Stores\PackagesStore;
use App\Stores\SinglesStore;
use App\Utilities\Config;
use App\Utilities\FileLogger;
use App\Utilities\PackageExporter;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Bolt\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
    private $paths = [
        'exports' => '',
        'exportRelative' => '',
        'public' => '',
    ];

    /**
     * The file logger for tracking progress
     *
     * @var FileLogger
     */
    private $fileLogger = null;

    /**
     * The content exporter
     *
     * @var PackageExporter
     */
    private $packageExporter = null;

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
     * @param CollectionsStore $collectionsStore Our collections store
     * @param ContentRepository $contentRepository The content repository
     * @param EntityManagerInterface $entityManager Symfony's entity manager
     * @param PackagesStore $packagesStore Our pakages store
     * @param RelationRepository $relationRepository The relation repository
     * @param SinglesStore $singlesStore Our Singles store
     * @param TaxonomyRepository $taxonomyRepository The taxonomy repository
     */
    public function __construct(
        CollectionsStore $collectionsStore,
        ContentRepository $contentRepository,
        EntityManagerInterface $entityManager,
        PackagesStore $packagesStore,
        RelationRepository $relationRepository,
        SinglesStore $singlesStore,
        TaxonomyRepository $taxonomyRepository
    ) {
        parent::__construct();

        $this->config = new Config();
        $siteUrl = $this->config->get('exporter/site_url');
        if (! empty($siteUrl)) {
            $siteUrl = rtrim($siteUrl, '/').'/';
        }
        $publicPath = $this->config->get('exporter/public_path');
        if (! $publicPath) {
            $publicPath = Constants::EXPORTS_PUBLIC_PATH;
        }
        $this->paths['public'] = Path::canonicalize(\dirname(__DIR__, 2).'/public/');
        $this->paths['exportRelative'] = $publicPath;
        $this->paths['exports'] = Path::canonicalize($this->paths['public'].$publicPath);
        if (! file_exists($this->paths['exports'])) {
            mkdir($this->paths['exports'], 0777, true);
        }
        $this->collectionsStore = $collectionsStore;
        $this->collectionsStore->siteUrl = $siteUrl;
        $this->singlesStore = $singlesStore;
        $this->singlesStore->siteUrl = $siteUrl;
        $this->packagesStore = $packagesStore;
    }

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Package the content to be used with the MM Interface.')
            ->addArgument(
                'slug',
                InputArgument::OPTIONAL,
                'The slug of the package to export. If it is not supplied, all will be exported.'
            );
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
        $slug = $input->getArgument('slug');
        $this->fileLogger = new FileLogger(
            $output,
            Path::join($this->paths['exports'], 'export_progress.json')
        );
        $this->packageExporter = new PackageExporter(
            $this->paths['public'],
            $this->paths['exports'],
            $this->config,
            $this->fileLogger
        );
        // The currently available packages in the database
        if ($slug) {
            $package = $this->packagesStore->findBySlug($slug);
            // We put in array to be iterated in build process
            $available = [$package];
        } else {
            $slug = '';
            $available = $this->packagesStore->findAll();
        }
        if (empty($available)) {
            $this->fileLogger->logError('No packages found!');

            return Command::FAILURE;
        }
        $this->removeOldExports($slug);

        try {
            $packages = $this->buildPackages($available);
            $this->exportPackages($packages);
            $this->fileLogger->logFinished('Content Exporter');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->fileLogger->logError($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Build the packages (its structure) with all its collections and singles into an array
     * so we can send it to the export method. We skip packages that do not have collections
     * or singles since they will have no files.
     *
     * @param array $available An array of all the available packages from the database
     *
     * @return array The array of packages
     */
    private function buildPackages($available): array
    {
        $results = [];
        $supported = $this->config->get('exporter/supported_languages');
        if (! $supported) {
            $supported = Constants::DEFAULT_SUPPORTED_LANGUAGES;
        }
        foreach ($available as $package) {
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
     * Package up the package files and create zip archives.
     *
     * @param array $packages All the packages (structure) with collections and singles to export
     */
    private function exportPackages(array $packages): void
    {
        foreach ($packages as $package) {
            $this->fileLogger->log('Creating package: '.$package->name);
            $this->packageExporter->export($package, false);
            // Create a slim version
            $this->packageExporter->export($package, true);
            $this->fileLogger->log('Completed package: '.$package->name);
        }
    }

    /**
     * Remove all the old exports for a specific slug. Use an empty string to remove all exports.
     *
     * @param string $slug The slug of the exports to remove. (default: '' ie all exports)
     */
    private function removeOldExports(string $slug = ''): void
    {
        $dateFormat = $this->config->get('exporter/file_date_suffix');
        if (! $dateFormat) {
            $dateFormat = Constants::DEFAULT_FILE_DATE_FORMAT;
        }
        $store = new PackageExportsStore(
            $this->paths['exports'],
            $dateFormat,
            $this->packagesStore,
            $this->paths['exportRelative']
        );
        if (empty($slug)) {
            $store->destroyAll();
        } else {
            $store->destroy($slug);
        }
    }
}
