<?php

declare(strict_types=1);

namespace App\Stores;

use Bolt\Configuration\Config;
use Bolt\Configuration\Content\ContentType;
use Bolt\Entity\Content;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Bolt\Repository\TaxonomyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Webmozart\PathUtil\Path;

/**
 * The parent class for the Content based stores
 */
class BaseContentStore
{
    /**
     * The url for the site.
     *
     * @var string
     */
    public $siteUrl = '';

    /**
     * Bolt's configuration class
     *
     * @var Config
     */
    protected $boltConfig = null;

    /**
     * The repository for retrieving content
     *
     * @var ContentRepository
     */
    protected $contentRepository = null;

    /**
     * The current locale
     *
     * @var string
     */
    protected $currentLocale = 'en';

    /**
     * Doctrine's entity manager
     *
     * @var EntityManagerInterface
     */
    protected $entityManager = null;

    /**
     * The repository for retrieving related items
     *
     * @var ContentRepository
     */
    protected $relationRepository;

    /**
     * The repository for retrieving taxonomy
     *
     * @var TaxonomyRepository
     */
    protected $taxonomyRepository;

    /**
     * Build the store
     *
     * @param Config $config Bolt's configuration class
     * @param ContentRepository $contentRepository Bolt's Content Repository
     * @param EntityManagerInterface $entityManager Doctrine's entity manager
     * @param RelationRepository $relationRepository Bolt's Related Repository
     * @param TaxonomyRepository $taxonomyRepository Bolt's Taxonomy Repository
     */
    public function __construct(
        Config $config,
        ContentRepository $contentRepository,
        EntityManagerInterface $entityManager,
        RelationRepository $relationRepository,
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->boltConfig = $config;
        $this->contentRepository = $contentRepository;
        $this->entityManager = $entityManager;
        $this->relationRepository = $relationRepository;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    /**
     * Add a package to the content
     *
     * @param string $contentType The content type of the content (valid: collection or single)
     * @param string $slug The slug of the content you want to add the package from
     * @param string $packageSlug The slug of the package to add
     *
     * @return bool Was it added?
     */
    protected function addPackageTaxonomy(string $contentType, string $slug, string $packageSlug): bool
    {
        $validTypes = ['collection', 'single'];
        if (! \in_array($contentType, $validTypes, true)) {
            return false;
        }
        $contentType = $this->getContentType($contentType);
        if (! $contentType) {
            return false;
        }
        $content = $this->contentRepository->findOneBySlug($slug, $contentType);
        if (! $content) {
            return false;
        }
        $taxonomy = $this->taxonomyRepository->findOneBy([
            'type' => 'packages',
            'slug' => $packageSlug,
        ]);
        if (! $taxonomy) {
            return false;
        }
        $content->addTaxonomy($taxonomy);
        $this->entityManager->persist($content);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Get the content type for the store
     *
     * @param string $slug The slug
     *
     * @return ?ContentType The content type
     */
    protected function getContentType(string $slug): ?ContentType
    {
        return ContentType::factory($slug, $this->boltConfig->get('contenttypes'));
    }

    /**
     * Get the public file for a file field
     *
     * @param Content $content The content
     * @param string $fieldName The field name
     *
     * @return string The path to the public file
     */
    protected function getFileFieldPublicPath(Content $content, string $fieldName): string
    {
        $file = $this->getTranslatedValue($content, $fieldName);
        $publicPath = Path::canonicalize(\dirname(__DIR__, 2).'/public/');

        return Path::join($publicPath, $file['path']);
    }

    /**
     * Get the public url for a file field
     *
     * @param Content $content The content
     * @param string $fieldName The field name
     *
     * @return string The url to the public file
     */
    protected function getFileFieldPublicUrl(Content $content, string $fieldName): string
    {
        if (empty($this->siteUrl)) {
            return '';
        }
        $file = $this->getTranslatedValue($content, $fieldName);

        return Path::join($this->siteUrl, $file['path']);
    }

    /**
     * get the media type
     *
     * @param Content $content The content
     *
     * @return string The type of media
     */
    protected function getMediaType(Content $content): string
    {
        $mediaTypes = $content->getTaxonomies('media_type');
        $mediaType = 'other';
        if ($mediaTypes->count() > 0) {
            $mediaType = $mediaTypes->first()->getName();
        }

        return $mediaType;
    }

    /**
     * Get the translated value based on the currentLocale
     *
     * @param Content $content The content
     * @param string $fieldName The field name
     *
     * @return mixed The value
     */
    protected function getTranslatedValue(Content $content, string $fieldName)
    {
        $field = $content->getField($fieldName);
        $field->setLocale($this->currentLocale);

        return $field->getParsedValue();
    }

    /**
     * Check whether the content is available in the current locale. If the file
     * is not set, then there is no point to add the single.
     *
     * @param Content $content The content
     *
     * @return bool yes|no
     */
    protected function hasTranslation(Content $content): bool
    {
        $field = $content->getField('file');
        $field->setUseDefaultLocale(false);
        $field->setLocale($this->currentLocale);
        $value = $field->getParsedValue();

        return $value && (\array_key_exists('filename', $value)) && ('' !== $value['filename']);
    }

    /**
     * Check whether the content is available in the current locale. The value must be a string.
     *
     * @param Content $content The content
     * @param string $fieldName The field name
     *
     * @return bool yes|no
     */
    protected function hasTranslatedField(Content $content, string $fieldName): bool
    {
        $field = $content->getField($fieldName);
        $field->setUseDefaultLocale(false);
        $field->setLocale($this->currentLocale);
        $value = $field->getParsedValue();

        return $value && ('' !== $value);
    }

    /**
     * Remove the package from the the given content
     *
     * @param string $contentType The content type of the content (valid: collection or single)
     * @param string $slug The slug of the content you want to remove the package from
     * @param string $packageSlug The slug of the package to remove
     *
     * @return bool Was it successful?
     */
    protected function removePackageTaxonomy(string $contentType, string $slug, string $packageSlug): bool
    {
        $validTypes = ['collection', 'single'];
        if (! \in_array($contentType, $validTypes, true)) {
            return false;
        }
        $contentType = $this->getContentType($contentType);
        if (! $contentType) {
            return false;
        }
        $content = $this->contentRepository->findOneBySlug($slug, $contentType);
        if (! $content) {
            return false;
        }
        $packageTaxonomy = null;
        $packages = $content->getTaxonomies('packages');
        foreach ($packages as $package) {
            if ($package->getSlug() === $packageSlug) {
                $packageTaxonomy = $package;
                break;
            }
        }
        if (! $packageTaxonomy) {
            return false;
        }
        $content->removeTaxonomy($packageTaxonomy);
        $this->entityManager->persist($content);
        $this->entityManager->flush();

        return true;
    }
}
