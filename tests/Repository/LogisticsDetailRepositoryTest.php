<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Repository;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\LogisticsDetailRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(LogisticsDetailRepository::class)]
#[RunTestsInSeparateProcesses]
final class LogisticsDetailRepositoryTest extends AbstractRepositoryTestCase
{
    private LogisticsDetailRepository $repository;

    private PickupOrder $testOrder;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(LogisticsDetailRepository::class);

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
        self::getEntityManager()->persist($config);

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

        self::getEntityManager()->persist($this->testOrder);
        self::getEntityManager()->flush();
    }

    public function testFindByOrderReturnsOrderedByLogisticsTimeDesc(): void
    {
        // 创建早期物流详情
        $oldDetail = new LogisticsDetail();
        $oldDetail->setOrder($this->testOrder);
        $oldDetail->setMailNo('MAIL001');
        $oldDetail->setLogisticsStatus('已发货');
        $oldDetail->setLogisticsDescription('商品已从仓库发出');
        $oldDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($oldDetail);

        // 创建最新物流详情
        $newDetail = new LogisticsDetail();
        $newDetail->setOrder($this->testOrder);
        $newDetail->setMailNo('MAIL001');
        $newDetail->setLogisticsStatus('派送中');
        $newDetail->setLogisticsDescription('快递员正在派送');
        $newDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-02 14:00:00'));
        self::getEntityManager()->persist($newDetail);

        self::getEntityManager()->flush();

        // 测试按时间倒序返回
        $results = $this->repository->findByOrder($this->testOrder);
        $this->assertCount(2, $results);
        $this->assertSame($newDetail, $results[0]);
        $this->assertSame($oldDetail, $results[1]);
    }

    public function testFindByOrderReturnsEmptyArrayForOrderWithoutLogistics(): void
    {
        // 测试没有物流详情的订单
        $results = $this->repository->findByOrder($this->testOrder);
        $this->assertEmpty($results);
    }

    public function testFindLatestByOrderReturnsLatestLogisticsDetail(): void
    {
        // 创建多个物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('商品已从仓库发出');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('运输中');
        $detail2->setLogisticsDescription('商品在运输途中');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 15:00:00'));
        self::getEntityManager()->persist($detail2);

        $latestDetail = new LogisticsDetail();
        $latestDetail->setOrder($this->testOrder);
        $latestDetail->setMailNo('MAIL001');
        $latestDetail->setLogisticsStatus('派送中');
        $latestDetail->setLogisticsDescription('快递员正在派送');
        $latestDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-02 09:00:00'));
        self::getEntityManager()->persist($latestDetail);

        self::getEntityManager()->flush();

        // 测试获取最新物流详情
        $result = $this->repository->findLatestByOrder($this->testOrder);
        $this->assertNotNull($result);
        $this->assertSame($latestDetail, $result);
        $this->assertEquals('派送中', $result->getLogisticsStatus());
    }

    public function testFindLatestByOrderReturnsNullForOrderWithoutLogistics(): void
    {
        // 测试没有物流详情的订单
        $result = $this->repository->findLatestByOrder($this->testOrder);
        $this->assertNull($result);
    }

    public function testDeleteByOrderRemovesAllLogisticsForOrder(): void
    {
        // 创建多个物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('商品已从仓库发出');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('派送中');
        $detail2->setLogisticsDescription('快递员正在派送');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-02 09:00:00'));
        self::getEntityManager()->persist($detail2);

        self::getEntityManager()->flush();

        // 确认数据存在
        $this->assertCount(2, $this->repository->findByOrder($this->testOrder));

        // 删除订单的所有物流详情
        $this->repository->deleteByOrder($this->testOrder);

        // 刷新实体管理器以确保删除操作生效
        self::getEntityManager()->clear();

        // 验证删除成功
        $foundTestOrder = self::getEntityManager()->find(PickupOrder::class, $this->testOrder->getId());
        if (null === $foundTestOrder) {
            self::fail('Failed to reload test order from database');
        }
        $this->testOrder = $foundTestOrder;
        $this->assertEmpty($this->repository->findByOrder($this->testOrder));
    }

    public function testDeleteByOrderDoesNotAffectOtherOrders(): void
    {
        // 创建第二个配置
        $config2 = new CainiaoConfig();
        $config2->setName('测试配置2');
        $config2->setAppKey('test_key2');
        $config2->setAppSecret('test_secret2');
        $config2->setAccessCode('test_code2');
        $config2->setProviderId('test_provider2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

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
        self::getEntityManager()->persist($order2);

        // 为第一个订单创建物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('商品已从仓库发出');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        // 为第二个订单创建物流详情
        $detail2 = new LogisticsDetail();
        $detail2->setOrder($order2);
        $detail2->setMailNo('MAIL002');
        $detail2->setLogisticsStatus('已发货');
        $detail2->setLogisticsDescription('商品已从仓库发出');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 11:00:00'));
        self::getEntityManager()->persist($detail2);

        self::getEntityManager()->flush();

        // 删除第一个订单的物流详情
        $this->repository->deleteByOrder($this->testOrder);

        // 刷新实体管理器
        self::getEntityManager()->clear();

        // 重新获取订单实体
        $foundTestOrder = self::getEntityManager()->find(PickupOrder::class, $this->testOrder->getId());
        $foundOrder2 = self::getEntityManager()->find(PickupOrder::class, $order2->getId());

        if (null === $foundTestOrder || null === $foundOrder2) {
            self::fail('Failed to reload orders from database');
        }

        $this->testOrder = $foundTestOrder;
        $order2 = $foundOrder2;

        // 验证第一个订单的物流详情被删除，第二个订单不受影响
        $this->assertEmpty($this->repository->findByOrder($this->testOrder));
        $this->assertCount(1, $this->repository->findByOrder($order2));
    }

    public function testFindOneByWithNonMatchingMailNoShouldReturnNull(): void
    {
        // 创建物流详情
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('MAIL001');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('商品已从仓库发出');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['mailNo' => '不存在的运单号']);

        $this->assertNull($result);
    }

    public function testFindAllWhenLogisticsRecordsExistShouldReturnArrayOfEntities(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('商品已从仓库发出');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL002');
        $detail2->setLogisticsStatus('运输中');
        $detail2->setLogisticsDescription('商品正在运输中');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detail2);

        self::getEntityManager()->flush();

        $result = $this->repository->findAll();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(LogisticsDetail::class, $result);
    }

    public function testFindWithNonExistingLogisticsIdShouldReturnNull(): void
    {
        $result = $this->repository->find(999999);

        $this->assertNull($result);
    }

    public function testSave(): void
    {
        // 测试保存新实体
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('SAVE_TEST_MAIL');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('测试保存的物流详情');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detail->setCity('深圳市');
        $detail->setArea('南山区');
        $detail->setAddress('科技园南区');
        $detail->setCourierName('张三');
        $detail->setCourierPhone('13800138000');

        $this->repository->save($detail);

        $this->assertNotNull($detail->getId());
        $this->assertEquals('SAVE_TEST_MAIL', $detail->getMailNo());

        // 验证实体已保存到数据库
        $found = $this->repository->find($detail->getId());
        $this->assertInstanceOf(LogisticsDetail::class, $found);
        $this->assertEquals('SAVE_TEST_MAIL', $found->getMailNo());
        $this->assertEquals('深圳市', $found->getCity());
        $this->assertEquals('张三', $found->getCourierName());
    }

    public function testSaveWithFlushFalse(): void
    {
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('NO_FLUSH_MAIL');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('测试不立即刷新的保存');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));

        $this->repository->save($detail, false);

        // 实体应该被标记为持久化，但ID可能还没有分配
        $this->assertNotNull($detail->getId());

        // 手动刷新以确保数据已保存
        self::getEntityManager()->flush();

        $found = $this->repository->find($detail->getId());
        $this->assertInstanceOf(LogisticsDetail::class, $found);
        $this->assertEquals('NO_FLUSH_MAIL', $found->getMailNo());
    }

    public function testRemove(): void
    {
        // 创建并保存物流详情
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('REMOVE_TEST_MAIL');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('测试删除的物流详情');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail);
        self::getEntityManager()->flush();

        $id = $detail->getId();

        // 删除实体
        $this->repository->remove($detail);

        // 验证实体已被删除
        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testRemoveWithFlushFalse(): void
    {
        // 创建并保存物流详情
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('REMOVE_NO_FLUSH_MAIL');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('测试不立即刷新的删除');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail);
        self::getEntityManager()->flush();

        $id = $detail->getId();

        // 删除但不立即刷新
        $this->repository->remove($detail, false);

        // 此时实体应该还存在于数据库中
        $found = $this->repository->find($id);
        $this->assertInstanceOf(LogisticsDetail::class, $found);

        // 手动刷新后实体应该被删除
        self::getEntityManager()->flush();
        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testFindByCityIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有城市信息的物流详情
        $detailWithCity = new LogisticsDetail();
        $detailWithCity->setOrder($this->testOrder);
        $detailWithCity->setMailNo('MAIL_WITH_CITY');
        $detailWithCity->setLogisticsStatus('已发货');
        $detailWithCity->setLogisticsDescription('有城市信息的物流详情');
        $detailWithCity->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithCity->setCity('深圳市');
        self::getEntityManager()->persist($detailWithCity);

        // 创建没有城市信息的物流详情
        $detailWithoutCity = new LogisticsDetail();
        $detailWithoutCity->setOrder($this->testOrder);
        $detailWithoutCity->setMailNo('MAIL_WITHOUT_CITY');
        $detailWithoutCity->setLogisticsStatus('已发货');
        $detailWithoutCity->setLogisticsDescription('没有城市信息的物流详情');
        $detailWithoutCity->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutCity);

        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['city' => null]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('MAIL_WITHOUT_CITY', $result[0]->getMailNo());
        $this->assertNull($result[0]->getCity());
    }

    public function testCountByCityIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有城市信息的物流详情
        $detailWithCity = new LogisticsDetail();
        $detailWithCity->setOrder($this->testOrder);
        $detailWithCity->setMailNo('MAIL_WITH_CITY');
        $detailWithCity->setLogisticsStatus('已发货');
        $detailWithCity->setLogisticsDescription('有城市信息的物流详情');
        $detailWithCity->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithCity->setCity('深圳市');
        self::getEntityManager()->persist($detailWithCity);

        // 创建两个没有城市信息的物流详情
        $detailWithoutCity1 = new LogisticsDetail();
        $detailWithoutCity1->setOrder($this->testOrder);
        $detailWithoutCity1->setMailNo('MAIL_WITHOUT_CITY_1');
        $detailWithoutCity1->setLogisticsStatus('已发货');
        $detailWithoutCity1->setLogisticsDescription('没有城市信息的物流详情1');
        $detailWithoutCity1->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutCity1);

        $detailWithoutCity2 = new LogisticsDetail();
        $detailWithoutCity2->setOrder($this->testOrder);
        $detailWithoutCity2->setMailNo('MAIL_WITHOUT_CITY_2');
        $detailWithoutCity2->setLogisticsStatus('运输中');
        $detailWithoutCity2->setLogisticsDescription('没有城市信息的物流详情2');
        $detailWithoutCity2->setLogisticsTime(new \DateTimeImmutable('2023-01-03 10:00:00'));
        self::getEntityManager()->persist($detailWithoutCity2);

        self::getEntityManager()->flush();

        $result = $this->repository->count(['city' => null]);

        $this->assertEquals(2, $result);
    }

    public function testFindByOrderRelation(): void
    {
        // 创建第二个订单
        $config2 = new CainiaoConfig();
        $config2->setName('测试配置2');
        $config2->setAppKey('test_key2');
        $config2->setAppSecret('test_secret2');
        $config2->setAccessCode('test_code2');
        $config2->setProviderId('test_provider2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

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
        self::getEntityManager()->persist($order2);

        // 为第一个订单创建物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('订单1的物流详情');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        // 为第二个订单创建物流详情
        $detail2 = new LogisticsDetail();
        $detail2->setOrder($order2);
        $detail2->setMailNo('MAIL002');
        $detail2->setLogisticsStatus('已发货');
        $detail2->setLogisticsDescription('订单2的物流详情');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detail2);

        self::getEntityManager()->flush();

        // 测试查找特定订单的物流详情
        $result = $this->repository->findBy(['order' => $this->testOrder]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('MAIL001', $result[0]->getMailNo());
        $this->assertEquals('订单1的物流详情', $result[0]->getLogisticsDescription());
    }

    public function testCountByOrderRelation(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 为测试订单创建多个物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL001');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('订单1的物流详情1');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL002');
        $detail2->setLogisticsStatus('运输中');
        $detail2->setLogisticsDescription('订单1的物流详情2');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detail2);

        self::getEntityManager()->flush();

        $result = $this->repository->count(['order' => $this->testOrder]);

        $this->assertEquals(2, $result);
    }

    public function testFindOneByWithOrderByShouldReturnFirstMatchingEntity(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建多个相同状态的物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL002');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('第二个物流详情');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('已发货');
        $detail2->setLogisticsDescription('第一个物流详情');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail2);

        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['logisticsStatus' => '已发货'], ['mailNo' => 'ASC']);

        $this->assertInstanceOf(LogisticsDetail::class, $result);
        $this->assertEquals('MAIL001', $result->getMailNo());
    }

    public function testFindByAreaIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有地区信息的物流详情
        $detailWithArea = new LogisticsDetail();
        $detailWithArea->setOrder($this->testOrder);
        $detailWithArea->setMailNo('MAIL_WITH_AREA');
        $detailWithArea->setLogisticsStatus('已发货');
        $detailWithArea->setLogisticsDescription('有地区信息的物流详情');
        $detailWithArea->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithArea->setArea('南山区');
        self::getEntityManager()->persist($detailWithArea);

        // 创建没有地区信息的物流详情
        $detailWithoutArea = new LogisticsDetail();
        $detailWithoutArea->setOrder($this->testOrder);
        $detailWithoutArea->setMailNo('MAIL_WITHOUT_AREA');
        $detailWithoutArea->setLogisticsStatus('已发货');
        $detailWithoutArea->setLogisticsDescription('没有地区信息的物流详情');
        $detailWithoutArea->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutArea);

        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['area' => null]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('MAIL_WITHOUT_AREA', $result[0]->getMailNo());
        $this->assertNull($result[0]->getArea());
    }

    public function testCountByAreaIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有地区信息的物流详情
        $detailWithArea = new LogisticsDetail();
        $detailWithArea->setOrder($this->testOrder);
        $detailWithArea->setMailNo('MAIL_WITH_AREA');
        $detailWithArea->setLogisticsStatus('已发货');
        $detailWithArea->setLogisticsDescription('有地区信息的物流详情');
        $detailWithArea->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithArea->setArea('南山区');
        self::getEntityManager()->persist($detailWithArea);

        // 创建两个没有地区信息的物流详情
        $detailWithoutArea1 = new LogisticsDetail();
        $detailWithoutArea1->setOrder($this->testOrder);
        $detailWithoutArea1->setMailNo('MAIL_WITHOUT_AREA_1');
        $detailWithoutArea1->setLogisticsStatus('已发货');
        $detailWithoutArea1->setLogisticsDescription('没有地区信息的物流详情1');
        $detailWithoutArea1->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutArea1);

        $detailWithoutArea2 = new LogisticsDetail();
        $detailWithoutArea2->setOrder($this->testOrder);
        $detailWithoutArea2->setMailNo('MAIL_WITHOUT_AREA_2');
        $detailWithoutArea2->setLogisticsStatus('运输中');
        $detailWithoutArea2->setLogisticsDescription('没有地区信息的物流详情2');
        $detailWithoutArea2->setLogisticsTime(new \DateTimeImmutable('2023-01-03 10:00:00'));
        self::getEntityManager()->persist($detailWithoutArea2);

        self::getEntityManager()->flush();

        $result = $this->repository->count(['area' => null]);

        $this->assertEquals(2, $result);
    }

    public function testFindByAddressIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有地址信息的物流详情
        $detailWithAddress = new LogisticsDetail();
        $detailWithAddress->setOrder($this->testOrder);
        $detailWithAddress->setMailNo('MAIL_WITH_ADDRESS');
        $detailWithAddress->setLogisticsStatus('已发货');
        $detailWithAddress->setLogisticsDescription('有地址信息的物流详情');
        $detailWithAddress->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithAddress->setAddress('科技园南区');
        self::getEntityManager()->persist($detailWithAddress);

        // 创建没有地址信息的物流详情
        $detailWithoutAddress = new LogisticsDetail();
        $detailWithoutAddress->setOrder($this->testOrder);
        $detailWithoutAddress->setMailNo('MAIL_WITHOUT_ADDRESS');
        $detailWithoutAddress->setLogisticsStatus('已发货');
        $detailWithoutAddress->setLogisticsDescription('没有地址信息的物流详情');
        $detailWithoutAddress->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutAddress);

        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['address' => null]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('MAIL_WITHOUT_ADDRESS', $result[0]->getMailNo());
        $this->assertNull($result[0]->getAddress());
    }

    public function testCountByAddressIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有地址信息的物流详情
        $detailWithAddress = new LogisticsDetail();
        $detailWithAddress->setOrder($this->testOrder);
        $detailWithAddress->setMailNo('MAIL_WITH_ADDRESS');
        $detailWithAddress->setLogisticsStatus('已发货');
        $detailWithAddress->setLogisticsDescription('有地址信息的物流详情');
        $detailWithAddress->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithAddress->setAddress('科技园南区');
        self::getEntityManager()->persist($detailWithAddress);

        // 创建没有地址信息的物流详情
        $detailWithoutAddress = new LogisticsDetail();
        $detailWithoutAddress->setOrder($this->testOrder);
        $detailWithoutAddress->setMailNo('MAIL_WITHOUT_ADDRESS');
        $detailWithoutAddress->setLogisticsStatus('已发货');
        $detailWithoutAddress->setLogisticsDescription('没有地址信息的物流详情');
        $detailWithoutAddress->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutAddress);

        self::getEntityManager()->flush();

        $result = $this->repository->count(['address' => null]);

        $this->assertEquals(1, $result);
    }

    public function testFindByCourierNameIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有快递员名称的物流详情
        $detailWithCourier = new LogisticsDetail();
        $detailWithCourier->setOrder($this->testOrder);
        $detailWithCourier->setMailNo('MAIL_WITH_COURIER');
        $detailWithCourier->setLogisticsStatus('已发货');
        $detailWithCourier->setLogisticsDescription('有快递员信息的物流详情');
        $detailWithCourier->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithCourier->setCourierName('张三');
        self::getEntityManager()->persist($detailWithCourier);

        // 创建没有快递员名称的物流详情
        $detailWithoutCourier = new LogisticsDetail();
        $detailWithoutCourier->setOrder($this->testOrder);
        $detailWithoutCourier->setMailNo('MAIL_WITHOUT_COURIER');
        $detailWithoutCourier->setLogisticsStatus('已发货');
        $detailWithoutCourier->setLogisticsDescription('没有快递员信息的物流详情');
        $detailWithoutCourier->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutCourier);

        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['courierName' => null]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('MAIL_WITHOUT_COURIER', $result[0]->getMailNo());
        $this->assertNull($result[0]->getCourierName());
    }

    public function testCountByCourierNameIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有快递员名称的物流详情
        $detailWithCourier = new LogisticsDetail();
        $detailWithCourier->setOrder($this->testOrder);
        $detailWithCourier->setMailNo('MAIL_WITH_COURIER');
        $detailWithCourier->setLogisticsStatus('已发货');
        $detailWithCourier->setLogisticsDescription('有快递员信息的物流详情');
        $detailWithCourier->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithCourier->setCourierName('张三');
        self::getEntityManager()->persist($detailWithCourier);

        // 创建没有快递员名称的物流详情
        $detailWithoutCourier = new LogisticsDetail();
        $detailWithoutCourier->setOrder($this->testOrder);
        $detailWithoutCourier->setMailNo('MAIL_WITHOUT_COURIER');
        $detailWithoutCourier->setLogisticsStatus('已发货');
        $detailWithoutCourier->setLogisticsDescription('没有快递员信息的物流详情');
        $detailWithoutCourier->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutCourier);

        self::getEntityManager()->flush();

        $result = $this->repository->count(['courierName' => null]);

        $this->assertEquals(1, $result);
    }

    public function testFindByCourierPhoneIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有快递员电话的物流详情
        $detailWithPhone = new LogisticsDetail();
        $detailWithPhone->setOrder($this->testOrder);
        $detailWithPhone->setMailNo('MAIL_WITH_PHONE');
        $detailWithPhone->setLogisticsStatus('已发货');
        $detailWithPhone->setLogisticsDescription('有快递员电话的物流详情');
        $detailWithPhone->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithPhone->setCourierPhone('13800138000');
        self::getEntityManager()->persist($detailWithPhone);

        // 创建没有快递员电话的物流详情
        $detailWithoutPhone = new LogisticsDetail();
        $detailWithoutPhone->setOrder($this->testOrder);
        $detailWithoutPhone->setMailNo('MAIL_WITHOUT_PHONE');
        $detailWithoutPhone->setLogisticsStatus('已发货');
        $detailWithoutPhone->setLogisticsDescription('没有快递员电话的物流详情');
        $detailWithoutPhone->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutPhone);

        self::getEntityManager()->flush();

        $result = $this->repository->findBy(['courierPhone' => null]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('MAIL_WITHOUT_PHONE', $result[0]->getMailNo());
        $this->assertNull($result[0]->getCourierPhone());
    }

    public function testCountByCourierPhoneIsNull(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建有快递员电话的物流详情
        $detailWithPhone = new LogisticsDetail();
        $detailWithPhone->setOrder($this->testOrder);
        $detailWithPhone->setMailNo('MAIL_WITH_PHONE');
        $detailWithPhone->setLogisticsStatus('已发货');
        $detailWithPhone->setLogisticsDescription('有快递员电话的物流详情');
        $detailWithPhone->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $detailWithPhone->setCourierPhone('13800138000');
        self::getEntityManager()->persist($detailWithPhone);

        // 创建没有快递员电话的物流详情
        $detailWithoutPhone = new LogisticsDetail();
        $detailWithoutPhone->setOrder($this->testOrder);
        $detailWithoutPhone->setMailNo('MAIL_WITHOUT_PHONE');
        $detailWithoutPhone->setLogisticsStatus('已发货');
        $detailWithoutPhone->setLogisticsDescription('没有快递员电话的物流详情');
        $detailWithoutPhone->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detailWithoutPhone);

        self::getEntityManager()->flush();

        $result = $this->repository->count(['courierPhone' => null]);

        $this->assertEquals(1, $result);
    }

    public function testFindOneByWithDescendingOrderShouldReturnLastInSortOrder(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建多个物流详情，按时间倒序测试
        $earliestDetail = new LogisticsDetail();
        $earliestDetail->setOrder($this->testOrder);
        $earliestDetail->setMailNo('MAIL001');
        $earliestDetail->setLogisticsStatus('已发货');
        $earliestDetail->setLogisticsDescription('最早的物流详情');
        $earliestDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 08:00:00'));
        self::getEntityManager()->persist($earliestDetail);

        $middleDetail = new LogisticsDetail();
        $middleDetail->setOrder($this->testOrder);
        $middleDetail->setMailNo('MAIL002');
        $middleDetail->setLogisticsStatus('已发货');
        $middleDetail->setLogisticsDescription('中间的物流详情');
        $middleDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 12:00:00'));
        self::getEntityManager()->persist($middleDetail);

        $latestDetail = new LogisticsDetail();
        $latestDetail->setOrder($this->testOrder);
        $latestDetail->setMailNo('MAIL003');
        $latestDetail->setLogisticsStatus('已发货');
        $latestDetail->setLogisticsDescription('最新的物流详情');
        $latestDetail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 16:00:00'));
        self::getEntityManager()->persist($latestDetail);

        self::getEntityManager()->flush();

        // 按时间倒序查找，应该返回最新的记录
        $result = $this->repository->findOneBy(['logisticsStatus' => '已发货'], ['logisticsTime' => 'DESC']);

        $this->assertInstanceOf(LogisticsDetail::class, $result);
        $this->assertEquals('MAIL003', $result->getMailNo());
        $this->assertEquals('最新的物流详情', $result->getLogisticsDescription());
    }

    public function testFindOneByWithMultipleOrderByColumnsShouldRespectPriority(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建具有相同状态但不同时间和运单号的物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL003');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('相同时间，运单号较大');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('已发货');
        $detail2->setLogisticsDescription('相同时间，运单号较小');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail2);

        $detail3 = new LogisticsDetail();
        $detail3->setOrder($this->testOrder);
        $detail3->setMailNo('MAIL002');
        $detail3->setLogisticsStatus('已发货');
        $detail3->setLogisticsDescription('更早时间');
        $detail3->setLogisticsTime(new \DateTimeImmutable('2023-01-01 08:00:00'));
        self::getEntityManager()->persist($detail3);

        self::getEntityManager()->flush();

        // 按时间升序优先，然后按运单号升序，应该返回最早时间的记录
        $result = $this->repository->findOneBy(['logisticsStatus' => '已发货'], ['logisticsTime' => 'ASC', 'mailNo' => 'ASC']);

        $this->assertInstanceOf(LogisticsDetail::class, $result);
        $this->assertEquals('MAIL002', $result->getMailNo());
        $this->assertEquals('更早时间', $result->getLogisticsDescription());
    }

    public function testFindOneByWithSecondaryOrderByColumnShouldRespectSecondarySort(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建具有相同时间但不同运单号的物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL003');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('运单号较大');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('已发货');
        $detail2->setLogisticsDescription('运单号较小');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail2);

        $detail3 = new LogisticsDetail();
        $detail3->setOrder($this->testOrder);
        $detail3->setMailNo('MAIL002');
        $detail3->setLogisticsStatus('已发货');
        $detail3->setLogisticsDescription('运单号中等');
        $detail3->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail3);

        self::getEntityManager()->flush();

        // 按时间升序优先（此时都相同），然后按运单号倒序，应该返回运单号最大的记录
        $result = $this->repository->findOneBy(['logisticsStatus' => '已发货'], ['logisticsTime' => 'ASC', 'mailNo' => 'DESC']);

        $this->assertInstanceOf(LogisticsDetail::class, $result);
        $this->assertEquals('MAIL003', $result->getMailNo());
        $this->assertEquals('运单号较大', $result->getLogisticsDescription());
    }

    public function testFindOneByWithNoOrderByShouldReturnFirstMatch(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建多个相同状态的物流详情
        $detail1 = new LogisticsDetail();
        $detail1->setOrder($this->testOrder);
        $detail1->setMailNo('MAIL002');
        $detail1->setLogisticsStatus('已发货');
        $detail1->setLogisticsDescription('第二个物流详情');
        $detail1->setLogisticsTime(new \DateTimeImmutable('2023-01-02 10:00:00'));
        self::getEntityManager()->persist($detail1);

        $detail2 = new LogisticsDetail();
        $detail2->setOrder($this->testOrder);
        $detail2->setMailNo('MAIL001');
        $detail2->setLogisticsStatus('已发货');
        $detail2->setLogisticsDescription('第一个物流详情');
        $detail2->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail2);

        self::getEntityManager()->flush();

        // 不指定排序，测试默认行为
        $result = $this->repository->findOneBy(['logisticsStatus' => '已发货']);

        $this->assertInstanceOf(LogisticsDetail::class, $result);
        $this->assertEquals('已发货', $result->getLogisticsStatus());
        // 不验证具体是哪个记录，因为没有排序时顺序不确定
        $this->assertContains($result->getMailNo(), ['MAIL001', 'MAIL002']);
    }

    public function testFindByOrderWithId(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建测试数据
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('MAIL001');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('测试订单ID查询');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail);
        self::getEntityManager()->flush();

        // 使用订单ID进行查询
        $result = $this->repository->findBy(['order' => $this->testOrder->getId()]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('MAIL001', $result[0]->getMailNo());
    }

    public function testCountByOrderWithId(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 为测试订单创建多个物流详情
        for ($i = 1; $i <= 3; ++$i) {
            $detail = new LogisticsDetail();
            $detail->setOrder($this->testOrder);
            $detail->setMailNo("MAIL00{$i}");
            $detail->setLogisticsStatus('已发货');
            $detail->setLogisticsDescription("物流详情 {$i}");
            $detail->setLogisticsTime(new \DateTimeImmutable("2023-01-0{$i} 10:00:00"));
            self::getEntityManager()->persist($detail);
        }
        self::getEntityManager()->flush();

        // 使用订单ID进行计数
        $result = $this->repository->count(['order' => $this->testOrder->getId()]);

        $this->assertEquals(3, $result);
    }

    public function testFindOneByOrderWithId(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建测试数据
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('MAIL001');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('测试单个订单查询');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail);
        self::getEntityManager()->flush();

        // 使用订单ID进行单个查询
        $result = $this->repository->findOneBy(['order' => $this->testOrder->getId()]);

        $this->assertInstanceOf(LogisticsDetail::class, $result);
        $this->assertEquals('MAIL001', $result->getMailNo());
        $this->assertEquals('测试单个订单查询', $result->getLogisticsDescription());
    }

    public function testCountByAssociationOrderShouldReturnCorrectNumber(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 为测试订单创建物流详情
        for ($i = 1; $i <= 4; ++$i) {
            $detail = new LogisticsDetail();
            $detail->setOrder($this->testOrder);
            $detail->setMailNo("MAIL00{$i}");
            $detail->setLogisticsStatus('已发货');
            $detail->setLogisticsDescription("订单物流详情 {$i}");
            $detail->setLogisticsTime(new \DateTimeImmutable("2023-01-0{$i} 10:00:00"));
            self::getEntityManager()->persist($detail);
        }

        // 创建另一个订单及其物流详情
        $config2 = new CainiaoConfig();
        $config2->setName('测试配置2');
        $config2->setAppKey('test_key2');
        $config2->setAppSecret('test_secret2');
        $config2->setAccessCode('test_code2');
        $config2->setProviderId('test_provider2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

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
        self::getEntityManager()->persist($order2);

        // 为第二个订单创建2个物流详情
        for ($i = 1; $i <= 2; ++$i) {
            $detail = new LogisticsDetail();
            $detail->setOrder($order2);
            $detail->setMailNo("OTHER_MAIL_00{$i}");
            $detail->setLogisticsStatus('运输中');
            $detail->setLogisticsDescription("其他订单物流详情 {$i}");
            $detail->setLogisticsTime(new \DateTimeImmutable("2023-01-0{$i} 12:00:00"));
            self::getEntityManager()->persist($detail);
        }

        self::getEntityManager()->flush();

        $count = $this->repository->count(['order' => $this->testOrder]);
        $this->assertSame(4, $count);
    }

    public function testFindOneByAssociationOrderShouldReturnMatchingEntity(): void
    {
        // 清理旧数据
        $allDetails = $this->repository->findAll();
        foreach ($allDetails as $detail) {
            self::assertInstanceOf(LogisticsDetail::class, $detail);
            $this->repository->remove($detail);
        }

        // 创建测试数据
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('ASSOCIATION_TEST_MAIL');
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('关联查询测试');
        $detail->setLogisticsTime(new \DateTimeImmutable('2023-01-01 10:00:00'));
        self::getEntityManager()->persist($detail);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['order' => $this->testOrder]);

        $this->assertInstanceOf(LogisticsDetail::class, $result);
        $this->assertEquals('ASSOCIATION_TEST_MAIL', $result->getMailNo());
        $this->assertEquals('关联查询测试', $result->getLogisticsDescription());
        $this->assertSame($this->testOrder, $result->getOrder());
    }

    /**
     * @return LogisticsDetailRepository
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $detail = new LogisticsDetail();
        $detail->setOrder($this->testOrder);
        $detail->setMailNo('TEST_MAIL_' . uniqid());
        $detail->setLogisticsStatus('已发货');
        $detail->setLogisticsDescription('测试物流详情_' . uniqid());
        $detail->setLogisticsTime(new \DateTimeImmutable());
        $detail->setCity('深圳市');
        $detail->setArea('南山区');
        $detail->setAddress('科技园');
        $detail->setCourierName('张三');
        $detail->setCourierPhone('13800138000');

        return $detail;
    }
}
