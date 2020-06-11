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

use DateTimeZone;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use InvalidArgumentException;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Pulsar\Model;
use Pulsar\Tests\Models\TestModel;
use Pulsar\Type;
use Pulsar\Validator;
use stdClass;

class ValidatorTest extends MockeryTestCase
{
    public static $model;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$model = new TestModel();
    }

    public function testGetRules()
    {
        $validator = new Validator('alpha');
        $this->assertEquals([['alpha']], $validator->getRules());

        $validator = new Validator('alpha|timestamp');
        $this->assertEquals([['alpha'], ['timestamp']], $validator->getRules());

        $validator = new Validator(['alpha', 'timestamp']);
        $this->assertEquals([['alpha'], ['timestamp']], $validator->getRules());

        $validator = new Validator([['alpha', 'min' => 5], 'timestamp']);
        $this->assertEquals([['alpha', 'min' => 5], ['timestamp']], $validator->getRules());

        $validator = new Validator(['alpha', 'min' => 5]);
        $this->assertEquals([['alpha', 'min' => 5]], $validator->getRules());
    }

    public function testInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new Validator('invalid');
        $s = 'abc';
        $validator->validate($s, self::$model);
    }

    public function testAlpha()
    {
        $validator = new Validator('alpha');
        $s = 'abc';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = ')S*F#$)S*';
        $this->assertFalse($validator->validate($s, self::$model));

        $validator = new Validator(['alpha', 'min' => 5]);
        $s = 'abcde';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = 'abc';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testAlphaNumeric()
    {
        $validator = new Validator('alpha_numeric');
        $s = 'abc1234';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = ')S*F#$)S*';
        $this->assertFalse($validator->validate($s, self::$model));

        $validator = new Validator(['alpha_numeric', 'min' => 5]);
        $s = 'a2cde';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = 'a2c';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testAlphaDash()
    {
        $validator = new Validator('alpha_dash');
        $s = 'abc-1234';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = ')S*F#$)S*';
        $this->assertFalse($validator->validate($s, self::$model));

        $validator = new Validator(['alpha_dash', 'min' => 5]);
        $s = 'r2-d2';
        $this->assertTrue($validator->validate($s, self::$model));

        $validator = new Validator(['alpha_numeric', 'min' => 7]);
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testBoolean()
    {
        $validator = new Validator('boolean');

        $s = '1';
        $this->assertTrue($validator->validate($s, self::$model));
        $this->assertTrue($s);
        $s = '0';
        $this->assertTrue($validator->validate($s, self::$model));
        $this->assertFalse($s);
    }

    public function testCallable()
    {
        $validator = new Validator(['callable', 'fn' => function (&$value, array $options, Model $model) {
            $value = 'changed';

            return true;
        }]);
        $s = 'original value';
        $this->assertTrue($validator->validate($s, self::$model));
        $this->assertEquals('changed', $s);
    }

    public function testDate()
    {
        date_default_timezone_set('UTC');

        $validator = new Validator('date');
        $s = 'today';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = '09/17/2013';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = 'doesnotwork';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testEmail()
    {
        $validator = new Validator('email');
        $s = 'test@example.com';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = 'test';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testEncrypt()
    {
        $keyAscii = 'def000004b2b3e1048422d3e526f49baf22f57ad94d9f11b35409af630a4ab0f40bcce2a9963dcb876da6df8ec06c7eb4f2cd32cfae385955918d43f49e633dc5d339f1d';
        $key = Key::loadFromAsciiSafeString($keyAscii);
        Type::setEncryptionKey($key);
        $validator = new Validator(['encrypt']);
        $s = 'original value';
        $this->assertTrue($validator->validate($s, self::$model));
        $this->assertNotEquals('original value', $s);
        $this->assertEquals('original value', Crypto::decrypt($s, $key));
    }

    public function testEnum()
    {
        $validator = new Validator(['enum', 'choices' => ['red', 'orange', 'yellow', 'green', 'blue', 'violet']]);
        $s = 'blue';
        $this->assertTrue($validator->validate($s, self::$model));

        $validator = new Validator(['enum', 'choices' => ['Austin', 'Dallas', 'OKC', 'Tulsa']]);
        $s = 'Paris';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testIp()
    {
        $validator = new Validator('ip');
        $s = '127.0.0.1';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = 'doesnotwork';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testMatching()
    {
        $validator = new Validator('matching');
        $match = 'notarray';
        $this->assertTrue($validator->validate($match, self::$model));

        $match = ['test', 'test'];
        $this->assertTrue($validator->validate($match, self::$model));
        $this->assertEquals('test', $match);

        $match = ['test', 'test', 'test', 'test'];
        $this->assertTrue($validator->validate($match, self::$model));
        $this->assertEquals('test', $match);

        $notmatching = ['test', 'nope'];
        $this->assertFalse($validator->validate($notmatching, self::$model));
        $this->assertEquals(['test', 'nope'], $notmatching);
    }

    public function testNumeric()
    {
        $validator = new Validator('numeric');
        $s = 12345.22;
        $this->assertTrue($validator->validate($s, self::$model));
        $s = '1234';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = 'notanumber';
        $this->assertFalse($validator->validate($s, self::$model));

        $validator = new Validator(['numeric', 'type' => 'double']);
        $s = 12345.22;
        $this->assertTrue($validator->validate($s, self::$model));

        $validator = new Validator(['numeric', 'type' => 'int']);
        $s = 12345.22;
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testPasswordPhp()
    {
        $validator = new Validator(['password_php', 'cost' => 12, 'min' => 8]);
        $password = 'testpassword';
        $this->assertTrue($validator->validate($password, self::$model));
        $this->assertTrue(password_verify('testpassword', $password));

        $invalid = '...';
        $this->assertFalse($validator->validate($invalid, self::$model));
    }

    public function testRange()
    {
        $s = -1;
        $validator = new Validator('range');
        $this->assertTrue($validator->validate($s, self::$model));

        $validator = new Validator(['range', 'min' => -1]);
        $this->assertTrue($validator->validate($s, self::$model));

        $validator = new Validator(['range', 'min' => -1, 'max' => 100]);
        $this->assertTrue($validator->validate($s, self::$model));

        $s = 100;
        $validator = new Validator(['range', 'min' => 101]);
        $this->assertFalse($validator->validate($s, self::$model));

        $validator = new Validator(['range', 'min' => 0, 'max' => 99]);
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testRequired()
    {
        $validator = new Validator('required');
        $s = 'ok';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = '';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testString()
    {
        $s = 'thisisok';
        $validator = new Validator('string');
        $this->assertTrue($validator->validate($s, self::$model));

        $validator = new Validator(['string', 'min' => 5]);
        $this->assertTrue($validator->validate($s, self::$model));
        $validator = new Validator(['string', 'min' => 1, 'max' => 8]);
        $this->assertTrue($validator->validate($s, self::$model));
        $validator = new Validator(['string', 'min' => 0, 'max' => 9]);
        $this->assertTrue($validator->validate($s, self::$model));
        $validator = new Validator(['string', 'min' => 9]);
        $this->assertFalse($validator->validate($s, self::$model));
        $validator = new Validator(['string', 'min' => 1, 'max' => 7]);
        $this->assertFalse($validator->validate($s, self::$model));

        $s = new stdClass();
        $validator = new Validator('string');
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testTimeZone()
    {
        $validator = new Validator('time_zone');

        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $this->assertTrue($validator->validate($tz, self::$model), "Time zone $tz was not considered valid");
        }

        $s = 'anywhere';
        $this->assertFalse($validator->validate($s, self::$model));

        $s = 'Jupiter/Europa';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testTimestamp()
    {
        $validator = new Validator('timestamp');
        $s = $t = time();
        $this->assertTrue($validator->validate($s, self::$model));
        $this->assertEquals($t, $s);

        $s = 'today';
        $this->assertTrue($validator->validate($s, self::$model));
        $this->assertEquals(strtotime('today'), $s);
    }

    public function testDbTimestamp()
    {
        $validator = new Validator('db_timestamp');

        $s = mktime(23, 34, 20, 4, 18, 2012);
        $this->assertTrue($validator->validate($s, self::$model));
        $this->assertEquals('2012-04-18 23:34:20', $s);

        $s = 'test';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testUrl()
    {
        $validator = new Validator('url');

        $s = 'http://example.com';
        $this->assertTrue($validator->validate($s, self::$model));
        $s = 'notaurl';
        $this->assertFalse($validator->validate($s, self::$model));
    }

    public function testMultipleRules()
    {
        $validator = new Validator(['matching', ['string', 'min' => 2]]);

        $t = ['test', 'test'];
        $this->assertTrue($validator->validate($t, self::$model));
        $this->assertEquals('test', $t);
    }

    public function testMultipleRulesShortCircuit()
    {
        $validator = new Validator(['matching', ['password', 'min' => 10]]);

        $t = ['test', 'no match'];
        $this->assertFalse($validator->validate($t, self::$model));
        $this->assertEquals('matching', $validator->getFailingRule());
    }

    public function testGetFailingRule()
    {
        $validator = new Validator(['matching', ['string', 'min' => 5]]);

        $t = ['test', 'test'];
        $this->assertFalse($validator->validate($t, self::$model));
        $this->assertEquals('string', $validator->getFailingRule());
    }
}
