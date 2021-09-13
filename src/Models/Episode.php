<?php
namespace App\Models;

/**
 * A episode model
 */
class Episode
{
    /**
     * The description of the episode.
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
     * The media type of the episode.
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
     * The slug of the episode.
     *
     * @var string
     */
    public $slug = '';

    /**
     * An array of tags
     *
     * @var Array
     */
    public $tags = [];

    /**
     * The title of the episode.
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
        string $localFilename
    )
    {
        if (!file_exists($localImage)) {
            throw new \InvalidArgumentException('The episode image does not exist!');
        }
        if (!file_exists($localFilename)) {
            throw new \InvalidArgumentException('The episode file does not exist!');
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
    }

    /**
     * Add a tag
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
