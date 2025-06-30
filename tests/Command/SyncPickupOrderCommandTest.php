<?php

namespace CainiaoPickupBundle\Tests\Command;

use CainiaoPickupBundle\Command\SyncPickupOrderCommand;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncPickupOrderCommandTest extends TestCase
{
    private SyncPickupOrderCommand $command;
    private EntityManagerInterface&MockObject $entityManager;
    private PickupOrderRepository&MockObject $pickupOrderRepository;
    private CainiaoHttpClient&MockObject $cainiaoHttpClient;
    private LoggerInterface&MockObject $logger;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->pickupOrderRepository = $this->createMock(PickupOrderRepository::class);
        $this->cainiaoHttpClient = $this->createMock(CainiaoHttpClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->command = new SyncPickupOrderCommand(
            $this->entityManager,
            $this->pickupOrderRepository,
            $this->cainiaoHttpClient,
            $this->logger
        );

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteWithSpecificOrderCode(): void
    {
        $orderCode = 'TEST123';
        $cainiaoOrderCode = 'CN123';
        
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getCainiaoOrderCode')->willReturn($cainiaoOrderCode);
        $order->method('getStatus')->willReturn(OrderStatusEnum::CREATE);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order);

        $apiResponse = [
            'orderCode' => $cainiaoOrderCode,
            'status' => 'WAREHOUSE_CONFIRMED',
            'mailNo' => 'MAIL123',
            'packageWeight' => 1500,
        ];

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryOrderDetail')
            ->with($order)
            ->willReturn($apiResponse);

        $order->expects($this->once())
            ->method('updateFromApiResponse')
            ->with($apiResponse);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Synced pickup order', $this->callback(function ($context) use ($orderCode, $cainiaoOrderCode) {
                return $context['orderCode'] === $orderCode
                    && $context['cainiaoOrderCode'] === $cainiaoOrderCode
                    && isset($context['status']);
            }));

        $exitCode = $this->commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Successfully synced order: TEST123', $this->commandTester->getDisplay());
    }

    public function testExecuteWithoutOrderCodeSyncsAllUnfinishedOrders(): void
    {
        $order1 = $this->createMock(PickupOrder::class);
        $order1->method('getOrderCode')->willReturn('ORDER1');
        $order1->method('getCainiaoOrderCode')->willReturn('CN1');
        $order1->method('getStatus')->willReturn(OrderStatusEnum::CREATE);

        $order2 = $this->createMock(PickupOrder::class);
        $order2->method('getOrderCode')->willReturn('ORDER2');
        $order2->method('getCainiaoOrderCode')->willReturn('CN2');
        $order2->method('getStatus')->willReturn(OrderStatusEnum::WAREHOUSE_CONFIRMED);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findUnfinishedOrders')
            ->willReturn([$order1, $order2]);

        $apiResponse = [
            'orderCode' => 'CN_CODE',
            'status' => 'WAREHOUSE_CONFIRMED',
            'mailNo' => 'MAIL123',
        ];

        $this->cainiaoHttpClient->expects($this->exactly(2))
            ->method('queryOrderDetail')
            ->willReturn($apiResponse);

        $order1->expects($this->once())
            ->method('updateFromApiResponse')
            ->with($apiResponse);

        $order2->expects($this->once())
            ->method('updateFromApiResponse')
            ->with($apiResponse);

        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Successfully synced order: ORDER1', $output);
        $this->assertStringContainsString('Successfully synced order: ORDER2', $output);
    }

    public function testExecuteWithOrderNotFound(): void
    {
        $orderCode = 'NOTFOUND';

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to sync orders', $this->anything());

        $exitCode = $this->commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Order not found: NOTFOUND', $this->commandTester->getDisplay());
    }

    public function testExecuteHandlesApiError(): void
    {
        $orderCode = 'TEST123';
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order);

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryOrderDetail')
            ->willThrowException(new \RuntimeException('API Error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to sync orders', $this->anything());

        $exitCode = $this->commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('API Error', $this->commandTester->getDisplay());
    }

    public function testExecuteHandlesPartialFailureInBatchSync(): void
    {
        $order1 = $this->createMock(PickupOrder::class);
        $order1->method('getOrderCode')->willReturn('ORDER1');
        $order1->method('getCainiaoOrderCode')->willReturn('CN1');
        $order1->method('getStatus')->willReturn(OrderStatusEnum::CREATE);

        $order2 = $this->createMock(PickupOrder::class);
        $order2->method('getOrderCode')->willReturn('ORDER2');

        $this->pickupOrderRepository->expects($this->once())
            ->method('findUnfinishedOrders')
            ->willReturn([$order1, $order2]);

        $this->cainiaoHttpClient->expects($this->exactly(2))
            ->method('queryOrderDetail')
            ->willReturnCallback(function ($order) {
                if ($order->getOrderCode() === 'ORDER1') {
                    return ['orderCode' => 'CN1', 'status' => 'CONFIRMED'];
                }
                throw new \CainiaoPickupBundle\Exception\CainiaoApiException('API Error for ORDER2');
            });

        $order1->expects($this->once())
            ->method('updateFromApiResponse');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to sync order', $this->callback(function ($context) {
                return $context['orderCode'] === 'ORDER2' && $context['error'] === 'API Error for ORDER2';
            }));

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Successfully synced order: ORDER1', $output);
        $this->assertStringContainsString('Failed to sync order ORDER2: API Error for ORDER2', $output);
    }
}