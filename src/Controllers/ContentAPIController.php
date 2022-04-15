<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Stores\CoursesStore;
use Bolt\Configuration\Config as BoltConfig;
use Bolt\Controller\TwigAwareController;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Bolt\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * An API for retrieving information about content in the Bolt instance.
 * Currently, we were unable to get the current Bolt API to work with public access.
 * We talked with the Bolt team, and they said it should just work. However,
 * it does not work. Is there something wrong with our setup?
 *
 * @TODO Replace this with the current Bolt API
 */
class ContentAPIController extends TwigAwareController
{
    /**
     * The store for retrieving courses
     *
     * @var CoursesStore
     */
    public $coursesStore = null;

    /**
     * Build the class
     */
    public function __construct(
        BoltConfig $boltConfig,
        ContentRepository $contentRepository,
        CoursesStore $coursesStore,
        EntityManagerInterface $entityManager,
        RelationRepository $relationRepository,
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->coursesStore = $coursesStore;
    }

    /**
     * Get a list of all courses stored in Bolt
     *
     * @return Response A JSON response
     */
    public function courses(): Response
    {
        $courses = $this->coursesStore->findAll();
        $response = $this->render('backend/content-api/courses.twig', [
            'courses' => $courses,
        ]);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
