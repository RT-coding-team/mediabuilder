<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Constants;
use App\Stores\CollectionsStore;
use App\Stores\PackagesStore;
use App\Stores\SinglesStore;
use Bolt\Configuration\Config as BoltConfig;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Bolt\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The controller for the package manager
 */
class PackageManagerController extends TwigAwareController implements BackendZoneInterface
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
        CollectionsStore $collectionsStore,
        ContentRepository $contentRepository,
        EntityManagerInterface $entityManager,
        PackagesStore $packagesStore,
        RelationRepository $relationRepository,
        SinglesStore $singlesStore,
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->collectionsStore = $collectionsStore;
        $this->packagesStore = $packagesStore;
        $this->singlesStore = $singlesStore;
    }

    /**
     * @Route("/package-manager/", name="app_package_manager")
     *
     * Manage the files
     */
    public function manage(): Response
    {
        if (! $this->authorizationChecker->isGranted(Constants::PACKAGE_MANAGER_REQUIRED_PERMISSION)) {
            return $this->redirectToRoute('bolt_dashboard');
        }

        $collections = $this->collectionsStore->findAll();
        $singles = $this->singlesStore->findAll();
        $packages = $this->packagesStore->findAll();

        return $this->render(
            'backend/package-manager/index.twig',
            [
                'collections' => $collections,
                'packages' => $packages,
                'singles' => $singles,
            ]
        );
    }
}
