<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(CainiaoConfig::class)]
final class CainiaoConfigTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CainiaoConfig();
    }

    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', '测试配置'];
        yield 'appKey' => ['appKey', 'test_app_key'];
        yield 'appSecret' => ['appSecret', 'test_app_secret'];
        yield 'accessCode' => ['accessCode', 'test_access_code'];
        yield 'providerId' => ['providerId', 'test_provider_id'];
        yield 'apiGateway' => ['apiGateway', 'https://api.test.com'];
        yield 'remark' => ['remark', '测试备注'];
        yield 'valid' => ['valid', true];
    }

    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(CainiaoConfig::class, $entity);
        $entity->setName('测试配置');

        $this->assertSame('测试配置', (string) $entity);
    }
}
