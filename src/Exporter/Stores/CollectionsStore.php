<?php
namespace App\Exporter\Stores;

use App\Exporter\Stores\BaseStore;
use App\Exporter\Models\Collection;
use App\Exporter\Models\Episode;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;

/**
 * A data store for collections
 */
class CollectionsStore extends BaseStore
{

    /**
     * Find all Collections
     *
     * @param   string          $locale     The locale to get content for (default: en)
     * @return  Array<Collection>           An array of collections
     */
    public function findAll($locale = 'en'): array
    {
        $collections = [];
        $this->currentLocale = $locale;
        $query = $this->contentRepository->findBy([
            'contentType'   =>  'collections',
            'status'        =>  Statuses::PUBLISHED
        ]);
        foreach ($query as $data) {
            $collection = $this->buildCollection($data);
            if (($collection) && (count($collection->episodes) > 0)) {
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
            $this->getTranslatedValue($content, 'title'),
            $this->getTranslatedValue($content, 'description'),
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
                $relatedContent = $related->getToContent();
                if ($relatedContent->getContentType() !== 'categories') {
                    /**
                     * Found a bug where getToContent() may return the collection. We need to check the from content.
                     */
                    $relatedContent = $related->getFromContent();
                    if ($relatedContent->getContentType() !== 'categories') {
                        continue;
                    }
                }
                if (!$this->hasTranslatedField($relatedContent, 'name')) {
                    continue;
                }
                $collection->addCategory($this->getTranslatedValue($relatedContent, 'name'));
            }
        }
        $episodeRelations = $this->relationRepository->findRelations($content, 'episodes');
        if ($episodeRelations) {
            foreach ($episodeRelations as $related) {
                $relatedContent = $related->getToContent();
                if ($relatedContent->getContentType() !== 'episodes') {
                    /**
                     * Found a bug where getToContent() may return the collection. We need to check the from content.
                     */
                    $relatedContent = $related->getFromContent();
                    if ($relatedContent->getContentType() !== 'episodes') {
                        continue;
                    }
                }
                $episode = $this->buildEpisode($relatedContent);
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
     * @return Episode          The episode|null
     * @access private
     */
    private function buildEpisode(Content $content)
    {
        if ((!$content) || (!$this->hasTranslation($content))) {
            return null;
        }
        $localImagePath = $this->getFileFieldPublicPath($content, 'image');
        $localFilePath = $this->getFileFieldPublicPath($content, 'file');
        $episode = new Episode(
            $content->getSlug(),
            $this->getTranslatedValue($content, 'title'),
            $this->getTranslatedValue($content, 'description'),
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

}
