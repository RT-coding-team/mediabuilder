<?php

declare(strict_types=1);

namespace App\Exporter\Stores;

use App\Exporter\Models\Package;
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
}
