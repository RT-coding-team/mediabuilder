<?php
namespace App\Exporter;

use App\Exporter\ExporterDefaults;
use Symfony\Component\Routing\Annotation\Route;
use Bolt\Configuration\Config;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Symfony\Component\HttpFoundation\Response;

/**
 * The exporter class for exporting the content to MM Interface
 */
class ExporterController extends TwigAwareController implements BackendZoneInterface
{

    /**
     * The configuration class
     *
     * @var Config
     */
    private $siteConfig = null;

    /**
     * Build the class
     *
     * @param Config $config The configuration class
     */
    public function __construct(Config $config)
    {
        $this->siteConfig = $config;
    }

    /**
     * @Route("/exporter/", name="app_exporter")
     */
    public function manage(): Response
    {
        $publicPath = $this->siteConfig->get('general/exporter/public_path');
        if (!$publicPath) {
            $publicPath = ExporterDefaults::PUBLIC_PATH;
        }
        return $this->render('backend/exporter/index.twig', []);
    }

}
