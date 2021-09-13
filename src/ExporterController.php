<?php
namespace App;

use App\Stores\CollectionsStore;
use App\Stores\SinglesStore;
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
     * Our collections store
     *
     * @var CollectionsStore
     * @access private
     */
    private $collectionsStore = null;

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
        $this->publicDir = Path::canonicalize(dirname(__DIR__, 1) . '/public/');
        $this->collectionsStore = new CollectionsStore(
            $contentRepository,
            $relationRepository,
            $this->publicDir
        );
        $this->singlesStore = new SinglesStore(
            $contentRepository,
            $relationRepository,
            $this->publicDir
        );
    }

    /**
     * @Route("/exporter/", name="app_exporter")
     */
    public function manage(): Response
    {
        $collections = $this->collectionsStore->findAll();
        $singles = $this->singlesStore->findAll();
        dd($singles);
        return $this->render('backend/exporter/index.twig', []);
    }

}
