<?php
namespace App\Exporter;

use App\Exporter\Stores\CollectionsStore;
use App\Exporter\Stores\SinglesStore;
use App\Exporter\Utilities\ContentExporter;
use Symfony\Component\Routing\Annotation\Route;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\PathUtil\Path;

/**
 * The exporter class for exporting the content to MM Interface
 */
class ExporterController extends TwigAwareController implements BackendZoneInterface
{

    /**
     * The directory in the public folder to store exports.
     *
     * @var string
     */
    public $exportsDir = '/files/exports/';

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
     * Build the class
     *
     * @param ContentRepository         $contentRepository      The content repository
     * @param RelationRepository        $relationRepository     The relation repository
     */
    public function __construct(
        ContentRepository $contentRepository,
        RelationRepository $relationRepository
    )
    {
        $this->directories['public'] = Path::canonicalize(dirname(__DIR__, 2) . '/public/');
        $this->directories['exports'] = Path::canonicalize($this->directories['public'] . $this->exportsDir);
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
     * @Route("/exporter/", name="app_exporter")
     */
    public function manage(): Response
    {
        // $this->export();
        return $this->render('backend/exporter/index.twig', []);
    }

    // TODO: Handle all the locales. Can we get the supported locals dynamically
    // TODO: Handle languges.json file
    public function export() {
        set_time_limit(0);
        $this->contentExporter->start();
        $collections = $this->collectionsStore->findAll();
        foreach ($collections as $collection) {
            $this->contentExporter->addCollection($collection);
        }
        $singles = $this->singlesStore->findAll();
        foreach ($singles as $single) {
            $this->contentExporter->addSingle($single);
        }
        $this->contentExporter->finish();
    }

}
