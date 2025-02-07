<?php

declare(strict_types=1);

namespace OpenAPIValidationTests\PSR7;

use Cache\Adapter\PHPArray\ArrayCachePool;
use cebe\openapi\spec\OpenApi;
use OpenAPIValidation\PSR7\CacheableSchemaFactory;
use OpenAPIValidation\PSR7\ValidatorBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \OpenAPIValidation\PSR7\ValidatorBuilder
 */
final class ValidatorBuilderTest extends TestCase
{
    public function testItCachesParsedOpenApiSpec() : void
    {
        // configure cache
        $pool  = [];
        $cache = new ArrayCachePool(10, $pool);

        $factory = $this->createMock(CacheableSchemaFactory::class);
        $factory->expects($this->once())->method('createSchema')
            ->willReturn(new OpenApi([]));
        $cacheKey = 'the_cache_key';
        $factory->expects($this->exactly(2))->method('getCacheKey')
            ->willReturn($cacheKey);

        $v1 = (new ValidatorBuilder())->setSchemaFactory($factory)->setCache($cache)->getServiceRequestValidator();
        $v2 = (new ValidatorBuilder())->setSchemaFactory($factory)->setCache($cache)->getServiceRequestValidator();

        self::assertEquals($v1, $v2);
        self::assertTrue($cache->getItem($cacheKey)->isHit());
    }

    public function testItUtilizesCacheKeyOverride() : void
    {
        // configure cache
        $pool  = [];
        $cache = new ArrayCachePool(10, $pool);

        $factory = $this->createMock(CacheableSchemaFactory::class);
        $factory->expects($this->once())->method('createSchema')
            ->willReturn(new OpenApi([]));
        $factory->expects($this->never())->method('getCacheKey');

        // parse file
        $cacheKey = 'custom_key';
        (new ValidatorBuilder())->setSchemaFactory($factory)
            ->setCache($cache)
            ->overrideCacheKey($cacheKey)
            ->getServiceRequestValidator();

        self::assertTrue($cache->getItem($cacheKey)->isHit());
    }
}
