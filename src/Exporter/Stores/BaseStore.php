<?php

declare(strict_types=1);

namespace App\Exporter\Stores;

use Bolt\Entity\Content;
use Bolt\Repository\ContentRepository;
use Bolt\Repository\RelationRepository;
use Webmozart\PathUtil\Path;

/**
 * The parent class for the stores
 */
class BaseStore
{
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
     * The directory for public files
     *
     * @var string
     */
    protected $publicDirectory = '';

    /**
     * The repository for retrieving related items
     *
     * @var ContentRepository
     */
    protected $relationRepository;

    /**
     * Build the store
     *
     * @param ContentRepository $contentRepository Bolt's Content Repository
     * @param RelationRepository $relationRepository Bolt's Related Repository
     * @param string $publicDirectory The public directory
     */
    public function __construct(
        ContentRepository $contentRepository,
        RelationRepository $relationRepository,
        string $publicDirectory
    ) {
        if (! file_exists($publicDirectory)) {
            throw new \InvalidArgumentException('The public directory does not exist!');
        }
        $this->contentRepository = $contentRepository;
        $this->relationRepository = $relationRepository;
        $this->publicDir = $publicDirectory;
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

        return Path::join($this->publicDir, $file['path']);
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
        if (\count($mediaTypes) > 0) {
            $mediaType = $mediaTypes[0]->getName();
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

        return '' !== $value['filename'];
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

        return '' !== $value;
    }
}
