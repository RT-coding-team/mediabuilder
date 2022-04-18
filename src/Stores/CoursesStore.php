<?php

declare(strict_types=1);

namespace App\Stores;

use App\Models\Course;
use Bolt\Entity\Content;
use Bolt\Enum\Statuses;

/**
 * A data store for retrieving the current courses.
 */
class CoursesStore extends BaseContentStore
{
    /**
     * Find all the Courses
     *
     * @return array An array of courses
     */
    public function findAll(): array
    {
        $courses = [];
        $query = $this->contentRepository->findBy([
            'contentType' => 'courses',
            'status' => Statuses::PUBLISHED,
        ]);
        foreach ($query as $data) {
            $course = $this->buildCourse($data);
            $courses[] = $course;
        }

        return $courses;
    }

    /**
     * Build the course
     *
     * @param Content $content The content object
     *
     * @return Course The course|null
     */
    private function buildCourse(Content $content): ?Course
    {
        if (! $content) {
            return null;
        }
        $description = $content->getFieldValue('description');
        $fileInfo = $content->getFieldValue('file');
        $language = $content->getFieldValue('language');
        $title = $content->getFieldValue('title');
        $course = new Course(
            $description,
            $fileInfo['path'],
            $title,
            $language
        );
        $tags = $content->getTaxonomies('tags');
        foreach ($tags as $tag) {
            $course->addTag($tag->getName());
        }

        return $course;
    }
}
