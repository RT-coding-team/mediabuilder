<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A course model
 */
class Course
{
    /**
     * The title for the course.
     *
     * @var string
     */
    public $title = '';

    /**
     * The language for the course
     *
     * @var string
     */
    public $language = '';

    /**
     * The description for the course
     *
     * @var string
     */
    public $description = '';

    /**
     * The file path to the file
     *
     * @var string
     */
    public $filePath = null;

    /**
     * A friendly string of the tags.
     *
     * @var string
     */
    public $prettyTags = '[]';

    /**
     * Tags the course is related to
     *
     * @var array
     */
    private $tags = [];

    /**
     * Build a course
     *
     * @param string $description The description of the course
     * @param string $filePath The path to the file of the course
     * @param string $title The title of the course
     * @param string $language The language of the course
     */
    public function __construct(
        string $description,
        string $filePath,
        string $title,
        $language = ''
    ) {
        $this->description = $description;
        $this->filePath = $filePath;
        $this->title = $title;
        $this->language = $language;
    }

    /**
     * Add a tag to the course
     *
     * @param string $tag The tag to add
     */
    public function addTag(string $tag): void
    {
        if (! \in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }
        $this->setPrettyTags();
    }

    /**
     * Get all the tags for the course
     *
     * @return array the tags
     */
    private function setPrettyTags(): void
    {
        if (0 === \count($this->tags)) {
            $this->prettyTags = '[]';

            return;
        }
        $tags = array_map(function ($tag) {
            return '"'.$tag.'"';
        }, $this->tags);
        $this->prettyTags = '['.implode(',', $tags).']';
    }
}
