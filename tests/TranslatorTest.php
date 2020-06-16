<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar\Tests;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Translator;

class TranslatorTest extends MockeryTestCase
{
    private function getTranslator(): Translator
    {
        $translator = new Translator('en');
        $translator->setDataDir('tests/locales');

        return $translator;
    }

    public function testGetAndSetLocale()
    {
        $translator = $this->getTranslator();
        $translator->setLocale('pirate');
        $this->assertEquals('pirate', $translator->getLocale());
    }

    public function testTranslate()
    {
        $translator = $this->getTranslator();
        $translator->setLocale('en');

        // test phrase
        $this->assertEquals('This is a test', $translator->translate('test_phrase'));

        // non-existent phrase
        $this->assertEquals('non_existent_phrase', $translator->translate('non_existent_phrase'));

        // non-existent locale
        $this->assertEquals('some_phrase', $translator->translate('some_phrase', [], 'pirate'));

        // fallback phrase
        $this->assertEquals('{{field_name}} is invalid', $translator->translate('pulsar.validation.failed', [], false));
    }

    public function testTranslateParameterInjection()
    {
        $translator = $this->getTranslator();
        $translator->setLocale('en');

        $parameters = [
            'parameter_1' => 1,
            'test' => 'testing',
            'blah' => 'blah',
        ];

        $expected = 'Testing parameter injection: 1 blah testing';

        $this->assertEquals($expected, $translator->translate('parameter_injection', $parameters));

        // fallback phrase
        $this->assertEquals('Test is invalid', $translator->translate('pulsar.validation.failed', ['field_name' => 'Test'], false));
    }
}
