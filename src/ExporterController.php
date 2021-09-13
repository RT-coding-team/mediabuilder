<?php
namespace App;

use App\Models\Collection;
use Symfony\Component\Routing\Annotation\Route;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * The exporter class for exporting the content to MM Interface
 */
class ExporterController extends TwigAwareController implements BackendZoneInterface
{

    /** @var ContentRepository */
    private $contentRepository;

    /** @var RelationRepository */
    private $relationRepository;

    /**
     * Build the class
     *
     * @param ContentRepository         $contentRepository      The content repository
     * @param RelationRepository        $relationRepository     The relation repository
     */
    public function __construct(
        ContentRepository $contentRepository,
        RelationRepository $relationRepository
    )
    {
        $this->contentRepository = $contentRepository;
        $this->relationRepository = $relationRepository;
    }

    /**
     * @Route("/exporter/", name="app_exporter")
     */
    public function manage(): Response
    {
        $this->getData();
        return $this->render('backend/exporter/index.twig', []);
    }

    /**
     * Build a collection
     *
     * @param  Content    $content The content
     * @return Collection          The new collection or null
     */
    private function buildCollection(Content $content): Collection
    {
        if (!$content) {
            return null;
        }
        $image = $content->getFieldValue('image');
        $mediaTypes = $content->getTaxonomies('media_type');
        $mediaType = 'other';
        if (count($mediaTypes) > 0) {
            $mediaType = $mediaTypes[0]->getName();
        }
        $collection = new Collection(
            $content->getSlug(),
            $content->getFieldValue('title'),
            $content->getFieldValue('description'),
            $mediaType,
            $image['path']
        );
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $collection->addTag($tag->getName());
        }
        $categoryRelations = $this->relationRepository->findRelations($content, 'categories');
        if ($categoryRelations) {
            foreach ($categoryRelations as $related) {
                $category = $related->getToContent();
                $collection->addCategory($category->getFieldValue('name'));
            }
        }
        return $collection;
    }

    private function getData() {
        // Media associated with a collection
        $collections = [];
        // Media by itself
        $singles = [];
        $query = $this->contentRepository->findBy([
            'contentType'   =>  'media',
            'status'        =>  Statuses::PUBLISHED
        ]);
        foreach ($query as $media) {
            $collectionQuery = $this->relationRepository->findFirstRelation($media, 'collections');
            if ($collectionQuery) {
                $collection = $this->buildCollection($collectionQuery->getFromContent());
            }
        }
    }

}
