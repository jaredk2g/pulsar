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
use PHPUnit\Framework\TestCase;
use Pulsar\Errors;

class ErrorsTest extends TestCase
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
        $this->assertNull($errorStack->getLocale());
        $locale = new Locale();
        $errorStack->setLocale($locale);
        $this->assertEquals($locale, $errorStack->getLocale());
    }

    public function testErrors()
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
        $expected = [
            'Something is wrong',
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
        $errorStack->push('Test');
        $this->assertEquals(['Test'], $errorStack->all());
        $this->assertEquals(['Test'], $errorStack->messages());
    }

    public function testFind()
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
        $expected = [
            'error' => 'username_invalid',
            'message' => 'Username is invalid',
            'params' => [
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
            $errorStack->push("$i");
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

        $errorStack->push('Test');
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
