<?php

namespace Pulsar\Interfaces;

interface TranslatorInterface
{
    /**
     * Translates a phrase.
     *
     * @param array       $params   parameters to inject into phrase
     * @param string|null $locale   optional locale
     * @param string|null $fallback optional fallback phrase
     */
    public function translate(string $phrase, array $params = [], ?string $locale = null, ?string $fallback = null): string;
}
