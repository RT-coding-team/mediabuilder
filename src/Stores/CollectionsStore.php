<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\Collection;
use App\Models\Episode;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;

/**
 * A data store for collections
 */
class CollectionsStore extends BaseContentStore
{
    /**
     * Add a package to the collection
     *
     * @param string $slug The slug of the collection you want to add the package from
     * @param string $packageSlug The slug of the package to add
     *
     * @return bool Was it added?
     */
    public function addPackage(string $slug, string $packageSlug): bool
    {
        return $this->addPackageTaxonomy('collection', $slug, $packageSlug);
    }

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
        usort($collections, fn ($a, $b) => strcmp($a->title, $b->title));

        return $collections;
    }

    /**
     * Find the collection based on the given slug
     *
     * @param string $slug The slug of the collection
     *
     * @return ?Collection null|The requested collection
     */
    public function findBySlug(string $slug): ?Collection
    {
        $contentType = $this->getContentType('collection');
        if (! $contentType) {
            return null;
        }
        $content = $this->contentRepository->findOneBySlug($slug, $contentType);

        return $this->buildCollection($content);
    }

    /**
     * Remove the package from the the given collection
     *
     * @param string $slug The slug of the collection you want to remove the package from
     * @param string $packageSlug The slug of the package to remove
     *
     * @return bool Was it successful?
     */
    public function removePackage(string $slug, string $packageSlug): bool
    {
        return $this->removePackageTaxonomy('collection', $slug, $packageSlug);
    }

    /**
     * Build a collection
     *
     * @param Content $content The content
     *
     * @return Collection The new collection or null
     */
    private function buildCollection(?Content $content): ?Collection
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
