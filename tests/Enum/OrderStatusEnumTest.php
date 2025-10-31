<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Enum;

use CainiaoPickupBundle\Enum\OrderStatusEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(OrderStatusEnum::class)]
final class OrderStatusEnumTest extends AbstractEnumTestCase
{
    /**
     * 测试枚举值是否符合预期
     */
    public function testEnumValues(): void
    {
        $this->assertSame('cancelled', OrderStatusEnum::CANCELLED->value);
        $this->assertSame('0', OrderStatusEnum::CREATE->value);
        $this->assertSame('100', OrderStatusEnum::WAREHOUSE_ACCEPT->value);
        $this->assertSame('150', OrderStatusEnum::WAREHOUSE_PROCESS->value);
        $this->assertSame('200', OrderStatusEnum::WAREHOUSE_CONFIRMED->value);
        $this->assertSame('300', OrderStatusEnum::CONSIGN->value);
        $this->assertSame('400', OrderStatusEnum::ACCEPT->value);
        $this->assertSame('430', OrderStatusEnum::LH_HO->value);
        $this->assertSame('470', OrderStatusEnum::JK_HW_ACCEPT->value);
        $this->assertSame('471', OrderStatusEnum::JK_HWC->value);
        $this->assertSame('472', OrderStatusEnum::JK_BSC->value);
        $this->assertSame('473', OrderStatusEnum::JK_GFC->value);
        $this->assertSame('474', OrderStatusEnum::JK_GJGX->value);
        $this->assertSame('475', OrderStatusEnum::CC_HO->value);
        $this->assertSame('500', OrderStatusEnum::TRANSPORT->value);
        $this->assertSame('600', OrderStatusEnum::DELIVERING->value);
        $this->assertSame('700', OrderStatusEnum::FAILED->value);
        $this->assertSame('800', OrderStatusEnum::REJECT->value);
        $this->assertSame('900', OrderStatusEnum::AGENT_SIGN->value);
        $this->assertSame('901', OrderStatusEnum::STA_DELIVERING->value);
        $this->assertSame('950', OrderStatusEnum::OTHER_SIGN->value);
        $this->assertSame('1000', OrderStatusEnum::SIGN->value);
        $this->assertSame('1100', OrderStatusEnum::ORDER_TRANSER->value);
        $this->assertSame('1200', OrderStatusEnum::REVERSE_RETURN->value);
    }

    /**
     * 测试从值获取枚举用例
     */
    public function testFromValue(): void
    {
        $this->assertEquals(OrderStatusEnum::CANCELLED, OrderStatusEnum::from('cancelled'));
        $this->assertEquals(OrderStatusEnum::CREATE, OrderStatusEnum::from('0'));
        $this->assertEquals(OrderStatusEnum::WAREHOUSE_ACCEPT, OrderStatusEnum::from('100'));
        $this->assertEquals(OrderStatusEnum::SIGN, OrderStatusEnum::from('1000'));
    }

    /**
     * 测试无效值的情况
     */
    public function testInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        OrderStatusEnum::from('non_existent_value');
    }

    /**
     * 测试tryFrom方法
     */
    public function testTryFrom(): void
    {
        $this->assertNull(OrderStatusEnum::tryFrom('non_existent_value'));
        $this->assertEquals(OrderStatusEnum::SIGN, OrderStatusEnum::tryFrom('1000'));
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        // 测试单个枚举实例的 toArray 方法
        $createStatus = OrderStatusEnum::CREATE;
        $result = $createStatus->toArray();

        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('0', $result['value']);
        $this->assertEquals('已下单', $result['label']);

        // 测试其他枚举值
        $signStatus = OrderStatusEnum::SIGN;
        $signResult = $signStatus->toArray();
        $this->assertEquals('1000', $signResult['value']);
        $this->assertEquals('已签收', $signResult['label']);

        $cancelledStatus = OrderStatusEnum::CANCELLED;
        $cancelledResult = $cancelledStatus->toArray();
        $this->assertEquals('cancelled', $cancelledResult['value']);
        $this->assertEquals('已取消', $cancelledResult['label']);
    }
}
