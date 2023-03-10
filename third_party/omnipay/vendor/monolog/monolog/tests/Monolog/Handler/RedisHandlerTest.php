<?php declare(strict_types=1);

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Handler;

use Monolog\Test\TestCase;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;

class RedisHandlerTest extends TestCase
{
    public function testConstructorShouldThrowExceptionForInvalidRedis()
    {
        $this->expectException(\InvalidArgumentException::class);

        new RedisHandler(new \stdClass(), 'key');
    }

    public function testConstructorShouldWorkWithPredis()
    {
        $redis = $this->createMock('Predis\Client');
        $this->assertInstanceof('Monolog\Handler\RedisHandler', new RedisHandler($redis, 'key'));
    }

    public function testConstructorShouldWorkWithRedis()
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('The redis ext is required to run this test');
        }

        $redis = $this->createMock('Redis');
        $this->assertInstanceof('Monolog\Handler\RedisHandler', new RedisHandler($redis, 'key'));
    }

    public function testPredisHandle()
    {
        $redis = $this->prophesize('Predis\Client');
        $redis->rpush('key', 'test')->shouldBeCalled();
        $redis = $redis->reveal();

        $record = $this->getRecord(Logger::WARNING, 'test', ['data' => new \stdClass, 'foo' => 34]);

        $handler = new RedisHandler($redis, 'key');
        $handler->setFormatter(new LineFormatter("%message%"));
        $handler->handle($record);
    }

    public function testRedisHandle()
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('The redis ext is required to run this test');
        }

        $redis = $this->createPartialMock('Redis', ['rPush']);

        // Redis uses rPush
        $redis->expects($this->once())
            ->method('rPush')
            ->with('key', 'test');

        $record = $this->getRecord(Logger::WARNING, 'test', ['data' => new \stdClass, 'foo' => 34]);

        $handler = new RedisHandler($redis, 'key');
        $handler->setFormatter(new LineFormatter("%message%"));
        $handler->handle($record);
    }

    public function testRedisHandleCapped()
    {
        if (!class_exists('Redis')) {
            $this->markTestSkipped('The redis ext is required to run this test');
        }

        $redis = $this->createPartialMock('Redis', ['multi', 'rPush', 'lTrim', 'exec']);

        // Redis uses multi
        $redis->expects($this->once())
            ->method('multi')
            ->will($this->returnSelf());

        $redis->expects($this->once())
            ->method('rPush')
            ->will($this->returnSelf());

        $redis->expects($this->once())
            ->method('lTrim')
            ->will($this->returnSelf());

        $redis->expects($this->once())
            ->method('exec')
            ->will($this->returnSelf());

        $record = $this->getRecord(Logger::WARNING, 'test', ['data' => new \stdClass, 'foo' => 34]);

        $handler = new RedisHandler($redis, 'key', Logger::DEBUG, true, 10);
        $handler->setFormatter(new LineFormatter("%message%"));
        $handler->handle($record);
    }

    public function testPredisHandleCapped()
    {
        $redis = $this->createPartialMock('Predis\Client', ['transaction']);

        $redisTransaction = $this->createPartialMock('Predis\Client', ['rPush', 'lTrim']);

        $redisTransaction->expects($this->once())
            ->method('rPush')
            ->will($this->returnSelf());

        $redisTransaction->expects($this->once())
            ->method('lTrim')
            ->will($this->returnSelf());

        // Redis uses multi
        $redis->expects($this->once())
            ->method('transaction')
            ->will($this->returnCallback(function ($cb) use ($redisTransaction) {
                $cb($redisTransaction);
            }));

        $record = $this->getRecord(Logger::WARNING, 'test', ['data' => new \stdClass, 'foo' => 34]);

        $handler = new RedisHandler($redis, 'key', Logger::DEBUG, true, 10);
        $handler->setFormatter(new LineFormatter("%message%"));
        $handler->handle($record);
    }
}
