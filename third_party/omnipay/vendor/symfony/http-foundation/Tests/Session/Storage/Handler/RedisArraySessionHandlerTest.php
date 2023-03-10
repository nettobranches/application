<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpFoundation\Tests\Session\Storage\Handler;

/**
 * @group integration
 */
class RedisArraySessionHandlerTest extends AbstractRedisSessionHandlerTestCase
{
    /**
     * @return \RedisArray
     */
    protected function createRedisClient(string $host): object
    {
        return new \RedisArray([$host]);
    }
}
