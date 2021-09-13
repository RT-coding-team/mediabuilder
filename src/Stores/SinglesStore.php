<?php
namespace App\Stores;

use App\Models\Single;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Webmozart\PathUtil\Path;

/**
 * A data store for singles.
 */
class SinglesStore
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
     * Find all singles
     *
     * @return Array<Single>    An array of singles
     */
    public function findAll(): array
    {
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

    /**
     * Build a single
     *
     * @param  Content $content The content object
     * @return Single           The single
     * @access private
     */
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
