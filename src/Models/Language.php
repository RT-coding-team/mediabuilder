<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A language model
 */
class Language
{
    /**
     * A list of codes that get this translation
     *
     * @var array
     */
    public $codes = [];

    /**
     * Is this the default language?
     *
     * @var bool
     */
    public $default = false;

    /**
     * The text of the language
     *
     * @var string
     */
    public $text = '';

    /**
     * Build the Language
     *
     * @param array $codes An array of language codes for this language
     * @param string $text The display text of the language
     * @param bool $default Is this the default language? (default: false)
     */
    public function __construct(
        array $codes,
        string $text,
        $default = false
    ) {
        $this->codes = $codes;
        $this->text = $text;
        $this->default = $default;
    }
}
