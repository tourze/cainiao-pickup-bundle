<?php

namespace CainiaoPickupBundle\Enum;

enum ItemTypeEnum: string
{
    case DOCUMENT = 'document';         // 文件
    case CLOTHING = 'clothing';         // 服装
    case ELECTRONICS = 'electronics';   // 电子产品
    case FOOD = 'food';                 // 食品
    case FRAGILE = 'fragile';           // 易碎品
    case OTHER = 'other';               // 其他
}
