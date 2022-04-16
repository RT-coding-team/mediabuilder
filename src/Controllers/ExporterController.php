<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Constants;
use App\Stores\PackagesStore;
use App\Utilities\Config;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
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
     * Our package store
     *
     * @var PackagesStore
     */
    private $packagesStore = null;

    /**
     * Build the class
     *
     * @param AuthorizationCheckerInterface $authorizationChecker permission checker
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        PackagesStore $packagesStore
    ) {
        $this->exportConfig = new Config();
        $this->authorizationChecker = $authorizationChecker;
        $this->packagesStore = $packagesStore;
        $publicPath = $this->exportConfig->get('exporter/public_path');
        $this->paths['exportRelative'] = $publicPath;
        $publicDir = Path::canonicalize(\dirname(__DIR__, 2).'/public/');
        $this->paths['export'] = Path::canonicalize($publicDir.$publicPath);
    }

    /**
     * Retrieve the list of files
     */
    public function apiFiles(): Response
    {
        $media = $this->getMedia();
        $response = $this->render('backend/exporter/api/files.twig', [
            'media' => $media,
        ]);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
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
     * @Route("/exporter/start/{slug}", name="app_exporter_start", defaults={"slug"="all"})
     *
     * Start the export process
     */
    public function start(string $slug): Response
    {
        if (! $this->isAllowed()) {
            $response = new Response(json_encode([]), 403);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $slugArgument = '';
        if ('all' !== $slug) {
            $exists = $this->packagesStore->findBySlug($slug);
            if (! $exists) {
                $response = new Response(json_encode([
                    'errors' => 'The package does not exist.',
                ]), 500);
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }
            $slugArgument = ' '.$slug;
        }
        $binDir = Path::canonicalize(\dirname(__DIR__, 2).'/bin/');
        $binFile = Path::join($binDir, 'console');
        $phpFile = Path::join(PHP_BINDIR, 'php');
        Process::fromShellCommandline($phpFile.' '.$binFile.' exporter:export'.$slugArgument.' &')->start();
        $response = new Response(json_encode([
            'started' => true,
        ], 200));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
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
        $media = $this->getMedia();
        $packages = $this->packagesStore->findAll();

        return $this->render('backend/exporter/index.twig', [
            'media' => $media,
            'packages' => $packages,
        ]);
    }

    /**
     * Get the media (keyed to slug)
     *
     * @return array The current media
     */
    private function getMedia(): array
    {
        $files = [];
        $dateFormat = $this->exportConfig->get('exporter/file_date_suffix');
        if (! $dateFormat) {
            $dateFormat = Constants::DEFAULT_FILE_DATE_SUFFIX;
        }
        foreach (glob($this->paths['export'].'/*.zip') as $filename) {
            $pieces = explode('_', basename($filename, '.zip'));
            $isSlim = false;
            $slug = '';
            if (2 === \count($pieces)) {
                $slug = $pieces[0];
                $package = $this->packagesStore->findBySlug($slug);
                $date = \DateTime::createFromFormat($dateFormat, $pieces[1]);
            } elseif (3 === \count($pieces)) {
                $slug = $pieces[1];
                $package = $this->packagesStore->findBySlug($slug);
                $date = \DateTime::createFromFormat($dateFormat, $pieces[2]);
                $isSlim = true;
            } else {
                continue;
            }
            $packageName = $package ? $package->name : '';
            if (! \array_key_exists($slug, $files)) {
                $files[$slug] = [];
            }
            $files[$slug][] = [
                'date' => $date->format('M j, Y g:i A'),
                'filename' => basename($filename),
                'filepath' => Path::join($this->paths['exportRelative'], basename($filename)),
                'is_slim' => $isSlim,
                'package' => $packageName,
                'timestamp' => $date->getTimestamp(),
            ];
        }
        $sorted = [];
        foreach ($files as $key => $val) {
            uasort($val, function ($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });
            $sorted[$key] = $val;
        }
        ksort($sorted);

        return $sorted;
    }

    /**
     * Check if the user is allowed here?
     *
     * @return bool yes|no
     */
    private function isAllowed(): bool
    {
        return $this->authorizationChecker->isGranted(Constants::EXPORTER_REQUIRED_PERMISSION);
    }
}
