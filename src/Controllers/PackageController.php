<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Constants;
use App\Stores\CollectionsStore;
use App\Stores\PackageExportsStore;
use App\Stores\PackagesStore;
use App\Stores\SinglesStore;
use App\Utilities\Config;
use Bolt\Configuration\Config as BoltConfig;
use Bolt\Controller\TwigAwareController;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Bolt\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
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
     * Our package exports store
     *
     * @var PackageExportsStore
     */
    private $packageExportsStore = null;

    /**
     * Our singles store
     *
     * @var SinglesStore
     */
    private $singlesStore = null;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        BoltConfig $boltConfig,
        CollectionsStore $collectionsStore,
        Config $config,
        ContentRepository $contentRepository,
        EntityManagerInterface $entityManager,
        PackagesStore $packagesStore,
        RelationRepository $relationRepository,
        SinglesStore $singlesStore,
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->collectionsStore = $collectionsStore;
        $dateFormat = $config->get('exporter/file_date_suffix');
        if (! $dateFormat) {
            $dateFormat = Constants::DEFAULT_FILE_DATE_FORMAT;
        }
        $relativePublicPath = $config->get('exporter/public_path');
        $absolutePublicDir = Path::canonicalize(\dirname(__DIR__, 2).'/public/');
        $absolutePath = Path::canonicalize($absolutePublicDir.$relativePublicPath);
        $this->packageExportsStore = new PackageExportsStore(
            $absolutePath,
            $dateFormat,
            $packagesStore,
            $relativePublicPath
        );
        $this->packagesStore = $packagesStore;
        $this->singlesStore = $singlesStore;
    }

    /**
     * @Route("/packages", name="app_packages_create", methods={"POST"})
     *
     * Create a new package
     */
    public function create(Request $request): Response
    {
        if (! $this->authorizationChecker->isGranted(Constants::PACKAGE_MANAGER_REQUIRED_PERMISSION)) {
            $response = new Response(json_encode([]), 403);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $data = json_decode($request->getContent());
        $errors = [];
        if (! isset($data->name) || empty($data->name)) {
            $errors[] = 'Missing the package name.';
        }
        if (! empty($errors)) {
            $response = new Response(json_encode([
                'errors' => $errors,
            ]), 400);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $package = $this->packagesStore->create($data->name);
        if (! $package) {
            $response = new Response(json_encode([
                'errors' => 'Unable to create the package.',
            ]), 500);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $response = new Response(json_encode([
            'package' => $package,
        ]), 201);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/packages/{slug}", name="app_packages_delete", methods={"DELETE"})
     *
     * Delete a package
     */
    public function destroy(string $slug): Response
    {
        if (! $this->authorizationChecker->isGranted(Constants::PACKAGE_MANAGER_REQUIRED_PERMISSION)) {
            $response = new Response(json_encode([]), 403);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $errors = [];
        if (! isset($slug) || empty($slug)) {
            $errors[] = 'Missing the package slug.';
        }
        if (! empty($errors)) {
            $response = new Response(json_encode([
                'errors' => $errors,
            ]), 400);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $success = $this->packagesStore->destroy($slug);
        $this->packageExportsStore->destroy($slug);
        if (! $success) {
            $response = new Response(json_encode([
                'errors' => 'Unable to destroy the package.',
            ]), 500);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $response = new Response(json_encode([]), 204);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
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
    public function toggle(Request $request, string $slug): Response
    {
        if (! $this->authorizationChecker->isGranted(Constants::PACKAGE_MANAGER_REQUIRED_PERMISSION)) {
            $response = new Response(json_encode([]), 403);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $data = json_decode($request->getContent());
        $errors = [];
        if (! isset($data->slug) || empty($data->slug)) {
            $errors[] = 'Missing the package slug.';
        }
        if (! isset($data->related)) {
            $errors[] = 'Missing the related content.';
        }
        if (! isset($data->related->content_type) || empty($data->related->content_type)) {
            $errors[] = 'Missing the related content type.';
        } elseif (! \in_array($data->related->content_type, ['collection', 'single'], true)) {
            $errors[] = 'Related content type can only be a collection or a single.';
        }
        if (! isset($data->related->slug) || empty($data->related->slug)) {
            $errors[] = 'Missing the related slug.';
        }
        if (! empty($errors)) {
            $response = new Response(json_encode([
                'errors' => $errors,
            ]), 400);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        if ('collection' === $data->related->content_type) {
            $store = $this->collectionsStore;
        } else {
            $store = $this->singlesStore;
        }
        $content = $store->findBySlug($data->related->slug);
        if (! $content) {
            $response = new Response(json_encode([
                'errors' => 'You must provide a valid collection/single.',
            ]), 400);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        if (\in_array($data->slug, $content->getPackages(), true)) {
            $state = 'removed';
            $success = $store->removePackage($data->related->slug, $data->slug);
        } else {
            $state = 'added';
            $success = $store->addPackage($data->related->slug, $data->slug);
        }
        if (! $success) {
            $response = new Response(json_encode([
                'errors' => 'Unable to modify the content\'s package.',
            ]), 500);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $response = new Response(json_encode([
            'state' => $state,
        ]), 200);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/packages/{slug}", name="app_packages_update", methods={"PATCH"})
     *
     * Update a package
     */
    public function update(Request $request, string $slug): Response
    {
        if (! $this->authorizationChecker->isGranted(Constants::PACKAGE_MANAGER_REQUIRED_PERMISSION)) {
            $response = new Response(json_encode([]), 403);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $data = json_decode($request->getContent());
        if (empty($data)) {
            $response = new Response(json_encode([
                'errors' => ['You must send the data to update.'],
            ]), 400);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $package = $this->packagesStore->update($slug, $data);
        $this->packageExportsStore->updateSlug($slug, $package->slug);
        if (! $package) {
            $response = new Response(json_encode([
                'errors' => 'Unable to update the package.',
            ]), 500);
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
        $response = new Response(json_encode([
            'package' => $package,
        ]), 201);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
