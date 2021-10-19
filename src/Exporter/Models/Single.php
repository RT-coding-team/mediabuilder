<?php

declare(strict_types=1);

namespace App\Exporter\Models;

/**
 * A single model
 */
class Single
{
    /**
     * An array of categories (needs to stay public to json serialize correctly)
     *
     * @var array
     */
    public $categories = [];

    /**
     * The description of the single
     *
     * @var string
     */
    public $desc = '';

    /**
     * The media file
     *
     * @var string
     */
    public $filename = '';

    /**
     * The path to the local media file
     *
     * @var string
     */
    public $localFilename = '';

    /**
     * The image of the single
     *
     * @var string
     */
    public $image = '';

    /**
     * The path to the local image of the single
     *
     * @var string
     */
    public $localImage = '';

    /**
     * The media type of the single
     *
     * @var string
     */
    public $mediaType = '';

    /**
     * The mime type of the file
     *
     * @var string
     */
    public $mimeType = '';

    /**
     * Is it recommended?
     *
     * @var bool
     */
    public $recommended = false;

    /**
     * The slug for the single
     *
     * @var string
     */
    public $slug = '';

    /**
     * An array of tags for the single
     *
     * @var array
     */
    public $tags = [];

    /**
     * The title for the single
     *
     * @var string
     */
    public $title = '';

    /**
     * An array of packages this single belongs to.
     *
     * @var array
     */
    private $packages = [];

    /**
     * Build a Single
     *
     * @param string $slug The slug
     * @param string $title The title
     * @param string $desc The description
     * @param string $mediaType The type of media
     * @param string $localFilename The path to the local file
     * @param string $localImage The path to the local image
     * @param bool $recommended Is it recommended? (default: false)
     */
    public function __construct(
        string $slug,
        string $title,
        string $desc,
        string $mediaType,
        string $localFilename,
        string $localImage,
        $recommended = false
    ) {
        if (! file_exists($localImage)) {
            throw new \InvalidArgumentException('The single image does not exist!');
        }
        if (! file_exists($localFilename)) {
            throw new \InvalidArgumentException('The single file does not exist!');
        }
        $this->slug = $slug;
        $this->title = $title;
        $this->desc = $desc;
        $this->mediaType = $mediaType;
        $this->localImage = $localImage;
        $this->image = basename($localImage);
        $this->localFilename = $localFilename;
        $this->filename = basename($localFilename);
        $this->mimeType = mime_content_type($localFilename);
        $this->recommended = $recommended;
    }

    /**
     * Add a category to this single
     *
     * @param string $category The category to add
     */
    public function addCategory(string $category): void
    {
        if (! \in_array($category, $this->categories, true)) {
            $this->categories[] = $category;
        }
    }

    /**
     * Add a package to this single
     *
     * @param string $package The package slug to add
     */
    public function addPackage(string $package): void
    {
        if (! \in_array($package, $this->packages, true)) {
            $this->packages[] = $package;
        }
    }

    /**
     * Add a tag to this single
     *
     * @param string $tag The tag to add
     */
    public function addTag(string $tag): void
    {
        if (! \in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }
    }

    /**
     * Do we belong to the given package?
     *
     * @param string $packageSlug The slug to check
     *
     * @return bool yes|no
     */
    public function belongsTo(string $packageSlug): bool
    {
        return \in_array($packageSlug, $this->packages, true);
    }
}
