<?php

namespace App\Exporter;

use Bolt\Menu\ExtensionBackendMenuInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds a menu item for the exporter
 */
class ExporterMenu implements ExtensionBackendMenuInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /**
     * Construct the class
     *
     * @param UrlGeneratorInterface $urlGenerator The URL generator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Add items to the side menu
     *
     * @param MenuItem $menu The current menu item
     */
    public function addItems(MenuItem $menu): void
    {
        // This adds a new heading
        $menu->addChild('Exporter', [
            'extras' => [
                'name' => 'Interface Exporter',
                'type' => 'separator',
            ]
        ]);

        // This adds the link
        $menu->addChild('Manage', [
           'uri' => $this->urlGenerator->generate('app_exporter'),
            'extras' => [
                'icon' => 'fa-file-export'
            ]
        ]);
    }
}
