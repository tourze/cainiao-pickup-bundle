<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(LogisticsDetail::class)]
final class LogisticsDetailTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new LogisticsDetail();
    }

    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'mailNo' => ['mailNo', 'SF123456789'];
        yield 'logisticsStatus' => ['logisticsStatus', '100'];
        yield 'logisticsDescription' => ['logisticsDescription', '已揽件'];
        yield 'logisticsTime' => ['logisticsTime', new \DateTimeImmutable('2023-08-01 10:00:00')];
        yield 'city' => ['city', '北京市'];
        yield 'area' => ['area', '朝阳区'];
        yield 'address' => ['address', '三里屯10号'];
        yield 'courierName' => ['courierName', '张三'];
        yield 'courierPhone' => ['courierPhone', '13800138000'];
    }

    /**
     * 测试 order 关联关系
     */
    public function testOrderRelationship(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(LogisticsDetail::class, $entity);
        $order = new PickupOrder();
        $order->setOrderCode('TEST123456');

        $entity->setOrder($order);
        $this->assertSame($order, $entity->getOrder());
    }

    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(LogisticsDetail::class, $entity);
        $entity->setLogisticsStatus('100');
        $entity->setLogisticsDescription('已揽件');

        $this->assertSame('100 - 已揽件', (string) $entity);
    }
}
