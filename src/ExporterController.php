<?php
namespace App;

use App\Models\Collection;
use App\Models\Episode;
use Symfony\Component\Routing\Annotation\Route;
use Bolt\Controller\Backend\BackendZoneInterface;
use Bolt\Controller\TwigAwareController;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\PathUtil\Path;

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
        $this->publicDir = Path::canonicalize(dirname(__DIR__, 1) . '/public/');
    }

    /**
     * @Route("/exporter/", name="app_exporter")
     */
    public function manage(): Response
    {
        $collections = $this->getCollections();
        dd($collections);
        return $this->render('backend/exporter/index.twig', []);
    }

    /**
     * Build a collection
     *
     * @param  Content    $content The content
     * @return Collection          The new collection or null
     * @access private
     */
    private function buildCollection(Content $content): Collection
    {
        if (!$content) {
            return null;
        }
        $image = $content->getFieldValue('image');
        $localImagePath = Path::join($this->publicDir, $image['path']);
        $mediaTypes = $content->getTaxonomies('media_type');
        $mediaType = 'other';
        if (count($mediaTypes) > 0) {
            $mediaType = $mediaTypes[0]->getName();
        }
        $recommendedValue = $content->getFieldValue('recommended');
        $recommended = ($recommendedValue == 'yes') ? true : false;
        $collection = new Collection(
            $content->getSlug(),
            $content->getFieldValue('title'),
            $content->getFieldValue('description'),
            $mediaType,
            $localImagePath,
            $recommended
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
        $episodeRelations = $this->relationRepository->findRelations($content, 'episodes');
        if ($episodeRelations) {
            foreach ($episodeRelations as $related) {
                $episode = $this->buildEpisode($related->getToContent());
                if ($episode) {
                    $collection->addEpisode($episode);
                }
            }
        }
        return $collection;
    }

    /**
     * Build a single episode.
     *
     * @param  Content $content The episode content
     * @return Episode          The episode
     * @access private
     */
    private function buildEpisode(Content $content): Episode
    {
        if (!$content) {
            return null;
        }
        $image = $content->getFieldValue('image');
        $localImagePath = Path::join($this->publicDir, $image['path']);
        $file = $content->getFieldValue('file');
        $localFilePath = Path::join($this->publicDir, $file['path']);
        $mediaTypes = $content->getTaxonomies('media_type');
        $mediaType = 'other';
        if (count($mediaTypes) > 0) {
            $mediaType = $mediaTypes[0]->getName();
        }
        $episode = new Episode(
            $content->getSlug(),
            $content->getFieldValue('title'),
            $content->getFieldValue('description'),
            $mediaType,
            $localImagePath,
            $localFilePath
        );
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $episode->addTag($tag->getName());
        }
        return $episode;
    }

    /**
     * Get all existing collections
     *
     * @return Array<Collection>    An array of Collections
     * @access private
     */
    private function getCollections(): array {
        $collections = [];
        $query = $this->contentRepository->findBy([
            'contentType'   =>  'collections',
            'status'        =>  Statuses::PUBLISHED
        ]);
        foreach ($query as $data) {
            $collection = $this->buildCollection($data);
            if ($collection) {
                $collections[] = $collection;
            }
        }
        return $collections;
    }

}
