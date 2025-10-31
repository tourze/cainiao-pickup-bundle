<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Enum;

use CainiaoPickupBundle\Enum\ItemTypeEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ItemTypeEnum::class)]
final class ItemTypeEnumTest extends AbstractEnumTestCase
{
    /**
     * 测试枚举值是否符合预期
     */
    public function testEnumValues(): void
    {
        $this->assertSame('document', ItemTypeEnum::DOCUMENT->value);
        $this->assertSame('clothing', ItemTypeEnum::CLOTHING->value);
        $this->assertSame('electronics', ItemTypeEnum::ELECTRONICS->value);
        $this->assertSame('food', ItemTypeEnum::FOOD->value);
        $this->assertSame('fragile', ItemTypeEnum::FRAGILE->value);
        $this->assertSame('other', ItemTypeEnum::OTHER->value);
    }

    /**
     * 测试从值获取枚举用例
     */
    public function testFromValue(): void
    {
        $this->assertEquals(ItemTypeEnum::DOCUMENT, ItemTypeEnum::from('document'));
        $this->assertEquals(ItemTypeEnum::CLOTHING, ItemTypeEnum::from('clothing'));
        $this->assertEquals(ItemTypeEnum::ELECTRONICS, ItemTypeEnum::from('electronics'));
        $this->assertEquals(ItemTypeEnum::FOOD, ItemTypeEnum::from('food'));
    }

    /**
     * 测试无效值的情况
     */
    public function testInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ItemTypeEnum::from('non_existent_value');
    }

    /**
     * 测试tryFrom方法
     */
    public function testTryFrom(): void
    {
        $this->assertNull(ItemTypeEnum::tryFrom('non_existent_value'));
        $this->assertEquals(ItemTypeEnum::FRAGILE, ItemTypeEnum::tryFrom('fragile'));
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        // 测试单个枚举实例的 toArray 方法
        $documentType = ItemTypeEnum::DOCUMENT;
        $result = $documentType->toArray();

        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('document', $result['value']);
        $this->assertEquals('文件', $result['label']);

        // 测试其他枚举值
        $clothingType = ItemTypeEnum::CLOTHING;
        $clothingResult = $clothingType->toArray();
        $this->assertEquals('clothing', $clothingResult['value']);
        $this->assertEquals('服装', $clothingResult['label']);
    }
}
