<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\Package;
use Bolt\Repository\TaxonomyRepository;

/**
 * A data store for packages
 */
class PackagesStore
{
    /**
     * The repository for retrieving taxonomy
     *
     * @var TaxonomyRepository
     */
    protected $taxonomyRepository = null;

    /**
     * Build the store
     *
     * @param TaxonomyRepository $taxonomyRepository Bolt's Taxonomy Repository
     */
    public function __construct(
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->taxonomyRepository = $taxonomyRepository;
    }

    /**
     * Find all packages.
     *
     * @return array an array of packages
     */
    public function findAll(): array
    {
        $packages = [];
        $query = $this->taxonomyRepository->findBy([
            'type' => 'packages',
        ]);
        foreach ($query as $data) {
            $packages[] = new Package(
                $data->getSlug(),
                $data->getName()
            );
        }

        return $packages;
    }

    /**
     * Find a package by its' slug
     *
     * @param string $slug The slug
     *
     * @return Package The package or null if it does not exist
     */
    public function findBySlug(string $slug): ?Package
    {
        $query = $this->taxonomyRepository->findBy([
            'type' => 'packages',
            'slug' => $slug,
        ]);
        if (0 === \count($query)) {
            return null;
        }

        return new Package(
            $query[0]->getSlug(),
            $query[0]->getName()
        );
    }
}
