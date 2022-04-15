<?php

declare(strict_types=1);

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
     * The remote URL to download the image.
     *
     * @var string
     */
    public $imageUrl = '';

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
     * The remote URL for the resource
     *
     * @var string
     */
    public $resourceUrl = '';

    /**
     * The slug of the episode.
     *
     * @var string
     */
    public $slug = '';

    /**
     * An array of tags
     *
     * @var array
     */
    public $tags = [];

    /**
     * The title of the episode.
     *
     * @var string
     */
    public $title = '';

    /**
     * Build the Episode
     *
     * @param string $slug The slug for the Episode
     * @param string $title The title for the Episode
     * @param string $desc The description for the Episode
     * @param string $imageUrl The url for the image
     * @param string $mediaType The type of media
     * @param string $localFilename The local file's path
     * @param string $localImage The local image's path
     * @param string $resourceUrl The url for the resource
     */
    public function __construct(
        string $slug,
        string $title,
        string $desc,
        string $imageUrl,
        string $mediaType,
        string $localFilename,
        string $localImage,
        string $resourceUrl
    ) {
        if (! file_exists($localImage)) {
            throw new \InvalidArgumentException('The episode image does not exist!');
        }
        if (! file_exists($localFilename)) {
            throw new \InvalidArgumentException('The episode file does not exist!');
        }
        $this->slug = $slug;
        $this->title = $title;
        $this->desc = $desc;
        $this->imageUrl = $imageUrl;
        $this->mediaType = $mediaType;
        $this->localImage = $localImage;
        $this->image = basename($localImage);
        $this->localFilename = $localFilename;
        $this->filename = basename($localFilename);
        $this->mimeType = mime_content_type($localFilename);
        $this->resourceUrl = $resourceUrl;
    }

    /**
     * Add a tag
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
     * Get an array for this object
     *
     * @param bool $isMainFile Is this for the main file?
     * @param bool $isSlim Is this for the slim packaging?
     *
     * @return array The array of the object
     */
    public function asArray(bool $isMainFile = false, bool $isSlim = false): array
    {
        $data = [
            'desc' => $this->desc,
            'filename' => $this->filename,
            'image' => $this->image,
            'mediaType' => $this->mediaType,
            'mimeType' => $this->mimeType,
            'slug' => $this->slug,
            'tags' => $this->tags,
            'title' => $this->title,
        ];
        if ($isMainFile) {
            unset($data['desc']);
            unset($data['filename']);
            unset($data['image']);
            unset($data['mediaType']);
            unset($data['mimeType']);
            unset($data['slug']);
            unset($data['tags']);
        }
        if ($isSlim) {
            $data['imageUrl'] = $this->imageUrl;
            $data['resourceUrl'] = $this->resourceUrl;
        }

        return $data;
    }

    /**
     * Get a JSON string for this object
     *
     * @param bool $isMainFile Is this for the main file?
     * @param bool $isSlim Is this for the slim packaging?
     *
     * @return string The JSON string
     */
    public function asJson(bool $isMainFile = false, bool $isSlim = false): string
    {
        return json_encode($this->asArray($isMainFile, $isSlim), \JSON_UNESCAPED_UNICODE);
    }
}
