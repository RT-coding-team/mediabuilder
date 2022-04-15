<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A package model
 */
class Package
{
    /**
     * The slug of the package.
     *
     * @var string
     */
    public $slug = '';

    /**
     * The title of the package.
     *
     * @var string
     */
    public $name = '';

    /**
     * The collections for this package keyed with the bolt_locale_code to
     * group by language.
     *
     * @var array
     */
    private $collections = [];

    /**
     * The singles for this package keyed with the bolt_locale_code to
     * group by language.
     *
     * @var array
     */
    private $singles = [];

    /**
     * A list of the locales that have either a single or collection.
     *
     * @var array
     */
    private $supportedLocales = [];

    /**
     * Build a package
     *
     * @param string $name The package name
     * @param string $slug The package slug
     */
    public function __construct(
        string $name,
        string $slug
    ) {
        $this->slug = $slug;
        $this->name = $name;
    }

    /**
     * Add a collection to this package.
     *
     * @param string $localCode Bolt's locale code
     * @param Collection $collection The collection to add
     */
    public function addCollection(string $localCode, Collection $collection): void
    {
        if (! isset($this->collections[$localCode])) {
            $this->collections[$localCode] = [];
            $this->addSupportedLocale($localCode);
        }
        $this->collections[$localCode][] = $collection;
    }

    /**
     * Add a single to this package.
     *
     * @param string $localCode Bolt's locale code
     * @param Collection $single The single to add
     */
    public function addSingle(string $localCode, Single $single): void
    {
        if (! isset($this->singles[$localCode])) {
            $this->singles[$localCode] = [];
            $this->addSupportedLocale($localCode);
        }
        $this->singles[$localCode][] = $single;
    }

    /**
     * Get all the collections for a specific locale
     *
     * @param string $localCode Bolt's locale code
     *
     * @return array The collections
     */
    public function getCollectionsByLocale(string $localCode): array
    {
        $collections = [];
        if (isset($this->collections[$localCode])) {
            $collections = $this->collections[$localCode];
        }

        return $collections;
    }

    /**
     * Get all the sgnles for a specific locale
     *
     * @param string $localCode Bolt's locale code
     *
     * @return array The singles
     */
    public function getSinglesByLocale(string $localCode): array
    {
        $singles = [];
        if (isset($this->singles[$localCode])) {
            $singles = $this->singles[$localCode];
        }

        return $singles;
    }

    /**
     * Do we have collections and/or singles for this locale?
     *
     * @param string $localeCode Bolt's locale code
     *
     * @return bool yes|no
     */
    public function hasContentForLocale(string $localeCode): bool
    {
        return \in_array($localeCode, $this->supportedLocales, true);
    }

    /**
     * Does this package have resources?
     *
     * @return bool yes|no
     */
    public function isEmpty(): bool
    {
        return empty($this->collections) && (empty($this->singles));
    }

    /**
     * Add to the supported locales if not present already
     *
     * @param string $localeCode Bolt's locale code
     */
    private function addSupportedLocale(string $localeCode): void
    {
        if (! \in_array($localeCode, $this->supportedLocales, true)) {
            $this->supportedLocales[] = $localeCode;
        }
    }
}
