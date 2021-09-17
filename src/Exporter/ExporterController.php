<?php

declare(strict_types=1);

namespace App\Exporter;

use App\Exporter\Utilities\Config;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Webmozart\PathUtil\Path;

/**
 * The exporter class for exporting the content to MM Interface
 */
class ExporterController extends TwigAwareController implements BackendZoneInterface
{
    /**
     * Our Exporter Config
     *
     * @var Config
     */
    public $exportConfig = null;

    /**
     * The exports directory
     *
     * @var string
     */
    public $paths = [
        'export' => '',
        'exportRelative' => '',
    ];

    /**
     * The authorization checker
     *
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker = null;

    /**
     * Build the class
     *
     * @param AuthorizationCheckerInterface $authorizationChecker permission checker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->exportConfig = new Config();
        $this->authorizationChecker = $authorizationChecker;
        $publicPath = $this->exportConfig->get('exporter/public_path');
        $this->paths['exportRelative'] = $publicPath;
        $publicDir = Path::canonicalize(\dirname(__DIR__, 2).'/public/');
        $this->paths['export'] = Path::canonicalize($publicDir.$publicPath);
    }

    /**
     * @Route("/exporter/", name="app_exporter")
     *
     * Manage the files
     */
    public function manage(): Response
    {
        if (! $this->isAllowed()) {
            return $this->redirectToRoute('bolt_dashboard');
        }
        $files = [];
        $dateFormat = $this->exportConfig->get('exporter/file_date_suffix');
        if (! $dateFormat) {
            $dateFormat = ExporterDefaults::FILE_DATE_SUFFIX;
        }
        foreach (glob($this->paths['export'].'/*.zip') as $filename) {
            $pieces = explode('_', basename($filename, '.zip'));
            if (2 > \count($pieces)) {
                continue;
            }
            $date = \DateTime::createFromFormat($dateFormat, $pieces[1]);
            $files[] = [
                'date' => $date->format('M j, Y g:i A'),
                'filename' => basename($filename),
                'filepath' => Path::join($this->paths['exportRelative'], basename($filename)),
                'timestamp' => $date->getTimestamp(),
            ];
        }
        uasort($files, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $this->render('backend/exporter/index.twig', [
            'files' => $files,
        ]);
    }

    /**
     * @Route("/exporter/delete", name="app_exporter_delete", methods={"DELETE"})
     *
     * Manage the files
     */
    public function delete(Request $request): Response
    {
        if (! $this->isAllowed()) {
            return $this->redirectToRoute('bolt_dashboard');
        }
        $filename = $request->request->get('filename');
        if (! $filename) {
            throw $this->createNotFoundException('The file does not exist!');
        }
        $filepath = Path::join($this->paths['export'], $filename);
        if (! file_exists($filepath)) {
            throw $this->createNotFoundException('The file does not exist!');
        }
        $deleted = unlink($filepath);
        $deletedVal = $deleted ? 'true' : 'false';

        return $this->redirectToRoute('app_exporter', [
            'deleted' => $deletedVal,
        ]);
    }

    /**
     * Check if the user is allowed here?
     *
     * @return bool yes|no
     */
    private function isAllowed(): bool
    {
        return $this->authorizationChecker->isGranted(ExporterDefaults::REQUIRED_PERMISSION);
    }
}
