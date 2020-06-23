<?php

namespace Pulsar\Interfaces;

interface TranslatorInterface
{
    /**
     * Translates a phrase.
     *
     * @param array       $context parameters to inject into phrase
     * @param string|null $locale  optional locale
     */
    public function translate(string $phrase, array $context = [], ?string $locale = null): string;
}
