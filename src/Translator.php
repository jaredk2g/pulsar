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

final class Translator implements TranslatorInterface
{
    private static array $messages = [
        'pulsar.validation.alpha' => '{{field_name}} only allows letters',
        'pulsar.validation.alpha_numeric' => '{{field_name}} only allows letters and numbers',
        'pulsar.validation.alpha_dash' => '{{field_name}} only allows letters and dashes',
        'pulsar.validation.boolean' => '{{field_name}} must be yes or no',
        'pulsar.validation.callable' => '{{field_name}} is invalid',
        'pulsar.validation.email' => '{{field_name}} must be a valid email address',
        'pulsar.validation.enum' => '{{field_name}} must be one of the allowed values',
        'pulsar.validation.date' => '{{field_name}} must be a date',
        'pulsar.validation.failed' => '{{field_name}} is invalid',
        'pulsar.validation.ip' => '{{field_name}} only allows valid IP addresses',
        'pulsar.validation.matching' => '{{field_name}} must match',
        'pulsar.validation.numeric' => '{{field_name}} only allows numbers',
        'pulsar.validation.password' => '{{field_name}} must meet the password requirements',
        'pulsar.validation.password_php' => '{{field_name}} must meet the password requirements',
        'pulsar.validation.range' => '{{field_name}} must be within the allowed range',
        'pulsar.validation.required' => '{{field_name}} is missing',
        'pulsar.validation.string' => '{{field_name}} must be a string of the proper length',
        'pulsar.validation.time_zone' => '{{field_name}} only allows valid time zones',
        'pulsar.validation.timestamp' => '{{field_name}} only allows timestamps',
        'pulsar.validation.unique' => 'The {{field_name}} you chose has already been taken. Please try a different {{field_name}}.',
        'pulsar.validation.url' => '{{field_name}} only allows valid URLs',
    ];

    private string $locale;
    private string $dataDir = '';
    private array $data = [];

    public function __construct(?string $locale = 'en')
    {
        $this->locale = $locale;
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

    public function translate(string $phrase, array $context = [], ?string $locale = null): string
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
            // try to supply a fallback message in case
            // the user does not have one specified
            $translatedPhrase = self::$messages[$phrase] ?? null;
        }

        if (null != $translatedPhrase) {
            // inject parameters into phrase
            if (count($context) > 0) {
                foreach ($context as $param => $paramValue) {
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
     */
    private function loadLocaleData(string $locale): void
    {
        if (isset($this->data[$locale])) {
            return;
        }

        $filename = str_replace('//', '/', $this->dataDir.'/').$locale.'.php';

        if ($this->dataDir && file_exists($filename)) {
            $this->data[$locale] = include $filename;
        } else {
            $this->data[$locale] = [];
        }
    }
}
