<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Repository;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PickupOrderRepository::class)]
#[RunTestsInSeparateProcesses]
final class PickupOrderRepositoryTest extends AbstractRepositoryTestCase
{
    private PickupOrderRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PickupOrderRepository::class);

        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' !== $currentTest) {
            // 大多数原有测试假设数据库是空的
            $this->clearAllOrders();
        }
    }

    private function clearAllOrders(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM ' . PickupOrder::class)->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . AddressInfo::class)->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . CainiaoConfig::class)->execute();
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

        self::getEntityManager()->persist($order);

        return $order;
    }

    public function testFindByOrderCodeReturnsOrderWhenExists(): void
    {
        $order = $this->createTestOrder('ORDER123', OrderStatusEnum::CREATE);
        self::getEntityManager()->flush();

        $result = $this->repository->findByOrderCode('ORDER123');
        $this->assertNotNull($result);
        $this->assertSame($order, $result);
        $this->assertEquals('ORDER123', $result->getOrderCode());
    }

    public function testFindByOrderCodeReturnsNullWhenNotExists(): void
    {
        $result = $this->repository->findByOrderCode('NONEXISTENT');
        $this->assertNull($result);
    }

    public function testFindByStatusReturnsOrdersWithSpecificStatus(): void
    {
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $order3 = $this->createTestOrder('ORDER3', OrderStatusEnum::WAREHOUSE_ACCEPT);
        self::getEntityManager()->flush();

        $results = $this->repository->findByStatus(OrderStatusEnum::CREATE);
        $this->assertCount(2, $results);
        $this->assertContains($order1, $results);
        $this->assertContains($order2, $results);
        $this->assertNotContains($order3, $results);
    }

    public function testFindByStatusReturnsEmptyArrayWhenNoOrdersWithStatus(): void
    {
        $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        self::getEntityManager()->flush();

        $results = $this->repository->findByStatus(OrderStatusEnum::WAREHOUSE_ACCEPT);
        $this->assertEmpty($results);
    }

    public function testFindUnfinishedOrdersReturnsOnlyUnfinishedOrders(): void
    {
        // 创建未完成的订单
        $createOrder = $this->createTestOrder('CREATE_ORDER', OrderStatusEnum::CREATE);
        $acceptOrder = $this->createTestOrder('ACCEPT_ORDER', OrderStatusEnum::WAREHOUSE_ACCEPT);
        $processOrder = $this->createTestOrder('PROCESS_ORDER', OrderStatusEnum::WAREHOUSE_PROCESS);

        // 创建已完成的订单
        $finishedOrder = $this->createTestOrder('FINISHED_ORDER', OrderStatusEnum::SIGN);
        $cancelledOrder = $this->createTestOrder('CANCELLED_ORDER', OrderStatusEnum::CANCELLED);

        self::getEntityManager()->flush();

        $results = $this->repository->findUnfinishedOrders();
        $this->assertCount(3, $results);
        $this->assertContains($createOrder, $results);
        $this->assertContains($acceptOrder, $results);
        $this->assertContains($processOrder, $results);
        $this->assertNotContains($finishedOrder, $results);
        $this->assertNotContains($cancelledOrder, $results);
    }

    public function testFindUnfinishedOrdersReturnsOrdersOrderedByCreatedAtDesc(): void
    {
        // 创建订单
        $oldOrder = $this->createTestOrder('OLD_ORDER', OrderStatusEnum::CREATE);
        $newOrder = $this->createTestOrder('NEW_ORDER', OrderStatusEnum::CREATE);
        self::getEntityManager()->flush();

        // 手动设置不同的创建时间
        $oldTime = new \DateTimeImmutable('-1 minute');
        $newTime = new \DateTimeImmutable();

        $oldOrder->setCreateTime($oldTime);
        $newOrder->setCreateTime($newTime);
        self::getEntityManager()->flush();

        $results = $this->repository->findUnfinishedOrders();
        $this->assertCount(2, $results);

        // 验证按创建时间倒序排列
        $this->assertEquals('NEW_ORDER', $results[0]->getOrderCode());
        $this->assertEquals('OLD_ORDER', $results[1]->getOrderCode());
    }

    public function testFindUnfinishedOrdersReturnsEmptyArrayWhenNoUnfinishedOrders(): void
    {
        $this->createTestOrder('FINISHED_ORDER', OrderStatusEnum::SIGN);
        $this->createTestOrder('CANCELLED_ORDER', OrderStatusEnum::CANCELLED);
        self::getEntityManager()->flush();

        $results = $this->repository->findUnfinishedOrders();
        $this->assertEmpty($results);
    }

    public function testRepositoryCanFindById(): void
    {
        $order = $this->createTestOrder('ORDER123', OrderStatusEnum::CREATE);
        self::getEntityManager()->flush();

        $result = $this->repository->find($order->getId());
        $this->assertNotNull($result);
        $this->assertSame($order, $result);
    }

    public function testRepositoryCanFindAll(): void
    {
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::WAREHOUSE_ACCEPT);
        self::getEntityManager()->flush();

        $results = $this->repository->findAll();
        $this->assertCount(2, $results);
        $this->assertContains($order1, $results);
        $this->assertContains($order2, $results);
    }

    public function testSave(): void
    {
        // 创建新订单
        $order = $this->createTestOrder('SAVE_TEST', OrderStatusEnum::CREATE);

        // 保存订单
        $this->repository->save($order, true);

        // 验证订单被保存
        $savedOrder = $this->repository->findOneBy(['orderCode' => 'SAVE_TEST']);
        $this->assertNotNull($savedOrder);
        self::assertInstanceOf(PickupOrder::class, $savedOrder);
        $this->assertEquals('SAVE_TEST', $savedOrder->getOrderCode());
        $this->assertEquals(OrderStatusEnum::CREATE, $savedOrder->getStatus());

        // 测试更新现有订单
        $savedOrder->setStatus(OrderStatusEnum::WAREHOUSE_ACCEPT);
        $this->repository->save($savedOrder, true);

        // 验证更新生效
        $updatedOrder = $this->repository->findOneBy(['orderCode' => 'SAVE_TEST']);
        $this->assertNotNull($updatedOrder);
        self::assertInstanceOf(PickupOrder::class, $updatedOrder);
        $this->assertEquals(OrderStatusEnum::WAREHOUSE_ACCEPT, $updatedOrder->getStatus());
    }

    public function testRemove(): void
    {
        // 创建并保存订单
        $order = $this->createTestOrder('REMOVE_TEST', OrderStatusEnum::CREATE);
        self::getEntityManager()->flush();
        $orderId = $order->getId();

        // 确认订单存在
        $existingOrder = $this->repository->find($orderId);
        $this->assertNotNull($existingOrder);

        // 删除订单
        $this->repository->remove($order, true);

        // 验证订单被删除
        $deletedOrder = $this->repository->find($orderId);
        $this->assertNull($deletedOrder);
    }

    public function testFindByConfigAssociation(): void
    {
        // 创建两个不同的配置
        $config1 = new CainiaoConfig();
        $config1->setName('配置1');
        $config1->setAppKey('key1');
        $config1->setAppSecret('secret1');
        $config1->setAccessCode('code1');
        $config1->setProviderId('provider1');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new CainiaoConfig();
        $config2->setName('配置2');
        $config2->setAppKey('key2');
        $config2->setAppSecret('secret2');
        $config2->setAccessCode('code2');
        $config2->setProviderId('provider2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

        // 创建使用不同配置的订单
        $order1 = $this->createTestOrder('CONFIG1_ORDER', OrderStatusEnum::CREATE);
        $order1->setConfig($config1);

        $order2 = $this->createTestOrder('CONFIG2_ORDER', OrderStatusEnum::CREATE);
        $order2->setConfig($config2);

        self::getEntityManager()->flush();

        // 根据配置查找订单
        $results = $this->repository->findBy(['config' => $config1]);
        $this->assertCount(1, $results);
        $this->assertSame($order1, $results[0]);

        $results = $this->repository->findBy(['config' => $config2]);
        $this->assertCount(1, $results);
        $this->assertSame($order2, $results[0]);
    }

    public function testCountByConfigAssociation(): void
    {
        // 创建两个不同的配置
        $config1 = new CainiaoConfig();
        $config1->setName('配置1');
        $config1->setAppKey('key1');
        $config1->setAppSecret('secret1');
        $config1->setAccessCode('code1');
        $config1->setProviderId('provider1');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new CainiaoConfig();
        $config2->setName('配置2');
        $config2->setAppKey('key2');
        $config2->setAppSecret('secret2');
        $config2->setAccessCode('code2');
        $config2->setProviderId('provider2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

        // 创建使用不同配置的订单
        $order1 = $this->createTestOrder('CONFIG1_ORDER1', OrderStatusEnum::CREATE);
        $order1->setConfig($config1);

        $order2 = $this->createTestOrder('CONFIG1_ORDER2', OrderStatusEnum::CREATE);
        $order2->setConfig($config1);

        $order3 = $this->createTestOrder('CONFIG2_ORDER', OrderStatusEnum::CREATE);
        $order3->setConfig($config2);

        self::getEntityManager()->flush();

        // 根据配置统计订单数量
        $count1 = $this->repository->count(['config' => $config1]);
        $this->assertEquals(2, $count1);

        $count2 = $this->repository->count(['config' => $config2]);
        $this->assertEquals(1, $count2);
    }

    public function testFindByNullableFieldIsNull(): void
    {
        // 创建有备注和无备注的订单
        $orderWithRemark = $this->createTestOrder('ORDER_WITH_REMARK', OrderStatusEnum::CREATE);
        $orderWithRemark->setRemark('这是备注');

        $orderWithoutRemark = $this->createTestOrder('ORDER_WITHOUT_REMARK', OrderStatusEnum::CREATE);
        // remark 默认为 null

        self::getEntityManager()->flush();

        // 查找没有备注的订单
        $results = $this->repository->findBy(['remark' => null]);
        $this->assertCount(1, $results);
        $this->assertSame($orderWithoutRemark, $results[0]);

        // 验证有备注的订单不在结果中
        $this->assertNotContains($orderWithRemark, $results);
    }

    public function testCountByNullableFieldIsNull(): void
    {
        // 创建有备注和无备注的订单
        $orderWithRemark = $this->createTestOrder('ORDER_WITH_REMARK', OrderStatusEnum::CREATE);
        $orderWithRemark->setRemark('这是备注');

        $orderWithoutRemark1 = $this->createTestOrder('ORDER_WITHOUT_REMARK1', OrderStatusEnum::CREATE);
        $orderWithoutRemark2 = $this->createTestOrder('ORDER_WITHOUT_REMARK2', OrderStatusEnum::CREATE);
        // remark 默认为 null

        self::getEntityManager()->flush();

        // 统计没有备注的订单数量
        $count = $this->repository->count(['remark' => null]);
        $this->assertEquals(2, $count);
    }

    public function testFindByExternalUserId(): void
    {
        // 创建不同外部用户ID的订单
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $order1->setExternalUserId('user001');

        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $order2->setExternalUserId('user001');

        $order3 = $this->createTestOrder('ORDER3', OrderStatusEnum::CREATE);
        $order3->setExternalUserId('user002');

        self::getEntityManager()->flush();

        // 根据外部用户ID查找订单
        $results = $this->repository->findBy(['externalUserId' => 'user001']);
        $this->assertCount(2, $results);
        $this->assertContains($order1, $results);
        $this->assertContains($order2, $results);
        $this->assertNotContains($order3, $results);
    }

    public function testFindByItemType(): void
    {
        // 创建不同物品类型的订单
        $order1 = $this->createTestOrder('DOC_ORDER', OrderStatusEnum::CREATE);
        $order1->setItemType(ItemTypeEnum::DOCUMENT);

        $order2 = $this->createTestOrder('CLOTHING_ORDER', OrderStatusEnum::CREATE);
        $order2->setItemType(ItemTypeEnum::CLOTHING);

        $order3 = $this->createTestOrder('DOC_ORDER2', OrderStatusEnum::CREATE);
        $order3->setItemType(ItemTypeEnum::DOCUMENT);

        self::getEntityManager()->flush();

        // 根据物品类型查找订单
        $results = $this->repository->findBy(['itemType' => ItemTypeEnum::DOCUMENT]);
        $this->assertCount(2, $results);
        $this->assertContains($order1, $results);
        $this->assertContains($order3, $results);
        $this->assertNotContains($order2, $results);
    }

    public function testFindByWeight(): void
    {
        // 创建不同重量的订单
        $order1 = $this->createTestOrder('LIGHT_ORDER', OrderStatusEnum::CREATE);
        $order1->setWeight(0.5);

        $order2 = $this->createTestOrder('HEAVY_ORDER', OrderStatusEnum::CREATE);
        $order2->setWeight(2.0);

        $order3 = $this->createTestOrder('LIGHT_ORDER2', OrderStatusEnum::CREATE);
        $order3->setWeight(0.5);

        self::getEntityManager()->flush();

        // 根据重量查找订单
        $results = $this->repository->findBy(['weight' => 0.5]);
        $this->assertCount(2, $results);
        $this->assertContains($order1, $results);
        $this->assertContains($order3, $results);
        $this->assertNotContains($order2, $results);
    }

    public function testFindOneByAssociationConfigShouldReturnMatchingEntity(): void
    {
        // 创建两个不同的配置
        $config1 = new CainiaoConfig();
        $config1->setName('配置1');
        $config1->setAppKey('key1');
        $config1->setAppSecret('secret1');
        $config1->setAccessCode('code1');
        $config1->setProviderId('provider1');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new CainiaoConfig();
        $config2->setName('配置2');
        $config2->setAppKey('key2');
        $config2->setAppSecret('secret2');
        $config2->setAccessCode('code2');
        $config2->setProviderId('provider2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

        // 创建使用不同配置的订单
        $order1 = $this->createTestOrder('CONFIG1_ORDER', OrderStatusEnum::CREATE);
        $order1->setConfig($config1);

        $order2 = $this->createTestOrder('CONFIG2_ORDER', OrderStatusEnum::CREATE);
        $order2->setConfig($config2);

        self::getEntityManager()->flush();

        // 根据配置查找订单
        $result = $this->repository->findOneBy(['config' => $config1]);
        $this->assertNotNull($result);
        $this->assertSame($order1, $result);

        $result = $this->repository->findOneBy(['config' => $config2]);
        $this->assertNotNull($result);
        $this->assertSame($order2, $result);
    }

    public function testFindOneByAssociationSenderInfoShouldReturnMatchingEntity(): void
    {
        // 创建订单1，发件人是深圳
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $senderInfo1 = $order1->getSenderInfo();
        $senderInfo1->setCityName('深圳市');

        // 创建订单2，发件人是广州
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $senderInfo2 = $order2->getSenderInfo();
        $senderInfo2->setCityName('广州市');

        self::getEntityManager()->flush();

        // 根据发件人信息查找订单
        $result = $this->repository->findOneBy(['senderInfo' => $senderInfo1]);
        $this->assertNotNull($result);
        $this->assertSame($order1, $result);

        $result = $this->repository->findOneBy(['senderInfo' => $senderInfo2]);
        $this->assertNotNull($result);
        $this->assertSame($order2, $result);
    }

    public function testFindOneByAssociationReceiverInfoShouldReturnMatchingEntity(): void
    {
        // 创建订单1，收件人是北京
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $receiverInfo1 = $order1->getReceiverInfo();
        $receiverInfo1->setCityName('北京市');

        // 创建订单2，收件人是上海
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $receiverInfo2 = $order2->getReceiverInfo();
        $receiverInfo2->setCityName('上海市');

        self::getEntityManager()->flush();

        // 根据收件人信息查找订单
        $result = $this->repository->findOneBy(['receiverInfo' => $receiverInfo1]);
        $this->assertNotNull($result);
        $this->assertSame($order1, $result);

        $result = $this->repository->findOneBy(['receiverInfo' => $receiverInfo2]);
        $this->assertNotNull($result);
        $this->assertSame($order2, $result);
    }

    public function testFindBySenderInfoAssociation(): void
    {
        // 创建订单1，发件人是深圳
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $senderInfo1 = $order1->getSenderInfo();
        $senderInfo1->setCityName('深圳市');

        // 创建订单2，发件人是广州
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $senderInfo2 = $order2->getSenderInfo();
        $senderInfo2->setCityName('广州市');

        // 创建订单3，发件人也是深圳
        $order3 = $this->createTestOrder('ORDER3', OrderStatusEnum::CREATE);
        $senderInfo3 = $order3->getSenderInfo();
        $senderInfo3->setCityName('深圳市');

        self::getEntityManager()->flush();

        // 根据发件人信息查找订单
        $results = $this->repository->findBy(['senderInfo' => $senderInfo1]);
        $this->assertCount(1, $results);
        $this->assertSame($order1, $results[0]);

        $results = $this->repository->findBy(['senderInfo' => $senderInfo2]);
        $this->assertCount(1, $results);
        $this->assertSame($order2, $results[0]);
    }

    public function testFindByReceiverInfoAssociation(): void
    {
        // 创建订单1，收件人是北京
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $receiverInfo1 = $order1->getReceiverInfo();
        $receiverInfo1->setCityName('北京市');

        // 创建订单2，收件人是上海
        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $receiverInfo2 = $order2->getReceiverInfo();
        $receiverInfo2->setCityName('上海市');

        self::getEntityManager()->flush();

        // 根据收件人信息查找订单
        $results = $this->repository->findBy(['receiverInfo' => $receiverInfo1]);
        $this->assertCount(1, $results);
        $this->assertSame($order1, $results[0]);

        $results = $this->repository->findBy(['receiverInfo' => $receiverInfo2]);
        $this->assertCount(1, $results);
        $this->assertSame($order2, $results[0]);
    }

    public function testCountBySenderInfoAssociation(): void
    {
        // 创建两个订单，每个有自己独立的发件人信息
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $senderInfo1 = $order1->getSenderInfo();
        $senderInfo1->setCityName('深圳市');

        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $senderInfo2 = $order2->getSenderInfo();
        $senderInfo2->setCityName('广州市');

        $order3 = $this->createTestOrder('ORDER3', OrderStatusEnum::CREATE);
        $senderInfo3 = $order3->getSenderInfo();
        $senderInfo3->setCityName('上海市');

        self::getEntityManager()->flush();

        // 根据发件人信息统计订单数量（每个发件人信息只对应一个订单）
        $count = $this->repository->count(['senderInfo' => $senderInfo1]);
        $this->assertEquals(1, $count);

        $count = $this->repository->count(['senderInfo' => $senderInfo2]);
        $this->assertEquals(1, $count);

        $count = $this->repository->count(['senderInfo' => $senderInfo3]);
        $this->assertEquals(1, $count);
    }

    public function testCountByReceiverInfoAssociation(): void
    {
        // 创建三个订单，每个有自己独立的收件人信息
        $order1 = $this->createTestOrder('ORDER1', OrderStatusEnum::CREATE);
        $receiverInfo1 = $order1->getReceiverInfo();
        $receiverInfo1->setCityName('北京市');

        $order2 = $this->createTestOrder('ORDER2', OrderStatusEnum::CREATE);
        $receiverInfo2 = $order2->getReceiverInfo();
        $receiverInfo2->setCityName('上海市');

        $order3 = $this->createTestOrder('ORDER3', OrderStatusEnum::CREATE);
        $receiverInfo3 = $order3->getReceiverInfo();
        $receiverInfo3->setCityName('广州市');

        self::getEntityManager()->flush();

        // 根据收件人信息统计订单数量（每个收件人信息只对应一个订单）
        $count = $this->repository->count(['receiverInfo' => $receiverInfo1]);
        $this->assertEquals(1, $count);

        $count = $this->repository->count(['receiverInfo' => $receiverInfo2]);
        $this->assertEquals(1, $count);

        $count = $this->repository->count(['receiverInfo' => $receiverInfo3]);
        $this->assertEquals(1, $count);
    }

    public function testFindByMailNoIsNull(): void
    {
        // 创建有运单号和无运单号的订单
        $orderWithMailNo = $this->createTestOrder('ORDER_WITH_MAIL', OrderStatusEnum::CREATE);
        $orderWithMailNo->setMailNo('YT123456789');

        $orderWithoutMailNo = $this->createTestOrder('ORDER_WITHOUT_MAIL', OrderStatusEnum::CREATE);
        // mailNo 默认为 null

        self::getEntityManager()->flush();

        // 查找没有运单号的订单
        $results = $this->repository->findBy(['mailNo' => null]);
        $this->assertCount(1, $results);
        $this->assertSame($orderWithoutMailNo, $results[0]);

        // 验证有运单号的订单不在结果中
        $this->assertNotContains($orderWithMailNo, $results);
    }

    public function testFindByCourierNameIsNull(): void
    {
        // 创建有快递员姓名和无快递员姓名的订单
        $orderWithCourier = $this->createTestOrder('ORDER_WITH_COURIER', OrderStatusEnum::CREATE);
        $orderWithCourier->setCourierName('张三');

        $orderWithoutCourier = $this->createTestOrder('ORDER_WITHOUT_COURIER', OrderStatusEnum::CREATE);
        // courierName 默认为 null

        self::getEntityManager()->flush();

        // 查找没有快递员姓名的订单
        $results = $this->repository->findBy(['courierName' => null]);
        $this->assertCount(1, $results);
        $this->assertSame($orderWithoutCourier, $results[0]);

        // 验证有快递员姓名的订单不在结果中
        $this->assertNotContains($orderWithCourier, $results);
    }

    public function testCountByMailNoIsNull(): void
    {
        // 创建有运单号和无运单号的订单
        $orderWithMailNo = $this->createTestOrder('ORDER_WITH_MAIL', OrderStatusEnum::CREATE);
        $orderWithMailNo->setMailNo('YT123456789');

        $orderWithoutMailNo1 = $this->createTestOrder('ORDER_WITHOUT_MAIL1', OrderStatusEnum::CREATE);
        $orderWithoutMailNo2 = $this->createTestOrder('ORDER_WITHOUT_MAIL2', OrderStatusEnum::CREATE);
        // mailNo 默认为 null

        self::getEntityManager()->flush();

        // 统计没有运单号的订单数量
        $count = $this->repository->count(['mailNo' => null]);
        $this->assertEquals(2, $count);
    }

    public function testCountByCourierNameIsNull(): void
    {
        // 创建有快递员姓名和无快递员姓名的订单
        $orderWithCourier = $this->createTestOrder('ORDER_WITH_COURIER', OrderStatusEnum::CREATE);
        $orderWithCourier->setCourierName('张三');

        $orderWithoutCourier1 = $this->createTestOrder('ORDER_WITHOUT_COURIER1', OrderStatusEnum::CREATE);
        $orderWithoutCourier2 = $this->createTestOrder('ORDER_WITHOUT_COURIER2', OrderStatusEnum::CREATE);
        // courierName 默认为 null

        self::getEntityManager()->flush();

        // 统计没有快递员姓名的订单数量
        $count = $this->repository->count(['courierName' => null]);
        $this->assertEquals(2, $count);
    }

    public function testCountByAssociationConfigShouldReturnCorrectNumber(): void
    {
        // 创建配置
        $config1 = new CainiaoConfig();
        $config1->setName('配置1');
        $config1->setAppKey('key1');
        $config1->setAppSecret('secret1');
        $config1->setAccessCode('code1');
        $config1->setProviderId('provider1');
        $config1->setValid(true);
        self::getEntityManager()->persist($config1);

        $config2 = new CainiaoConfig();
        $config2->setName('配置2');
        $config2->setAppKey('key2');
        $config2->setAppSecret('secret2');
        $config2->setAccessCode('code2');
        $config2->setProviderId('provider2');
        $config2->setValid(true);
        self::getEntityManager()->persist($config2);

        // 创建4个使用config1的订单
        $order1 = $this->createTestOrder('CONFIG1_ORDER1', OrderStatusEnum::CREATE);
        $order1->setConfig($config1);

        $order2 = $this->createTestOrder('CONFIG1_ORDER2', OrderStatusEnum::CREATE);
        $order2->setConfig($config1);

        $order3 = $this->createTestOrder('CONFIG1_ORDER3', OrderStatusEnum::CREATE);
        $order3->setConfig($config1);

        $order4 = $this->createTestOrder('CONFIG1_ORDER4', OrderStatusEnum::CREATE);
        $order4->setConfig($config1);

        // 创建2个使用config2的订单
        $order5 = $this->createTestOrder('CONFIG2_ORDER1', OrderStatusEnum::CREATE);
        $order5->setConfig($config2);

        $order6 = $this->createTestOrder('CONFIG2_ORDER2', OrderStatusEnum::CREATE);
        $order6->setConfig($config2);

        self::getEntityManager()->flush();

        // 根据配置统计订单数量
        $count = $this->repository->count(['config' => $config1]);
        $this->assertEquals(4, $count);

        $count = $this->repository->count(['config' => $config2]);
        $this->assertEquals(2, $count);
    }

    /**
     * @return PickupOrderRepository
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        // 创建一个完整的订单实体，确保能够被持久化到数据库
        // 需要手动持久化关联的实体，因为 PickupOrder 的 config 关联没有配置级联持久化

        $entityManager = self::getEntityManager();

        // 创建菜鸟配置并持久化
        $config = new CainiaoConfig();
        $config->setName('测试配置_' . uniqid());
        $config->setAppKey('test_key_' . uniqid());
        $config->setAppSecret('test_secret_' . uniqid());
        $config->setAccessCode('test_code_' . uniqid());
        $config->setProviderId('test_provider_' . uniqid());
        $config->setValid(true);
        $entityManager->persist($config);

        // 创建发件人地址（会通过级联自动持久化）
        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人_' . uniqid());
        $senderInfo->setMobile('13800138001');
        $senderInfo->setProvinceName('广东省');
        $senderInfo->setCityName('深圳市');
        $senderInfo->setAreaName('南山区');
        $senderInfo->setAddress('科技园');
        $senderInfo->setFullAddressDetail('广东省深圳市南山区科技园');

        // 创建收件人地址（会通过级联自动持久化）
        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人_' . uniqid());
        $receiverInfo->setMobile('13800138002');
        $receiverInfo->setProvinceName('北京市');
        $receiverInfo->setCityName('北京市');
        $receiverInfo->setAreaName('朝阳区');
        $receiverInfo->setAddress('三里屯');
        $receiverInfo->setFullAddressDetail('北京市北京市朝阳区三里屯');

        // 创建订单但不持久化
        $order = new PickupOrder();
        $order->setOrderCode('TEST_ORDER_' . uniqid());
        $order->setSenderInfo($senderInfo);
        $order->setReceiverInfo($receiverInfo);
        $order->setItemType(ItemTypeEnum::DOCUMENT);
        $order->setWeight(1.0);
        $order->setStatus(OrderStatusEnum::CREATE);
        $order->setExternalUserId('test_user_' . uniqid());
        $order->setExternalUserMobile('13800138000');
        $order->setConfig($config);

        return $order;
    }
}
