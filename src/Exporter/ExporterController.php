<?php
namespace App\Exporter;

use App\Exporter\ExporterDefaults;
use App\Exporter\Utilities\Config;
use Symfony\Component\Routing\Annotation\Route;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The exporter class for exporting the content to MM Interface
 */
class ExporterController extends TwigAwareController implements BackendZoneInterface
{

    /**
     * Our configuration class
     *
     * @var Config
     */
    private $exporterConfig = null;

    /**
     * Build the class
     *
     */
    public function __construct()
    {
        $this->exporterConfig = new Config();
    }

    /**
     * @Route("/exporter/", name="app_exporter")
     */
    public function manage(): Response
    {
        $publicPath = $this->exporterConfig->get('exporter/public_path');
        return $this->render('backend/exporter/index.twig', []);
    }

}
