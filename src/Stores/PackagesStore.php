<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\Package;
use Bolt\Common\Str;
use Bolt\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * A data store for packages
 */
class PackagesStore
{
    /**
     * Doctrine's entity manager
     *
     * @var EntityManagerInterface
     */
    protected $entityManager = null;

    /**
     * The repository for retrieving taxonomy
     *
     * @var TaxonomyRepository
     */
    protected $taxonomyRepository = null;

    /**
     * Build the store
     *
     * @param EntityManagerInterface $entityManager Doctrine's entity manager
     * @param TaxonomyRepository $taxonomyRepository Bolt's Taxonomy Repository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->entityManager = $entityManager;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    /**
     * Create a new package
     *
     * @param string $name The name for the package
     *
     * @return bool This it create successfully?
     */
    public function create(string $name): bool
    {
        $slug = Str::slug($name);
        $exists = $this->findBySlug($slug);
        if ($exists) {
            return false;
        }
        $taxonomy = $this->taxonomyRepository->factory('packages', $slug, $name);
        $this->entityManager->persist($taxonomy);
        $this->entityManager->flush();

        return true;
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
        usort($packages, fn ($a, $b) => strcmp($a->title, $b->title));

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
