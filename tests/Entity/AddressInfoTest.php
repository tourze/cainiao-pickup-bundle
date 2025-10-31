<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\AddressInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(AddressInfo::class)]
final class AddressInfoTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AddressInfo();
    }

    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', '测试名称'];
        yield 'mobile' => ['mobile', '13800138000'];
        yield 'fullAddressDetail' => ['fullAddressDetail', '北京市朝阳区三里屯街道10号'];
        yield 'provinceName' => ['provinceName', '北京市'];
        yield 'cityName' => ['cityName', '北京市'];
        yield 'areaName' => ['areaName', '朝阳区'];
        yield 'address' => ['address', '三里屯街道10号'];
    }

    /**
     * 测试 toApiFormat 方法
     */
    public function testToApiFormat(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(AddressInfo::class, $entity);
        $entity->setName('测试名称');
        $entity->setMobile('13800138000');
        $entity->setFullAddressDetail('北京市朝阳区三里屯街道10号');
        $entity->setProvinceName('北京市');
        $entity->setCityName('北京市');
        $entity->setAreaName('朝阳区');
        $entity->setAddress('三里屯街道10号');

        $result = $entity->toApiFormat();

        $this->assertSame('测试名称', $result['name']);
        $this->assertSame('13800138000', $result['mobile']);
        $this->assertSame('北京市朝阳区三里屯街道10号', $result['fullAddressDetail']);
        $this->assertSame('北京市', $result['provinceName']);
        $this->assertSame('北京市', $result['cityName']);
        $this->assertSame('朝阳区', $result['areaName']);
        $this->assertSame('三里屯街道10号', $result['address']);
    }

    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(AddressInfo::class, $entity);
        $entity->setName('测试名称');
        $entity->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $this->assertSame('测试名称 - 北京市朝阳区三里屯街道10号', (string) $entity);
    }
}
