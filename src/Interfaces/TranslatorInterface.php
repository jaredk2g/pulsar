<?php

namespace Pulsar\Interfaces;

interface TranslatorInterface
{
    /**
     * Translates a phrase.
     *
     * @param array       $params parameters to inject into phrase
     * @param string|null $locale optional locale
     */
    public function translate(string $phrase, array $params = [], ?string $locale = null): string;
}
