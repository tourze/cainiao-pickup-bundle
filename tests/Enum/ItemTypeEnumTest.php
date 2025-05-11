<?php

namespace CainiaoPickupBundle\Tests\Enum;

use CainiaoPickupBundle\Enum\ItemTypeEnum;
use PHPUnit\Framework\TestCase;

class ItemTypeEnumTest extends TestCase
{
    /**
     * 测试枚举值是否符合预期
     */
    public function testEnumValues()
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
    public function testFromValue()
    {
        $this->assertEquals(ItemTypeEnum::DOCUMENT, ItemTypeEnum::from('document'));
        $this->assertEquals(ItemTypeEnum::CLOTHING, ItemTypeEnum::from('clothing'));
        $this->assertEquals(ItemTypeEnum::ELECTRONICS, ItemTypeEnum::from('electronics'));
        $this->assertEquals(ItemTypeEnum::FOOD, ItemTypeEnum::from('food'));
    }

    /**
     * 测试无效值的情况
     */
    public function testInvalidValue()
    {
        $this->expectException(\ValueError::class);
        ItemTypeEnum::from('non_existent_value');
    }

    /**
     * 测试tryFrom方法
     */
    public function testTryFrom()
    {
        $this->assertNull(ItemTypeEnum::tryFrom('non_existent_value'));
        $this->assertEquals(ItemTypeEnum::FRAGILE, ItemTypeEnum::tryFrom('fragile'));
    }
} 