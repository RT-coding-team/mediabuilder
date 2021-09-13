<?php
namespace App;

use App\Models\Collection;
use App\Models\Episode;
use App\Models\Single;
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
        $singles = $this->getSingles();
        dd($singles);
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
        $localImagePath = $this->getFileFieldPublicPath($content, 'image');
        $recommendedValue = $content->getFieldValue('recommended');
        $recommended = ($recommendedValue == 'yes') ? true : false;
        $collection = new Collection(
            $content->getSlug(),
            $content->getFieldValue('title'),
            $content->getFieldValue('description'),
            $this->getMediaType($content),
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
        $localImagePath = $this->getFileFieldPublicPath($content, 'image');
        $localFilePath = $this->getFileFieldPublicPath($content, 'file');
        $episode = new Episode(
            $content->getSlug(),
            $content->getFieldValue('title'),
            $content->getFieldValue('description'),
            $this->getMediaType($content),
            $localFilePath,
            $localImagePath
        );
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $episode->addTag($tag->getName());
        }
        return $episode;
    }

    private function buildSingle(Content $content): Single
    {
        if (!$content) {
            return null;
        }
        $recommendedValue = $content->getFieldValue('recommended');
        $recommended = ($recommendedValue == 'yes') ? true : false;
        $localImagePath = $this->getFileFieldPublicPath($content, 'image');
        $localFilePath = $this->getFileFieldPublicPath($content, 'file');
        $single = new Single(
            $content->getSlug(),
            $content->getFieldValue('title'),
            $content->getFieldValue('description'),
            $this->getMediaType($content),
            $localFilePath,
            $localImagePath,
            $recommended
        );
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $single->addTag($tag->getName());
        }
        $categoryRelations = $this->relationRepository->findRelations($content, 'categories');
        if ($categoryRelations) {
            foreach ($categoryRelations as $related) {
                $category = $related->getToContent();
                $single->addCategory($category->getFieldValue('name'));
            }
        }
        return $single;
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

    /**
     * Get the public file for a file field
     *
     * @param  Content $content   The content
     * @param  string  $fieldName The field name
     * @return string             The path to the public file
     * @access private
     */
    private function getFileFieldPublicPath(Content $content, string $fieldName): string
    {
        $file = $content->getFieldValue($fieldName);
        return Path::join($this->publicDir, $file['path']);
    }

    /**
     * get the media type
     *
     * @param  Content $content The content
     * @return string           The type of media
     * @access private
     */
    private function getMediaType(Content $content): string
    {
        $mediaTypes = $content->getTaxonomies('media_type');
        $mediaType = 'other';
        if (count($mediaTypes) > 0) {
            $mediaType = $mediaTypes[0]->getName();
        }
        return $mediaType;
    }


    /**
     * Get all the singles
     *
     * @return Array<Single>    An array of singles
     */
    private function getSingles(): array {
        $singles = [];
        $query = $this->contentRepository->findBy([
            'contentType'   =>  'singles',
            'status'        =>  Statuses::PUBLISHED
        ]);
        foreach ($query as $data) {
            $single = $this->buildSingle($data);
            if ($single) {
                $singles[] = $single;
            }
        }
        return $singles;
    }

}
