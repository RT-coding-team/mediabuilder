<?php
namespace App\Exporter\Models;

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
     * @var boolean
     */
    public $default = false;

    /**
     * The text of the language
     *
     * @var string
     */
    public $text = '';

    public function __construct(
        array $codes,
        string $text,
        $default = false
    )
    {
        $this->codes = $codes;
        $this->text = $text;
        $this->default = $default;
    }
}
