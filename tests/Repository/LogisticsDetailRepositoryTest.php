<?php

namespace CainiaoPickupBundle\Tests\Repository;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\LogisticsDetailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogisticsDetailRepositoryTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;
    private EntityManagerInterface|MockObject $entityManager;
    private LogisticsDetailRepository $repository;
    private PickupOrder $order;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);
        
        $this->repository = $this->getMockBuilder(LogisticsDetailRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findBy', 'findOneBy', 'createQueryBuilder'])
            ->getMock();
        
        // 准备测试数据
        $this->order = new PickupOrder();
        $this->order->setOrderCode('TEST123456')
            ->setStatus(OrderStatusEnum::CREATE)
            ->setItemType(ItemTypeEnum::DOCUMENT)
            ->setWeight(1.5);
    }

    public function testFindByOrder_returnsLogisticsDetailsSortedByTime(): void
    {
        // 准备测试数据
        $logisticsDetails = [
            $this->createLogisticsDetail('2023-08-01 15:00:00', '100', '已揽件'),
            $this->createLogisticsDetail('2023-08-01 18:00:00', '500', '运输中'),
            $this->createLogisticsDetail('2023-08-02 09:00:00', '600', '派送中'),
        ];

        // 设置模拟行为
        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(
                ['order' => $this->order],
                ['logisticsTime' => 'DESC']
            )
            ->willReturn($logisticsDetails);

        // 执行测试
        $result = $this->repository->findByOrder($this->order);

        // 断言
        $this->assertCount(3, $result);
        $this->assertSame($logisticsDetails, $result);
    }

    public function testFindLatestByOrder_returnsLatestDetail(): void
    {
        // 准备测试数据
        $latestDetail = $this->createLogisticsDetail('2023-08-02 09:00:00', '600', '派送中');

        // 设置模拟行为
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(
                ['order' => $this->order],
                ['logisticsTime' => 'DESC']
            )
            ->willReturn($latestDetail);

        // 执行测试
        $result = $this->repository->findLatestByOrder($this->order);

        // 断言
        $this->assertSame($latestDetail, $result);
        $this->assertEquals('600', $result->getLogisticsStatus());
        $this->assertEquals('派送中', $result->getLogisticsDescription());
    }


    /**
     * 创建物流详情测试数据
     */
    private function createLogisticsDetail(string $time, string $status, string $description): LogisticsDetail
    {
        $detail = new LogisticsDetail();
        $detail->setOrder($this->order)
            ->setMailNo('SF' . rand(100000, 999999))
            ->setLogisticsStatus($status)
            ->setLogisticsDescription($description)
            ->setLogisticsTime(new \DateTimeImmutable($time));
            
        return $detail;
    }
} 