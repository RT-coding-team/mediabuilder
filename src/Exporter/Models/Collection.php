<?php

declare(strict_types=1);

namespace App\Exporter\Models;

/**
 * A collection model
 */
class Collection
{
    /**
     * An array of categories (needs to stay public to json serialize correctly)
     *
     * @var array
     */
    public $categories = [];

    /**
     * The description of the collection
     *
     * @var string
     */
    public $desc = '';

    /**
     * An array of Media for the collection
     *
     * @var array
     */
    public $episodes = [];

    /**
     * The image of the collection
     *
     * @var string
     */
    public $image = '';

    /**
     * The path to the local image of the collection
     *
     * @var string
     */
    public $localImage = '';

    /**
     * The media type of the collection
     *
     * @var string
     */
    public $mediaType = '';

    /**
     * Is it recommended?
     *
     * @var bool
     */
    public $recommended = false;

    /**
     * The slug for the collection
     *
     * @var string
     */
    public $slug = '';

    /**
     * An array of tags for the collection (needs to stay public to json serialize correctly)
     *
     * @var array
     */
    public $tags = [];

    /**
     * The title for the collection
     *
     * @var string
     */
    public $title = '';

    /**
     * An array of packages that the collection belongs to
     *
     * @var array
     */
    private $packages = [];

    /**
     * Build the Collection
     *
     * @param string $slug The slug for the Collection
     * @param string $title The title for the Collection
     * @param string $desc The description for the Collection
     * @param string $mediaType The type of media for the Collection
     * @param string $localImage The path to the local image
     * @param bool $recommended Is it a recommended collection? (default: false)
     */
    public function __construct(
        string $slug,
        string $title,
        string $desc,
        string $mediaType,
        string $localImage,
        $recommended = false
    ) {
        if (! file_exists($localImage)) {
            throw new \InvalidArgumentException('The collection image does not exist!');
        }
        $this->slug = $slug;
        $this->title = $title;
        $this->desc = $desc;
        $this->mediaType = $mediaType;
        $this->localImage = $localImage;
        $this->image = basename($localImage);
        $this->recommended = $recommended;
    }

    /**
     * Add a category to this collection
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
     * Add an episode to the collection
     *
     * @param Episode $episode The episode to add
     */
    public function addEpisode(Episode $episode): void
    {
        $this->episodes[] = $episode;
    }

    /**
     * Add a package to this collection
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
     * Add a tag to this collection
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
