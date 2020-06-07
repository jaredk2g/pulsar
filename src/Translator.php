<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

use Pulsar\Interfaces\TranslatorInterface;

class Translator implements TranslatorInterface
{
    /**
     * @var string
     */
    private $locale = 'en';

    /**
     * @var string
     */
    private $dataDir;

    /**
     * @var array
     */
    private $data = [];

    public function __construct(?string $locale = null)
    {
        if ($locale) {
            $this->locale = $locale;
        }
    }

    /**
     * Sets the locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Gets the locale.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Sets the directory where translation files can be loaded from.
     * Translation files are expected to be have the same name as the
     * locale with a .php extension. The translation file should return
     * an array with translations.
     */
    public function setDataDir(string $dir): void
    {
        $this->dataDir = $dir;
    }

    public function translate(string $phrase, array $params = [], ?string $locale = null, ?string $fallback = null): string
    {
        if (!$locale) {
            $locale = $this->locale;
        }

        // lazy load locale data
        $this->loadLocaleData($locale);

        // look up the phrase
        $translatedPhrase = $this->data[$locale]['phrases'][$phrase] ?? null;

        // use the fallback phrase if a translated phrase is not
        // available
        if (!$translatedPhrase) {
            $translatedPhrase = $fallback;
        }

        if (null != $translatedPhrase) {
            // inject parameters into phrase
            if (count($params) > 0) {
                foreach ($params as $param => $paramValue) {
                    $translatedPhrase = str_replace('{{'.$param.'}}', $paramValue, $translatedPhrase);
                }
            }

            return $translatedPhrase;
        }

        // if the phrase does not exist for this locale
        // just return the phrase key
        return $phrase;
    }

    /**
     * Loads locale data for a supplied locale.
     *
     * @return $this
     */
    private function loadLocaleData(string $locale)
    {
        if (isset($this->data[$locale])) {
            return $this;
        }

        $filename = str_replace('//', '/', $this->dataDir.'/').$locale.'.php';

        if ($this->dataDir && file_exists($filename)) {
            $this->data[$locale] = include $filename;
        } else {
            $this->data[$locale] = [];
        }

        return $this;
    }
}
