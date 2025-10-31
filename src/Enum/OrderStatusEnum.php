<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum OrderStatusEnum: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case CANCELLED = 'cancelled';       // 已取消

    // 订单段状态
    case CREATE = '0';           // 已下单

    // 仓段状态
    case WAREHOUSE_ACCEPT = '100';         // 仓库已接单
    case WAREHOUSE_PROCESS = '150';        // 仓库处理中
    case WAREHOUSE_CONFIRMED = '200';      // 已出库
    case CONSIGN = '300';                  // 已发货

    // 配送段状态
    case ACCEPT = '400';                   // 已揽件
    case LH_HO = '430';                    // 干线运输中

    // 进口 海外作业段状态
    case JK_HW_ACCEPT = '470';             // 本地已揽件
    case JK_HWC = '471';                   // 仓作业中
    case JK_BSC = '472';                   // 保税仓作业中
    case JK_GFC = '473';                   // GFC仓作业中
    case JK_GJGX = '474';                  // 干线运输中
    case CC_HO = '475';                    // 清关中

    // 配送段状态
    case TRANSPORT = '500';                // 运输中
    case DELIVERING = '600';               // 派送中
    case FAILED = '700';                   // 物流异常提醒
    case REJECT = '800';                   // 拒签

    // case WAITTING_DELIVERY = '800';      // 待提货 // 注释掉，800 已用于 REJECT
    case AGENT_SIGN = '900';               // 待取件
    case STA_DELIVERING = '901';           // 驿站派送中
    case OTHER_SIGN = '950';               // 他人代收
    case SIGN = '1000';                    // 已签收
    case ORDER_TRANSER = '1100';           // 已转单
    case REVERSE_RETURN = '1200';          // 退货返回

    /**
     * 获取订单状态对应的中文描述
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::CANCELLED => '已取消',
            self::CREATE => '已下单',
            self::WAREHOUSE_ACCEPT => '仓库已接单',
            self::WAREHOUSE_PROCESS => '仓库处理中',
            self::WAREHOUSE_CONFIRMED => '已出库',
            self::CONSIGN => '已发货',
            self::ACCEPT => '已揽件',
            self::LH_HO => '干线运输中',
            self::JK_HW_ACCEPT => '本地已揽件',
            self::JK_HWC => '仓作业中',
            self::JK_BSC => '保税仓作业中',
            self::JK_GFC => 'GFC仓作业中',
            self::JK_GJGX => '干线运输中',
            self::CC_HO => '清关中',
            self::TRANSPORT => '运输中',
            self::DELIVERING => '派送中',
            self::FAILED => '物流异常提醒',
            self::REJECT => '拒签',
            self::AGENT_SIGN => '待取件',
            self::STA_DELIVERING => '驿站派送中',
            self::OTHER_SIGN => '他人代收',
            self::SIGN => '已签收',
            self::ORDER_TRANSER => '已转单',
            self::REVERSE_RETURN => '退货返回',
        };
    }
}
