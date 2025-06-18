<?php

namespace CainiaoPickupBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ItemTypeEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    
    case DOCUMENT = 'document';         // 文件
    case CLOTHING = 'clothing';         // 服装
    case ELECTRONICS = 'electronics';   // 电子产品
    case FOOD = 'food';                 // 食品
    case FRAGILE = 'fragile';           // 易碎品
    case OTHER = 'other';               // 其他
    
    /**
     * 获取物品类型对应的中文描述
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DOCUMENT => '文件',
            self::CLOTHING => '服装',
            self::ELECTRONICS => '电子产品',
            self::FOOD => '食品',
            self::FRAGILE => '易碎品',
            self::OTHER => '其他',
        };
    }
}
