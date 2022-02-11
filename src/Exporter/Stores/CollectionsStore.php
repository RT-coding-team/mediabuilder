<?php

declare(strict_types=1);

namespace App\Exporter\Stores;

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
     * @param string $locale The locale to get content for (default: en)
     *
     * @return array An array of collections
     */
    public function findAll($locale = 'en'): array
    {
        $collections = [];
        $this->currentLocale = $locale;
        $query = $this->contentRepository->findBy([
            'contentType' => 'collections',
            'status' => Statuses::PUBLISHED,
        ]);
        foreach ($query as $data) {
            $collection = $this->buildCollection($data);
            if ($collection && (\count($collection->episodes) > 0)) {
                $collections[] = $collection;
            }
        }

        return $collections;
    }

    /**
     * Build a collection
     *
     * @param Content $content The content
     *
     * @return Collection The new collection or null
     */
    private function buildCollection(Content $content): ?Collection
    {
        if (! $content || (! $this->hasTranslatedField($content, 'title'))) {
            return null;
        }
        $localImagePath = $this->getFileFieldPublicPath($content, 'image');
        $imageUrl = $this->getFileFieldPublicUrl($content, 'image');
        $recommendedValue = $content->getFieldValue('recommended');
        $recommended = ('yes' === $recommendedValue);
        $collection = new Collection(
            $content->getSlug(),
            $this->getTranslatedValue($content, 'title'),
            $this->getTranslatedValue($content, 'description'),
            $imageUrl,
            $this->getMediaType($content),
            $localImagePath,
            $recommended
        );
        $packages = $content->getTaxonomies('packages');
        foreach ($packages as $package) {
            $collection->addPackage($package->getSlug());
        }
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $collection->addTag($tag->getName());
        }
        $categoryRelations = $this->relationRepository->findRelations($content, 'categories');
        if ($categoryRelations) {
            foreach ($categoryRelations as $related) {
                $relatedContent = $related->getToContent();
                if ('categories' !== $relatedContent->getContentType()) {
                    /**
                     * Found a bug where getToContent() may return the collection. We need to check the from content.
                     */
                    $relatedContent = $related->getFromContent();
                    if ('categories' !== $relatedContent->getContentType()) {
                        continue;
                    }
                }
                if (! $this->hasTranslatedField($relatedContent, 'name')) {
                    continue;
                }
                $collection->addCategory($this->getTranslatedValue($relatedContent, 'name'));
            }
        }
        $episodeRelations = $this->relationRepository->findRelations($content, 'episodes');
        if ($episodeRelations) {
            foreach ($episodeRelations as $related) {
                $relatedContent = $related->getToContent();
                if ('episodes' !== $relatedContent->getContentType()) {
                    /**
                     * Found a bug where getToContent() may return the collection. We need to check the from content.
                     */
                    $relatedContent = $related->getFromContent();
                    if ('episodes' !== $relatedContent->getContentType()) {
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
     * @param Content $content The episode content
     *
     * @return Episode The episode|null
     */
    private function buildEpisode(Content $content): ?Episode
    {
        if (! $content || (! $this->hasTranslation($content))) {
            return null;
        }
        $localImagePath = $this->getFileFieldPublicPath($content, 'image');
        $imageUrl = $this->getFileFieldPublicUrl($content, 'image');
        $localFilePath = $this->getFileFieldPublicPath($content, 'file');
        $resourceUrl = $this->getFileFieldPublicUrl($content, 'file');
        $episode = new Episode(
            $content->getSlug(),
            $this->getTranslatedValue($content, 'title'),
            $this->getTranslatedValue($content, 'description'),
            $imageUrl,
            $this->getMediaType($content),
            $localFilePath,
            $localImagePath,
            $resourceUrl
        );
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $episode->addTag($tag->getName());
        }

        return $episode;
    }
}
