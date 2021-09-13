<?php
namespace App\Stores;

use App\Models\Collection;
use App\Models\Episode;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Webmozart\PathUtil\Path;

/**
 * A data store for collections
 */
class CollectionsStore
{

    /**
     * The repository for retrieving content
     *
     * @var ContentRepository
     */
    private $contentRepository = null;

    /**
     * The directory for public files
     *
     * @var string
     */
    private $publicDirectory = '';

    /**
     * The repository for retrieving related items
     * @var ContentRepository
     */
    private $relationRepository;

    public function __construct(
        ContentRepository $contentRepository,
        RelationRepository $relationRepository,
        string $publicDirectory
    )
    {
        if (!file_exists($publicDirectory)) {
            throw new \InvalidArgumentException('The public directory does not exist!');
        }
        $this->contentRepository = $contentRepository;
        $this->relationRepository = $relationRepository;
        $this->publicDir = $publicDirectory;
    }

    /**
     * Find all Collections
     *
     * @return array An array of collections
     */
    public function findAll(): array
    {
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
}
