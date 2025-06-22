<?php

namespace CainiaoPickupBundle\Tests\Repository;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CainiaoConfigRepositoryTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;
    private EntityManagerInterface|MockObject $entityManager;
    private CainiaoConfigRepository $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->registry->method('getManagerForClass')
            ->willReturn($this->entityManager);
        
        $this->repository = $this->getMockBuilder(CainiaoConfigRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['findBy', 'find'])
            ->getMock();
    }

    public function testFind_returnsConfigById(): void
    {
        // 准备测试数据
        $configId = 1;
        $config = new CainiaoConfig();
        $config->setName('测试配置')
            ->setAppKey('test_app_key')
            ->setValid(true);
        
        // 设置模拟行为
        $this->repository->expects($this->once())
            ->method('find')
            ->with($configId)
            ->willReturn($config);
        
        // 执行测试
        $result = $this->repository->find($configId);
        
        // 断言
        $this->assertSame($config, $result);
        $this->assertEquals('测试配置', $result->getName());
        $this->assertEquals('test_app_key', $result->getAppKey());
        $this->assertTrue($result->isValid());
    }

} 