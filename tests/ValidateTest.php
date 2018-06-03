<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Validator;

class ValidateTest extends MockeryTestCase
{
    public function testGetRules()
    {
        $validator = new Validator('alpha');
        $this->assertEquals('alpha', $validator->getRules());
    }

    public function testAlpha()
    {
        $validator = new Validator('alpha');
        $s = 'abc';
        $this->assertTrue($validator->validate($s));
        $s = ')S*F#$)S*';
        $this->assertFalse($validator->validate($s));

        $validator = new Validator('alpha:5');
        $s = 'abcde';
        $this->assertTrue($validator->validate($s));
        $s = 'abc';
        $this->assertFalse($validator->validate($s));
    }

    public function testAlphaNumeric()
    {
        $validator = new Validator('alpha_numeric');
        $s = 'abc1234';
        $this->assertTrue($validator->validate($s));
        $s = ')S*F#$)S*';
        $this->assertFalse($validator->validate($s));

        $validator = new Validator('alpha_numeric:5');
        $s = 'a2cde';
        $this->assertTrue($validator->validate($s));
        $s = 'a2c';
        $this->assertFalse($validator->validate($s));
    }

    public function testAlphaDash()
    {
        $validator = new Validator('alpha_dash');
        $s = 'abc-1234';
        $this->assertTrue($validator->validate($s));
        $s = ')S*F#$)S*';
        $this->assertFalse($validator->validate($s));

        $validator = new Validator('alpha_dash:5');
        $s = 'r2-d2';
        $this->assertTrue($validator->validate($s));

        $validator = new Validator('alpha_dash:7');
        $this->assertFalse($validator->validate($s));
    }

    public function testBoolean()
    {
        $validator = new Validator('boolean');

        $s = '1';
        $this->assertTrue($validator->validate($s));
        $this->assertTrue($s);
        $s = '0';
        $this->assertTrue($validator->validate($s));
        $this->assertFalse($s);
    }

    public function testEmail()
    {
        $validator = new Validator('email');
        $s = 'test@example.com';
        $this->assertTrue($validator->validate($s));
        $s = 'test';
        $this->assertFalse($validator->validate($s));
    }

    public function testEnum()
    {
        $validator = new Validator('enum:red,orange,yellow,green,blue,violet');
        $s = 'blue';
        $this->assertTrue($validator->validate($s));

        $validator = new Validator('enum:Austin,Dallas,OKC,Tulsa');
        $s = 'Paris';
        $this->assertFalse($validator->validate($s));
    }

    public function testDate()
    {
        date_default_timezone_set('UTC');

        $validator = new Validator('date');
        $s = 'today';
        $this->assertTrue($validator->validate($s));
        $s = '09/17/2013';
        $this->assertTrue($validator->validate($s));
        $s = 'doesnotwork';
        $this->assertFalse($validator->validate($s));
    }

    public function testIp()
    {
        $validator = new Validator('ip');
        $s = '127.0.0.1';
        $this->assertTrue($validator->validate($s));
        $s = 'doesnotwork';
        $this->assertFalse($validator->validate($s));
    }

    public function testMatching()
    {
        $validator = new Validator('matching');
        $match = 'notarray';
        $this->assertTrue($validator->validate($match));

        $match = ['test', 'test'];
        $this->assertTrue($validator->validate($match));
        $this->assertEquals('test', $match);

        $match = ['test', 'test', 'test', 'test'];
        $this->assertTrue($validator->validate($match));
        $this->assertEquals('test', $match);

        $notmatching = ['test', 'nope'];
        $this->assertFalse($validator->validate($notmatching));
        $this->assertEquals(['test', 'nope'], $notmatching);
    }

    public function testNumeric()
    {
        $validator = new Validator('numeric');
        $s = 12345.22;
        $this->assertTrue($validator->validate($s));
        $s = '1234';
        $this->assertTrue($validator->validate($s));
        $s = 'notanumber';
        $this->assertFalse($validator->validate($s));

        $validator = new Validator('numeric:double');
        $s = 12345.22;
        $this->assertTrue($validator->validate($s));

        $validator = new Validator('numeric:int');
        $s = 12345.22;
        $this->assertFalse($validator->validate($s));
    }

    public function testPasswordPhp()
    {
        $salt = 'saltvalue';
        Validator::configure(['password_cost' => 12]);

        $validator = new Validator('password_php:8');
        $password = 'testpassword';
        $this->assertTrue($validator->validate($password));
        $this->assertTrue(password_verify('testpassword', $password));

        $invalid = '...';
        $this->assertFalse($validator->validate($invalid));
    }

    public function testRange()
    {
        $s = -1;
        $validator = new Validator('range');
        $this->assertTrue($validator->validate($s));

        $validator = new Validator('range:-1');
        $this->assertTrue($validator->validate($s));

        $validator = new Validator('range:-1:100');
        $this->assertTrue($validator->validate($s));

        $s = 100;
        $validator = new Validator('range:101');
        $this->assertFalse($validator->validate($s));

        $validator = new Validator('range:0:99');
        $this->assertFalse($validator->validate($s));
    }

    public function testRequired()
    {
        $validator = new Validator('required');
        $s = 'ok';
        $this->assertTrue($validator->validate($s));
        $s = '';
        $this->assertFalse($validator->validate($s));
    }

    public function testString()
    {
        $s = 'thisisok';
        $validator = new Validator('string');
        $this->assertTrue($validator->validate($s));

        $validator = new Validator('string:5');
        $this->assertTrue($validator->validate($s));
        $validator = new Validator('string:1:8');
        $this->assertTrue($validator->validate($s));
        $validator = new Validator('string:0:9');
        $this->assertTrue($validator->validate($s));
        $validator = new Validator('string:9');
        $this->assertFalse($validator->validate($s));
        $validator = new Validator('string:1:7');
        $this->assertFalse($validator->validate($s));

        $s = new stdClass();
        $validator = new Validator('string');
        $this->assertFalse($validator->validate($s));
    }

    public function testTimeZone()
    {
        $validator = new Validator('time_zone');

        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $this->assertTrue($validator->validate($tz), "Time zone $tz was not considered valid");
        }

        $s = 'anywhere';
        $this->assertFalse($validator->validate($s));

        $s = 'Jupiter/Europa';
        $this->assertFalse($validator->validate($s));
    }

    public function testTimestamp()
    {
        $validator = new Validator('timestamp');
        $s = $t = time();
        $this->assertTrue($validator->validate($s));
        $this->assertEquals($t, $s);

        $s = 'today';
        $this->assertTrue($validator->validate($s));
        $this->assertEquals(strtotime('today'), $s);
    }

    public function testDbTimestamp()
    {
        $validator = new Validator('db_timestamp');

        $s = mktime(23, 34, 20, 4, 18, 2012);
        $this->assertTrue($validator->validate($s));
        $this->assertEquals('2012-04-18 23:34:20', $s);

        $s = 'test';
        $this->assertFalse($validator->validate($s));
    }

    public function testUrl()
    {
        $validator = new Validator('url');

        $s = 'http://example.com';
        $this->assertTrue($validator->validate($s));
        $s = 'notaurl';
        $this->assertFalse($validator->validate($s));
    }

    public function testMultipleRules()
    {
        $validator = new Validator('matching|string:2');

        $t = ['test', 'test'];
        $this->assertTrue($validator->validate($t));
        $this->assertEquals('test', $t);
    }

    public function testMultipleRulesShortCircuit()
    {
        $validator = new Validator('matching|password:10');

        $t = ['test', 'no match'];
        $this->assertFalse($validator->validate($t));
        $this->assertEquals('matching', $validator->getFailingRule());
    }

    public function testKeyValueRules()
    {
        $test = [
            'test' => ['test', 'test'],
            'test2' => 'alphanumer1c',
        ];

        $rules = [
            'test' => 'matching|string:2',
            'test2' => 'alpha_numeric',
        ];

        $validator = new Validator($rules);

        $this->assertTrue($validator->validate($test));
        $this->assertEquals('test', $test['test']);
    }

    public function testGetFailingRule()
    {
        $validator = new Validator('matching|string:5');

        $t = ['test', 'test'];
        $this->assertFalse($validator->validate($t));
        $this->assertEquals('string', $validator->getFailingRule());
    }
}
