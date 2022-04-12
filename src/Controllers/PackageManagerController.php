<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Defaults\PackageManagerDefaults;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
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

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @Route("/package-manager/", name="app_package_manager")
     *
     * Manage the files
     */
    public function manage(): Response
    {
        if (! $this->authorizationChecker->isGranted(PackageManagerDefaults::REQUIRED_PERMISSION)) {
            return $this->redirectToRoute('bolt_dashboard');
        }

        return $this->render('backend/package-manager/index.twig', []);
    }
}
