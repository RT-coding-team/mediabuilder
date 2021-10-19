<?php

declare(strict_types=1);

namespace App\Exporter\Stores;

use App\Exporter\Models\Single;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;

/**
 * A data store for singles.
 */
class SinglesStore extends BaseStore
{
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

        return $singles;
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
        $localFilePath = $this->getFileFieldPublicPath($content, 'file');
        $single = new Single(
            $content->getSlug(),
            $this->getTranslatedValue($content, 'title'),
            $this->getTranslatedValue($content, 'description'),
            $this->getMediaType($content),
            $localFilePath,
            $localImagePath,
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
