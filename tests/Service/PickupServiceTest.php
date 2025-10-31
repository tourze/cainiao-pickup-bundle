<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Service;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Exception\OrderCannotBeCancelledException;
use CainiaoPickupBundle\Exception\OrderModificationFailedException;
use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use CainiaoPickupBundle\Service\PickupService;
use CainiaoPickupBundle\Tests\AbstractTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(PickupService::class)]
#[RunTestsInSeparateProcesses]
final class PickupServiceTest extends AbstractTestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;

    /** @var MockObject&PickupOrderRepository */
    private MockObject $pickupOrderRepository;

    /** @var MockObject&LoggerInterface */
    private MockObject $logger;

    /** @var MockObject&CainiaoHttpClient */
    private MockObject $cainiaoHttpClient;

    /** @var MockObject&CainiaoConfigRepository */
    private MockObject $cainiaoConfigRepository;

    private PickupService $pickupService;

    private CainiaoConfig $validConfig;

    private PickupOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        // 创建 mock 依赖
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->pickupOrderRepository = $this->createMock(PickupOrderRepository::class);
        $this->cainiaoConfigRepository = $this->createMock(CainiaoConfigRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cainiaoHttpClient = $this->createMock(CainiaoHttpClient::class);

        // 直接创建服务实例（单元测试方式）
        $this->pickupService = new PickupService(
            $this->entityManager,
            $this->pickupOrderRepository,
            $this->logger,
            $this->cainiaoHttpClient,
            $this->cainiaoConfigRepository
        );

        // 准备测试数据
        $this->validConfig = new CainiaoConfig();
        $this->validConfig->setName('测试配置');
        $this->validConfig->setAppKey('app_key');
        $this->validConfig->setAppSecret('app_secret');
        $this->validConfig->setProviderId('cainiao_provider');
        $this->validConfig->setAccessCode('access_code');
        $this->validConfig->setApiGateway('https://api.example.com/cainiao');
        $this->validConfig->setValid(true);

        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人');
        $senderInfo->setMobile('13800138000');
        $senderInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人');
        $receiverInfo->setMobile('13900139000');
        $receiverInfo->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $this->order = new PickupOrder();
        $this->order->setOrderCode('TEST123456');
        $this->order->setSenderInfo($senderInfo);
        $this->order->setReceiverInfo($receiverInfo);
        $this->order->setItemType(ItemTypeEnum::DOCUMENT);
        $this->order->setWeight(1.5);
        $this->order->setConfig($this->validConfig);
    }

    public function testCreatePickupOrderWithValidData(): void
    {
        // 设置模拟行为
        $this->cainiaoConfigRepository->expects($this->once())
            ->method('findValidConfig')
            ->willReturn($this->validConfig)
        ;

        // 注意：由于使用了真实的EntityManager，我们不验证persist/flush调用

        $this->cainiaoHttpClient->expects($this->once())
            ->method('createPickupOrder')
            ->with(self::isInstanceOf(PickupOrder::class))
            ->willReturnCallback(function (PickupOrder $order): void {
                $order->setCainiaoOrderCode('CN123456');
                $order->setMailNo('SF123456789');
            })
        ;

        // 执行测试
        $orderData = [
            'senderName' => '发件人',
            'senderPhone' => '13800138000',
            'senderFullAddress' => '北京市朝阳区三里屯街道10号',
            'receiverName' => '收件人',
            'receiverPhone' => '13900139000',
            'receiverFullAddress' => '上海市浦东新区张江高科园区88号',
            'itemType' => 'document',
            'weight' => 1.5,
            'remark' => '测试备注',
            'expectPickupTimeStart' => '2023-08-01 09:00:00',
            'expectPickupTimeEnd' => '2023-08-01 18:00:00',
            'itemQuantity' => '1',
            'itemValue' => 100.00,
            'externalUserId' => 'user123',
            'externalUserMobile' => '13888888888',
        ];

        $result = $this->pickupService->createPickupOrder($orderData);

        // 断言
        $this->assertInstanceOf(PickupOrder::class, $result);
        $this->assertSame('CN123456', $result->getCainiaoOrderCode());
        $this->assertSame('SF123456789', $result->getMailNo());
        $this->assertSame('document', $result->getItemType()->value);
        $this->assertSame(1.5, $result->getWeight());
        $this->assertSame('测试备注', $result->getRemark());
        $this->assertSame('user123', $result->getExternalUserId());
        $this->assertSame('13888888888', $result->getExternalUserMobile());
    }

    public function testCreatePickupOrderWithNoValidConfig(): void
    {
        // 设置模拟行为
        $this->cainiaoConfigRepository->expects($this->once())
            ->method('findValidConfig')
            ->willReturn(null)
        ;

        // 断言异常
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No valid Cainiao config found');

        // 执行测试
        $orderData = [
            'senderName' => '发件人',
            'senderPhone' => '13800138000',
            'senderFullAddress' => '北京市朝阳区三里屯街道10号',
            'receiverName' => '收件人',
            'receiverPhone' => '13900139000',
            'receiverFullAddress' => '上海市浦东新区张江高科园区88号',
            'itemType' => 'document',
            'weight' => 1.5,
        ];

        $this->pickupService->createPickupOrder($orderData);
    }

    public function testUpdateOrderStatusUpdatesStatusCorrectly(): void
    {
        // 注意：由于使用了真实的EntityManager，我们不验证flush调用

        // 执行测试
        $result = $this->pickupService->updateOrderStatus($this->order, OrderStatusEnum::WAREHOUSE_ACCEPT);

        // 断言
        $this->assertSame(OrderStatusEnum::WAREHOUSE_ACCEPT, $result->getStatus());
        $this->assertSame($this->order, $result);
    }

    public function testGetOrderDetailReturnsCorrectOrder(): void
    {
        // 设置模拟行为
        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with('TEST123456')
            ->willReturn($this->order)
        ;

        // 执行测试
        $result = $this->pickupService->getOrderDetail('TEST123456');

        // 断言
        $this->assertSame($this->order, $result);
    }

    public function testGetOrderDetailWithNonExistentOrder(): void
    {
        // 设置模拟行为
        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with('NON_EXISTENT')
            ->willReturn(null)
        ;

        // 执行测试
        $result = $this->pickupService->getOrderDetail('NON_EXISTENT');

        // 断言
        $this->assertNull($result);
    }

    public function testGetOrdersByStatusReturnsCorrectOrders(): void
    {
        // 设置模拟行为
        $this->pickupOrderRepository->expects($this->once())
            ->method('findByStatus')
            ->with(OrderStatusEnum::CREATE)
            ->willReturn([$this->order])
        ;

        // 执行测试
        $result = $this->pickupService->getOrdersByStatus(OrderStatusEnum::CREATE);

        // 断言
        $this->assertCount(1, $result);
        $this->assertSame($this->order, $result[0]);
    }

    public function testCancelPickupOrderWithCancellableStatus(): void
    {
        // 准备测试数据
        $this->order->setStatus(OrderStatusEnum::CREATE);

        // 设置模拟行为
        $this->cainiaoHttpClient->expects($this->once())
            ->method('cancelPickupOrder')
            ->with(
                self::identicalTo($this->order),
                self::identicalTo('用户取消')
            )
        ;

        // 注意：由于使用了真实的EntityManager，我们不验证flush调用

        // 执行测试
        $result = $this->pickupService->cancelPickupOrder($this->order, '用户取消');

        // 断言
        $this->assertSame(OrderStatusEnum::CANCELLED, $result->getStatus());
        $this->assertSame('用户取消', $result->getCancelReason());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getCancelTime());
    }

    public function testCancelPickupOrderWithNonCancellableStatus(): void
    {
        // 准备测试数据
        $this->order->setStatus(OrderStatusEnum::SIGN);

        // 断言异常
        $this->expectException(OrderCannotBeCancelledException::class);

        // 执行测试
        $this->pickupService->cancelPickupOrder($this->order, '用户取消');
    }

    public function testModifyPickupOrderWithModifiableStatus(): void
    {
        // 准备测试数据
        $this->order->setStatus(OrderStatusEnum::CREATE);
        $oldSenderName = $this->order->getSenderInfo()->getName();
        $oldReceiverName = $this->order->getReceiverInfo()->getName();

        // 设置模拟行为
        $this->cainiaoHttpClient->expects($this->once())
            ->method('modifyPickupOrder')
            ->with(self::identicalTo($this->order))
        ;

        // 注意：由于使用了真实的EntityManager，我们不验证flush调用

        // 执行测试
        $modifyData = [
            'senderName' => '新发件人',
            'senderPhone' => '13811111111',
            'senderAddress' => '新的发件地址',
            'receiverName' => '新收件人',
            'receiverPhone' => '13922222222',
            'receiverAddress' => '新的收件地址',
            'itemType' => 'electronics',
            'weight' => 2.0,
            'itemQuantity' => '2',
            'itemValue' => 200.00,
            'expectPickupTimeStart' => '2023-08-02 09:00:00',
            'expectPickupTimeEnd' => '2023-08-02 18:00:00',
            'remark' => '新的备注',
        ];

        $result = $this->pickupService->modifyPickupOrder($this->order, $modifyData);

        // 断言
        $this->assertNotSame($oldSenderName, $result->getSenderInfo()->getName());
        $this->assertNotSame($oldReceiverName, $result->getReceiverInfo()->getName());
        $this->assertSame('新发件人', $result->getSenderInfo()->getName());
        $this->assertSame('新收件人', $result->getReceiverInfo()->getName());
        $this->assertSame(ItemTypeEnum::ELECTRONICS, $result->getItemType());
        $this->assertSame(2.0, $result->getWeight());
        $this->assertSame('2', $result->getItemQuantity());
        $this->assertSame(200.00, $result->getItemValue());
        $this->assertSame('2023-08-02 09:00:00', $result->getExpectPickupTimeStart());
        $this->assertSame('2023-08-02 18:00:00', $result->getExpectPickupTimeEnd());
        $this->assertSame('新的备注', $result->getRemark());
    }

    public function testModifyPickupOrderWithNonModifiableStatus(): void
    {
        // 准备测试数据
        $this->order->setStatus(OrderStatusEnum::SIGN);

        // 断言异常
        $this->expectException(OrderModificationFailedException::class);

        // 执行测试
        $this->pickupService->modifyPickupOrder($this->order, [
            'senderName' => '新发件人',
            'senderPhone' => '13811111111',
            'senderAddress' => '新的发件地址',
        ]);
    }
}
