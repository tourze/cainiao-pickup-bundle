<?php

namespace CainiaoPickupBundle\Enum;

enum OrderStatusEnum: string
{
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
}
