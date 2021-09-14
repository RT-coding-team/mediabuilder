<?php
namespace App\Exporter\Models;

use App\Exporter\Models\Episode;

/**
 * A collection model
 */
class Collection
{
    /**
     * An array of categories
     *
     * @var Array
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
     * @var Array
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
     * @var boolean
     */
    public $recommended = false;

    /**
     * The slug for the collection
     *
     * @var string
     */
    public $slug = '';

    /**
     * An array of tags for the collection
     *
     * @var Array
     */
    public $tags = [];

    /**
     * The title for the collection
     *
     * @var string
     */
    public $title = '';

    public function __construct(
        string $slug,
        string $title,
        string $desc,
        string $mediaType,
        string $localImage,
        $recommended = false
    )
    {
        if (!file_exists($localImage)) {
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
    public function addCategory(string $category)
    {
        if (!in_array($category, $this->categories)) {
            $this->categories[] = $category;
        }
    }

    /**
     * Add an episode to the collection
     *
     * @param Episode $episode The episode to add
     */
    public function addEpisode(Episode $episode) {
        $this->episodes[] = $episode;
    }

    /**
     * Add a tag to this collection
     *
     * @param string $tag The tag to add
     */
    public function addTag(string $tag)
    {
        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }
}
