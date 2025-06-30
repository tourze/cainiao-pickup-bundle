<?php

namespace CainiaoPickupBundle\Tests\Integration\Repository;

use CainiaoPickupBundle\CainiaoPickupBundle;
use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\LogisticsDetailRepository;
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

class LogisticsDetailRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private LogisticsDetailRepository $repository;
    private PickupOrder $testOrder;

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
        $this->repository = $container->get(LogisticsDetailRepository::class);

        // 创建Schema
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // 创建测试订单
        $this->createTestOrder();
    }

    private function createTestOrder(): void
    {
        // 创建菜鸟配置
        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('test_key');
        $config->setAppSecret('test_secret');
        $config->setAccessCode('test_code');
        $config->setProviderId('test_provider');
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
        $this->testOrder = new PickupOrder();
        $this->testOrder->setOrderCode('ORDER123');
        $this->testOrder->setSenderInfo($senderInfo);
        $this->testOrder->setReceiverInfo($receiverInfo);
        $this->testOrder->setItemType(ItemTypeEnum::DOCUMENT);
        $this->testOrder->setWeight(1.0);
        $this->testOrder->setStatus(OrderStatusEnum::CREATE);
        $this->testOrder->setExternalUserId('test_user_123');
        $this->testOrder->setExternalUserMobile('13800138123');
        $this->testOrder->setConfig($config);

        $this->entityManager->persist($this->testOrder);
        $this->entityManager->flush();
    }

    public function testFindByOrder_returnsOrderedByLogisticsTimeDesc(): void
    {
        // 创建早期物流详情
        $oldDetail = new LogisticsDetail();
        $oldDetail->setOrder($this->testOrder);
        $oldDetail->setMailNo('MAIL001');
        $oldDetail->setLogisticsStatus('已发货');
        $oldDetail->setLogisticsDescription('商品已从仓库发出');
        $oldDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $this->entityManager->persist($oldDetail);

        // 创建最新物流详情
        $newDetail = new LogisticsDetail();
        $newDetail->setOrder($this->testOrder);
        $newDetail->setMailNo('MAIL001');
        $newDetail->setLogisticsStatus('派送中');
        $newDetail->setLogisticsDescription('快递员正在派送');
        $newDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-02 14:00:00'));
        $this->entityManager->persist($newDetail);

        $this->entityManager->flush();

        // 测试按时间倒序返回
        $results = $this->repository->findByOrder($this->testOrder);
        $this->assertCount(2, $results);
        $this->assertSame($newDetail, $results[0]);
        $this->assertSame($oldDetail, $results[1]);
    }

    public function testFindByOrder_returnsEmptyArrayForOrderWithoutLogistics(): void
    {
        // 测试没有物流详情的订单
        $results = $this->repository->findByOrder($this->testOrder);
        $this->assertEmpty($results);
    }

    public function testFindLatestByOrder_returnsLatestLogisticsDetail(): void
    {
        // 创建多个物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('商品已从仓库发出');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $this->entityManager->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('运输中');
        $detail2->setLogisticsDescription('商品在运输途中');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 15:00:00'));
        $this->entityManager->persist($detail2);

        $latestDetail = new LogisticsDetail();
        $latestDetail->setOrder($this->testOrder);
        $latestDetail->setMailNo('MAIL001');
        $latestDetail->setLogisticsStatus('派送中');
        $latestDetail->setLogisticsDescription('快递员正在派送');
        $latestDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-02 09:00:00'));
        $this->entityManager->persist($latestDetail);

        $this->entityManager->flush();

        // 测试获取最新物流详情
        $result = $this->repository->findLatestByOrder($this->testOrder);
        $this->assertNotNull($result);
        $this->assertSame($latestDetail, $result);
        $this->assertEquals('派送中', $result->getLogisticsStatus());
    }

    public function testFindLatestByOrder_returnsNullForOrderWithoutLogistics(): void
    {
        // 测试没有物流详情的订单
        $result = $this->repository->findLatestByOrder($this->testOrder);
        $this->assertNull($result);
    }

    public function testDeleteByOrder_removesAllLogisticsForOrder(): void
    {
        // 创建多个物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('商品已从仓库发出');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $this->entityManager->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('派送中');
        $detail2->setLogisticsDescription('快递员正在派送');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-02 09:00:00'));
        $this->entityManager->persist($detail2);

        $this->entityManager->flush();

        // 确认数据存在
        $this->assertCount(2, $this->repository->findByOrder($this->testOrder));

        // 删除订单的所有物流详情
        $this->repository->deleteByOrder($this->testOrder);

        // 刷新实体管理器以确保删除操作生效
        $this->entityManager->clear();

        // 验证删除成功
        $this->testOrder = $this->entityManager->find(PickupOrder::class, $this->testOrder->getId());
        $this->assertEmpty($this->repository->findByOrder($this->testOrder));
    }

    public function testDeleteByOrder_doesNotAffectOtherOrders(): void
    {
        // 创建第二个配置
        $config2 = new CainiaoConfig();
        $config2->setName('测试配置2');
        $config2->setAppKey('test_key2');
        $config2->setAppSecret('test_secret2');
        $config2->setAccessCode('test_code2');
        $config2->setProviderId('test_provider2');
        $config2->setValid(true);
        $this->entityManager->persist($config2);

        // 创建第二个订单
        $senderInfo2 = new AddressInfo();
        $senderInfo2->setName('发件人2');
        $senderInfo2->setMobile('13800138003');
        $senderInfo2->setProvinceName('上海市');
        $senderInfo2->setCityName('上海市');
        $senderInfo2->setAreaName('浦东新区');
        $senderInfo2->setAddress('陆家嘴');
        $senderInfo2->setFullAddressDetail('上海市上海市浦东新区陆家嘴');

        $receiverInfo2 = new AddressInfo();
        $receiverInfo2->setName('收件人2');
        $receiverInfo2->setMobile('13800138004');
        $receiverInfo2->setProvinceName('江苏省');
        $receiverInfo2->setCityName('南京市');
        $receiverInfo2->setAreaName('玄武区');
        $receiverInfo2->setAddress('新街口');
        $receiverInfo2->setFullAddressDetail('江苏省南京市玄武区新街口');

        $order2 = new PickupOrder();
        $order2->setOrderCode('ORDER456');
        $order2->setSenderInfo($senderInfo2);
        $order2->setReceiverInfo($receiverInfo2);
        $order2->setItemType(ItemTypeEnum::OTHER);
        $order2->setWeight(2.0);
        $order2->setStatus(OrderStatusEnum::CREATE);
        $order2->setExternalUserId('test_user_456');
        $order2->setExternalUserMobile('13800138456');
        $order2->setConfig($config2);
        $this->entityManager->persist($order2);

        // 为第一个订单创建物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('商品已从仓库发出');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $this->entityManager->persist($detail1);

        // 为第二个订单创建物流详情
        $detail2 = new LogisticsDetail();
        $detail2->setOrder($order2);
        $detail2->setMailNo('MAIL002');
        $detail2->setLogisticsStatus('已发货');
        $detail2->setLogisticsDescription('商品已从仓库发出');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 11:00:00'));
        $this->entityManager->persist($detail2);

        $this->entityManager->flush();

        // 删除第一个订单的物流详情
        $this->repository->deleteByOrder($this->testOrder);

        // 刷新实体管理器
        $this->entityManager->clear();

        // 重新获取订单实体
        $this->testOrder = $this->entityManager->find(PickupOrder::class, $this->testOrder->getId());
        $order2 = $this->entityManager->find(PickupOrder::class, $order2->getId());

        // 验证第一个订单的物流详情被删除，第二个订单不受影响
        $this->assertEmpty($this->repository->findByOrder($this->testOrder));
        $this->assertCount(1, $this->repository->findByOrder($order2));
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