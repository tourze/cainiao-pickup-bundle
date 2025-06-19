<?php

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use PHPUnit\Framework\TestCase;

class LogisticsDetailTest extends TestCase
{
    private LogisticsDetail $logisticsDetail;
    private PickupOrder $order;

    protected function setUp(): void
    {
        $this->order = new PickupOrder();
        $this->order->setOrderCode('TEST123456')
            ->setStatus(OrderStatusEnum::CREATE)
            ->setItemType(ItemTypeEnum::DOCUMENT)
            ->setWeight(1.5);

        $this->logisticsDetail = new LogisticsDetail();
        $this->logisticsDetail->setOrder($this->order)
            ->setMailNo('SF123456789')
            ->setLogisticsStatus('100')
            ->setLogisticsDescription('已揽件')
            ->setLogisticsTime(new \DateTimeImmutable('2023-08-01 10:00:00'));
    }

    public function testGetterSetter_basicProperties(): void
    {
        $this->assertSame($this->order, $this->logisticsDetail->getOrder());
        $this->assertSame('SF123456789', $this->logisticsDetail->getMailNo());
        $this->assertSame('100', $this->logisticsDetail->getLogisticsStatus());
        $this->assertSame('已揽件', $this->logisticsDetail->getLogisticsDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->logisticsDetail->getLogisticsTime());
        $this->assertEquals('2023-08-01 10:00:00', $this->logisticsDetail->getLogisticsTime()->format('Y-m-d H:i:s'));
    }

    public function testGetterSetter_optionalProperties(): void
    {
        $this->logisticsDetail->setCity('北京市')
            ->setArea('朝阳区')
            ->setAddress('三里屯10号')
            ->setCourierName('张三')
            ->setCourierPhone('13800138000');
        
        $this->assertSame('北京市', $this->logisticsDetail->getCity());
        $this->assertSame('朝阳区', $this->logisticsDetail->getArea());
        $this->assertSame('三里屯10号', $this->logisticsDetail->getAddress());
        $this->assertSame('张三', $this->logisticsDetail->getCourierName());
        $this->assertSame('13800138000', $this->logisticsDetail->getCourierPhone());
    }

    public function testFluentInterface_returnsObjectInstance(): void
    {
        $result = $this->logisticsDetail->setCity('北京市');
        $this->assertSame($this->logisticsDetail, $result);
        
        $result = $this->logisticsDetail->setArea('朝阳区');
        $this->assertSame($this->logisticsDetail, $result);
        
        $result = $this->logisticsDetail->setAddress('三里屯10号');
        $this->assertSame($this->logisticsDetail, $result);
    }

    public function testTimestampProperties_canBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        
        $this->logisticsDetail->setCreateTime($now);
        $this->logisticsDetail->setUpdateTime($now);
        
        $this->assertSame($now, $this->logisticsDetail->getCreateTime());
        $this->assertSame($now, $this->logisticsDetail->getUpdateTime());
    }

    public function testLogisticsTimeIsCorrectlySet(): void
    {
        $newTime = new \DateTimeImmutable('2023-08-02 15:30:00');
        $this->logisticsDetail->setLogisticsTime($newTime);
        
        $this->assertSame($newTime, $this->logisticsDetail->getLogisticsTime());
        $this->assertEquals('2023-08-02 15:30:00', $this->logisticsDetail->getLogisticsTime()->format('Y-m-d H:i:s'));
    }

    public function testOrderRelationship_isCorrectlySet(): void
    {
        $newOrder = new PickupOrder();
        $newOrder->setOrderCode('NEW123456')
            ->setStatus(OrderStatusEnum::WAREHOUSE_ACCEPT)
            ->setItemType(ItemTypeEnum::ELECTRONICS)
            ->setWeight(2.5);
            
        $this->logisticsDetail->setOrder($newOrder);
        
        $this->assertSame($newOrder, $this->logisticsDetail->getOrder());
        $this->assertSame('NEW123456', $this->logisticsDetail->getOrder()->getOrderCode());
        $this->assertSame(OrderStatusEnum::WAREHOUSE_ACCEPT, $this->logisticsDetail->getOrder()->getStatus());
    }
} 