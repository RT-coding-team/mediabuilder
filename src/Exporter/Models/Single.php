<?php
namespace App\Exporter\Models;

/**
 * A single model
 */
class Single
{
    /**
     * An array of categories
     *
     * @var Array
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
     * @var boolean
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
     * @var Array
     */
    public $tags = [];

    /**
     * The title for the single
     *
     * @var string
     */
    public $title = '';

    public function __construct(
        string $slug,
        string $title,
        string $desc,
        string $mediaType,
        string $localFilename,
        string $localImage,
        $recommended = false
    )
    {
        if (!file_exists($localImage)) {
            throw new \InvalidArgumentException('The single image does not exist!');
        }
        if (!file_exists($localFilename)) {
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
    public function addCategory(string $category)
    {
        if (!in_array($category, $this->categories)) {
            $this->categories[] = $category;
        }
    }

    /**
     * Add a tag to this single
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