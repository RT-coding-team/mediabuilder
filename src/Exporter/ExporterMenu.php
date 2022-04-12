<?php

declare(strict_types=1);

namespace App\Exporter;

use Bolt\Menu\ExtensionBackendMenuInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds a menu item for the exporter
 */
class ExporterMenu implements ExtensionBackendMenuInterface
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
        if (! $this->authorizationChecker->isGranted(ExporterDefaults::REQUIRED_PERMISSION)) {
            return;
        }
        // This adds a new heading
        $menu->addChild('Packages', [
            'extras' => [
                'name' => 'Packages',
                'type' => 'separator',
            ],
        ]);

        // This adds the link
        $menu->addChild('Export Packages', [
            'uri' => $this->urlGenerator->generate('app_exporter'),
            'extras' => [
                'icon' => 'fa-parachute-box',
            ],
        ]);
    }
}
