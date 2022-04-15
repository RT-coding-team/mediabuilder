<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\Single;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;

/**
 * A data store for singles.
 */
class SinglesStore extends BaseContentStore
{
    /**
     * Add a package to the single
     *
     * @param string $slug The slug of the single you want to add the package from
     * @param string $packageSlug The slug of the package to add
     *
     * @return bool Was it added?
     */
    public function addPackage(string $slug, string $packageSlug): bool
    {
        return $this->addPackageTaxonomy('single', $slug, $packageSlug);
    }

    /**
     * Find all singles
     *
     * @param string $locale The locale to get content for (default: en)
     *
     * @return array an array of singles
     */
    public function findAll($locale = 'en'): array
    {
        $singles = [];
        $this->currentLocale = $locale;
        $query = $this->contentRepository->findBy([
            'contentType' => 'singles',
            'status' => Statuses::PUBLISHED,
        ]);
        foreach ($query as $data) {
            $single = $this->buildSingle($data);
            if ($single) {
                $singles[] = $single;
            }
        }
        usort($singles, fn ($a, $b) => strcmp($a->title, $b->title));

        return $singles;
    }

    /**
     * Find the single based on the given slug
     *
     * @param string $slug The slug of the single
     *
     * @return ?Single null|The requested single
     */
    public function findBySlug(string $slug): ?Single
    {
        $contentType = $this->getContentType('single');
        if (! $contentType) {
            return null;
        }
        $content = $this->contentRepository->findOneBySlug($slug, $contentType);

        return $this->buildSingle($content);
    }

    /**
     * Remove the package from the the given single
     *
     * @param string $slug The slug of the single you want to remove the package from
     * @param string $packageSlug The slug of the package to remove
     *
     * @return bool Was it successful?
     */
    public function removePackage(string $slug, string $packageSlug): bool
    {
        return $this->removePackageTaxonomy('single', $slug, $packageSlug);
    }

    /**
     * Build a single
     *
     * @param Content $content The content object
     *
     * @return Single The single|null if not Translatable
     */
    private function buildSingle(Content $content): ?Single
    {
        if (! $content || (! $this->hasTranslation($content))) {
            return null;
        }
        $recommendedValue = $content->getFieldValue('recommended');
        $recommended = ('yes' === $recommendedValue);
        $localImagePath = $this->getFileFieldPublicPath($content, 'image');
        $imageUrl = $this->getFileFieldPublicUrl($content, 'image');
        $localFilePath = $this->getFileFieldPublicPath($content, 'file');
        $resourceUrl = $this->getFileFieldPublicUrl($content, 'file');
        $single = new Single(
            $content->getSlug(),
            $this->getTranslatedValue($content, 'title'),
            $this->getTranslatedValue($content, 'description'),
            $imageUrl,
            $this->getMediaType($content),
            $localFilePath,
            $localImagePath,
            $resourceUrl,
            $recommended
        );
        $packages = $content->getTaxonomies('packages');
        foreach ($packages as $package) {
            $single->addPackage($package->getSlug());
        }
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $single->addTag($tag->getName());
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
                $single->addCategory($this->getTranslatedValue($relatedContent, 'name'));
            }
        }

        return $single;
    }
}
