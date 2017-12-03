<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Locale;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Errors;

class ErrorsTest extends MockeryTestCase
{
    private function getErrorStack()
    {
        $stack = new Errors();
        $stack->setLocale(new Locale());

        return $stack;
    }

    public function testGetLocale()
    {
        $errorStack = new Errors();
        $this->assertInstanceOf(Locale::class, $errorStack->getLocale());
        $locale = new Locale();
        $errorStack->setLocale($locale);
        $this->assertEquals($locale, $errorStack->getLocale());
    }

    public function testErrorsDeprecated()
    {
        $errorStack = $this->getErrorStack();

        // push some errors
        $error1 = [
            'error' => 'some_error',
            'message' => 'Something is wrong',
        ];

        $error2 = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'params' => [
                'field' => 'username',
            ],
        ];

        $this->assertEquals($errorStack, $errorStack->push($error1));
        $this->assertEquals($errorStack, $errorStack->push($error2));
        $this->assertEquals($errorStack, $errorStack->push('some_error'));

        // check the result
        $expected1 = [
            'error' => 'some_error',
            'message' => 'Something is wrong',
            'params' => [],
        ];

        $expected2 = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'params' => [
                'field' => 'username',
            ],
        ];

        $expected3 = [
            'error' => 'some_error',
            'message' => 'some_error',
            'params' => [],
        ];

        $errors = $errorStack->errors();
        $this->assertEquals(3, count($errors));
        $this->assertEquals([$expected1, $expected2, $expected3], $errors);
    }

    public function testAll()
    {
        $errorStack = $this->getErrorStack();

        // push some errors
        $this->assertEquals($errorStack, $errorStack->add('some_error'));
        $this->assertEquals($errorStack, $errorStack->add('pulsar.validation.failed', ['field_name' => 'Username']));
        $this->assertEquals($errorStack, $errorStack->add('some_error'));

        // check the result
        $expected = [
            'some_error',
            'Username is invalid',
            'some_error',
        ];

        $messages = $errorStack->all();
        $this->assertEquals(3, count($messages));
        $this->assertEquals($expected, $messages);

        $messages = $errorStack->messages();
        $this->assertEquals(3, count($messages));
        $this->assertEquals($expected, $messages);
    }

    public function testAllWithoutLocale()
    {
        $errorStack = new Errors();
        $errorStack->add('pulsar.validation.failed', ['field_name' => 'test']);
        $this->assertEquals(['test is invalid'], $errorStack->all());
        $this->assertEquals(['test is invalid'], $errorStack->messages());
    }

    public function testAllFallback()
    {
        $errorStack = $this->getErrorStack();
        $errorStack->add('pulsar.validation.alpha', ['field_name' => 'Name']);
        $this->assertEquals(['Name only allows letters'], $errorStack->all());
        $this->assertEquals(['Name only allows letters'], $errorStack->messages());
    }

    public function testFind()
    {
        $errorStack = $this->getErrorStack();

        // push some errors
        $this->assertEquals($errorStack, $errorStack->add('some_error'));
        $this->assertEquals($errorStack, $errorStack->add('pulsar.validation.failed', ['field_name' => 'Username', 'field' => 'username']));
        $this->assertEquals($errorStack, $errorStack->add('some_error'));

        // check the result
        $expected = [
            'error' => 'pulsar.validation.failed',
            'message' => 'Username is invalid',
            'params' => [
                'field_name' => 'Username',
                'field' => 'username',
            ],
        ];

        $this->assertEquals($expected, $errorStack->find('username'));
        $this->assertEquals($expected, $errorStack->find('username', 'field'));

        $this->assertFalse($errorStack->find('non-existent'));
    }

    public function testHas()
    {
        $errorStack = $this->getErrorStack();

        // push some errors
        $this->assertEquals($errorStack, $errorStack->add('some_error'));
        $this->assertEquals($errorStack, $errorStack->add('username_invalid', ['field' => 'username']));
        $this->assertEquals($errorStack, $errorStack->add('some_error'));

        // check the result
        $this->assertTrue($errorStack->has('username'));
        $this->assertTrue($errorStack->has('username', 'field'));

        $this->assertFalse($errorStack->has('non-existent'));
        $this->assertFalse($errorStack->has('username', 'something'));
    }

    public function testClear()
    {
        $errorStack = $this->getErrorStack();
        $this->assertEquals($errorStack, $errorStack->clear());
        $this->assertCount(0, $errorStack->all());
        $this->assertCount(0, $errorStack->messages());
    }

    public function testIterator()
    {
        $errorStack = $this->getErrorStack();

        for ($i = 1; $i <= 5; ++$i) {
            $errorStack->add("$i");
        }

        $result = [];
        foreach ($errorStack as $k => $v) {
            $result[$k] = $v['error'];
        }

        $this->assertEquals(['1', '2', '3', '4', '5'], $result);
    }

    public function testCount()
    {
        $errorStack = $this->getErrorStack();

        $errorStack->add('Test');
        $this->assertCount(1, $errorStack);
    }

    public function testArrayAccess()
    {
        $errorStack = $this->getErrorStack();

        $errorStack[0] = 'test';
        $this->assertTrue(isset($errorStack[0]));
        $this->assertFalse(isset($errorStack[6]));

        $this->assertEquals('test', $errorStack[0]['error']);
        unset($errorStack[0]);
    }

    public function testArrayGetFail()
    {
        $this->expectException(OutOfBoundsException::class);

        $errorStack = $this->getErrorStack();

        echo $errorStack['invalid'];
    }

    public function testArraySetFail()
    {
        $this->expectException(Exception::class);

        $errorStack = $this->getErrorStack();

        $errorStack['invalid'] = 'test';
    }
}
