<?php

namespace CainiaoPickupBundle\Tests\Integration\Repository;

use CainiaoPickupBundle\CainiaoPickupBundle;
use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;

class PickupOrderRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private PickupOrderRepository $repository;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            // Doctrine extensions
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            // Core bundles
            CainiaoPickupBundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->repository = $container->get(PickupOrderRepository::class);

        // 创建Schema
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createTestOrder(string $orderCode, OrderStatusEnum $status): PickupOrder
    {
        // 创建菜鸟配置
        $config = new CainiaoConfig();
        $config->setName('测试配置_' . $orderCode);
        $config->setAppKey('test_key_' . $orderCode);
        $config->setAppSecret('test_secret_' . $orderCode);
        $config->setAccessCode('test_code_' . $orderCode);
        $config->setProviderId('test_provider_' . $orderCode);
        $config->setValid(true);
        $this->entityManager->persist($config);

        // 创建发件人地址
        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人');
        $senderInfo->setMobile('13800138001');
        $senderInfo->setProvinceName('广东省');
        $senderInfo->setCityName('深圳市');
        $senderInfo->setAreaName('南山区');
        $senderInfo->setAddress('科技园');
        $senderInfo->setFullAddressDetail('广东省深圳市南山区科技园');

        // 创建收件人地址
        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人');
        $receiverInfo->setMobile('13800138002');
        $receiverInfo->setProvinceName('北京市');
        $receiverInfo->setCityName('北京市');
        $receiverInfo->setAreaName('朝阳区');
        $receiverInfo->setAddress('三里屯');
        $receiverInfo->setFullAddressDetail('北京市北京市朝阳区三里屯');

        // 创建订单
        $order = new PickupOrder();
        $order->setOrderCode($orderCode);
        $order->setSenderInfo($senderInfo);
        $order->setReceiverInfo($receiverInfo);
        $order->setItemType(ItemTypeEnum::DOCUMENT);
        $order->setWeight(1.0);
        $order->setStatus($status);
        $order->setExternalUserId('test_user_' . substr($orderCode, -3));
        $order->setExternalUserMobile('1380013' . substr($orderCode, -4));
        $order->setConfig($config);

        $this->entityManager->persist($order);
        return $order;
    }

    public function testFindByOrderCode_returnsOrderWhenExists(): void
    {
        $order = $this->createTestOrder('ORDER123', OrderStatusEnum::CREATE);
        $this->entityManager->flush();

        $result = $this->repository->findByOrderCode('ORDER123');
        $this->assertNotNull($result);
        $this->assertSame($order, $result);
        $this->assertEquals('ORDER123', $result->getOrderCode());
    }

    public function testFindByOrderCode_returnsNullWhenNotExists(): void
    {
        $result = $this->repository->findByOrderCode('NONEXISTENT');
        $this->assertNull($result);
    }

    public function testFindByStatus_returnsOrdersWithSpecificStatus(): void
    {
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $order3 = $this->createTestOrder('ORDER3', OrderStatusEnum::WAREHOUSE_ACCEPT);
        $this->entityManager->flush();

        $results = $this->repository->findByStatus(OrderStatusEnum::CREATE);
        $this->assertCount(2, $results);
        $this->assertContains($order1, $results);
        $this->assertContains($order2, $results);
        $this->assertNotContains($order3, $results);
    }

    public function testFindByStatus_returnsEmptyArrayWhenNoOrdersWithStatus(): void
    {
        $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $this->entityManager->flush();

        $results = $this->repository->findByStatus(OrderStatusEnum::WAREHOUSE_ACCEPT);
        $this->assertEmpty($results);
    }

    public function testFindUnfinishedOrders_returnsOnlyUnfinishedOrders(): void
    {
        // 创建未完成的订单
        $createOrder = $this->createTestOrder('CREATE_ORDER', OrderStatusEnum::CREATE);
        $acceptOrder = $this->createTestOrder('ACCEPT_ORDER', OrderStatusEnum::WAREHOUSE_ACCEPT);
        $processOrder = $this->createTestOrder('PROCESS_ORDER', OrderStatusEnum::WAREHOUSE_PROCESS);

        // 创建已完成的订单
        $finishedOrder = $this->createTestOrder('FINISHED_ORDER', OrderStatusEnum::SIGN);
        $cancelledOrder = $this->createTestOrder('CANCELLED_ORDER', OrderStatusEnum::CANCELLED);

        $this->entityManager->flush();

        $results = $this->repository->findUnfinishedOrders();
        $this->assertCount(3, $results);
        $this->assertContains($createOrder, $results);
        $this->assertContains($acceptOrder, $results);
        $this->assertContains($processOrder, $results);
        $this->assertNotContains($finishedOrder, $results);
        $this->assertNotContains($cancelledOrder, $results);
    }

    public function testFindUnfinishedOrders_returnsOrdersOrderedByCreatedAtDesc(): void
    {
        // 创建订单
        $oldOrder = $this->createTestOrder('OLD_ORDER', OrderStatusEnum::CREATE);
        $newOrder = $this->createTestOrder('NEW_ORDER', OrderStatusEnum::CREATE);
        $this->entityManager->flush();

        // 手动设置不同的创建时间
        $oldTime = new \DateTimeImmutable('-1 minute');
        $newTime = new \DateTimeImmutable();
        
        $oldOrder->setCreateTime($oldTime);
        $newOrder->setCreateTime($newTime);
        $this->entityManager->flush();

        $results = $this->repository->findUnfinishedOrders();
        $this->assertCount(2, $results);
        
        // 验证按创建时间倒序排列
        $this->assertEquals('NEW_ORDER', $results[0]->getOrderCode());
        $this->assertEquals('OLD_ORDER', $results[1]->getOrderCode());
    }

    public function testFindUnfinishedOrders_returnsEmptyArrayWhenNoUnfinishedOrders(): void
    {
        $this->createTestOrder('FINISHED_ORDER', OrderStatusEnum::SIGN);
        $this->createTestOrder('CANCELLED_ORDER', OrderStatusEnum::CANCELLED);
        $this->entityManager->flush();

        $results = $this->repository->findUnfinishedOrders();
        $this->assertEmpty($results);
    }

    public function testRepositoryInheritsFromServiceEntityRepository(): void
    {
        $this->assertInstanceOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class, $this->repository);
    }

    public function testRepositoryCanFindById(): void
    {
        $order = $this->createTestOrder('ORDER123', OrderStatusEnum::CREATE);
        $this->entityManager->flush();

        $result = $this->repository->find($order->getId());
        $this->assertNotNull($result);
        $this->assertSame($order, $result);
    }

    public function testRepositoryCanFindAll(): void
    {
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::WAREHOUSE_ACCEPT);
        $this->entityManager->flush();

        $results = $this->repository->findAll();
        $this->assertCount(2, $results);
        $this->assertContains($order1, $results);
        $this->assertContains($order2, $results);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // 清理以避免内存泄漏
        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }
    }
}