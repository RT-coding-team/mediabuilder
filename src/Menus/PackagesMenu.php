<?php

declare(strict_types=1);

namespace App\Menus;

use App\Constants;
use Bolt\Menu\ExtensionBackendMenuInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds a menu item for the packages navigation
 */
class PackagesMenu implements ExtensionBackendMenuInterface
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
        if (
            ! $this->authorizationChecker->isGranted(Constants::EXPORTER_REQUIRED_PERMISSION) &&
            (! $this->authorizationChecker->isGranted(Constants::PACKAGE_MANAGER_REQUIRED_PERMISSION))
        ) {
            return;
        }
        $menu->addChild('Packages', [
            'extras' => [
                'name' => 'Packages',
                'type' => 'separator',
            ],
        ]);
        if ($this->authorizationChecker->isGranted(Constants::PACKAGE_MANAGER_REQUIRED_PERMISSION)) {
            $menu->addChild('Manage', [
                'uri' => $this->urlGenerator->generate('app_package_manager'),
                'extras' => [
                    'icon' => 'fas fa-boxes',
                ],
            ]);
        }
        if ($this->authorizationChecker->isGranted(Constants::EXPORTER_REQUIRED_PERMISSION)) {
            $menu->addChild('Export', [
                'uri' => $this->urlGenerator->generate('app_exporter'),
                'extras' => [
                    'icon' => 'fa-parachute-box',
                ],
            ]);
        }
    }
}
