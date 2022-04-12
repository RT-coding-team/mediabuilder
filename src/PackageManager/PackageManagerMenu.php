<?php

declare(strict_types=1);

namespace App\PackageManager;

use Bolt\Menu\ExtensionBackendMenuInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds a menu item for the package manager
 */
class PackageManagerMenu implements ExtensionBackendMenuInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /**
     * The authorization checker.
     *
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker = null;

    /**
     * Construct the class
     *
     * @param UrlGeneratorInterface $urlGenerator The URL generator
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Add items to the side menu
     *
     * @param MenuItem $menu The current menu item
     */
    public function addItems(MenuItem $menu): void
    {
        if (! $this->authorizationChecker->isGranted(PackageManagerDefaults::REQUIRED_PERMISSION)) {
            return;
        }
        $menu->addChild('Packages', [
            'extras' => [
                'name' => 'Packages',
                'type' => 'separator',
            ],
        ]);
        // This adds the link
        $menu->addChild('Manage Packages', [
            'uri' => $this->urlGenerator->generate('app_package_manager'),
            'extras' => [
                'icon' => 'fas fa-boxes',
            ],
        ]);
    }
}
