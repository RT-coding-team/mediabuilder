<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Defaults\PackageManagerDefaults;
use App\Stores\CollectionsStore;
use App\Stores\PackagesStore;
use App\Stores\SinglesStore;
use Bolt\Configuration\Config as BoltConfig;
use Bolt\Controller\TwigAwareController;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Webmozart\PathUtil\Path;

/**
 * Controller for handling packages.
 */
class PackageController extends TwigAwareController
{
    /**
     * The authorization checker
     *
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker = null;

    /**
     * Our collections store
     *
     * @var CollectionsStore
     */
    private $collectionsStore = null;

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

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        BoltConfig $boltConfig,
        ContentRepository $contentRepository,
        PackagesStore $packagesStore,
        RelationRepository $relationRepository
    ) {
        $publicPath = Path::canonicalize(\dirname(__DIR__, 2).'/public/');
        $this->authorizationChecker = $authorizationChecker;
        $this->collectionsStore = new CollectionsStore(
            $boltConfig,
            $contentRepository,
            $relationRepository,
            $publicPath,
            ''
        );
        $this->packagesStore = $packagesStore;
        $this->singlesStore = new SinglesStore(
            $boltConfig,
            $contentRepository,
            $relationRepository,
            $publicPath,
            ''
        );
    }

    /**
     * @Route("/packages/toggle/{slug}", name="app_packages_toggle", methods={"POST"})
     *
     * Add/Remove the package for the given content type.
     *
     * @example payload:
     * var payload = {
     *   slug: 'package-slug',
     *   related: {
     *     content_type: 'collection|single',
     *     slug: 'related-slug',
     *   },
     * };
     */
    public function togglePackage(Request $request, string $slug): Response
    {
        if (! $this->authorizationChecker->isGranted(PackageManagerDefaults::REQUIRED_PERMISSION)) {
            $response = new Response(json_encode([]), 403);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $content = json_decode($request->getContent());
        $errors = [];
        if (! isset($content->slug) || empty($content->slug)) {
            $errors[] = 'Missing the package slug.';
        }
        if (! isset($content->related)) {
            $errors[] = 'Missing the related content.';
        }
        if (! isset($content->related->content_type) || empty($content->related->content_type)) {
            $errors[] = 'Missing the related content type.';
        } elseif (! \in_array($content->related->content_type, ['collection', 'single'], true)) {
            $errors[] = 'Related content type can only be a collection or a single.';
        }
        if (! isset($content->related->slug) || empty($content->related->slug)) {
            $errors[] = 'Missing the related slug.';
        }
        if (! empty($errors)) {
            $response = new Response(json_encode([
                'errors' => $errors,
            ]), 400);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        if ('collection' === $content->related->content_type) {
            $collection = $this->collectionsStore->findBySlug($content->related->slug);
            if (! $collection) {
                $response = new Response(json_encode([
                    'errors' => 'You must provide a valid collection.',
                ]), 400);
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }
            print_r($collection);
            exit;
        }
        $response = new Response(json_encode([
            'state' => 'added',
        ]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
