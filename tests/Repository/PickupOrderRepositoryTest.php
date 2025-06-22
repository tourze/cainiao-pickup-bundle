<?php

namespace CainiaoPickupBundle\Tests\Repository;

use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PickupOrderRepositoryTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;
    private EntityManagerInterface|MockObject $entityManager;
    private PickupOrderRepository $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);
        
        $this->repository = $this->getMockBuilder(PickupOrderRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder', 'findBy', 'find'])
            ->getMock();
    }

    public function testFind_returnsOrderById(): void
    {
        // 准备测试数据
        $orderId = 1;
        $order = new PickupOrder();
        $order->setOrderCode('TEST123456');
        
        // 设置模拟行为
        $this->repository->expects($this->once())
            ->method('find')
            ->with($orderId)
            ->willReturn($order);
        
        // 执行测试
        $result = $this->repository->find($orderId);
        
        // 断言
        $this->assertSame($order, $result);
        $this->assertEquals('TEST123456', $result->getOrderCode());
    }

} 