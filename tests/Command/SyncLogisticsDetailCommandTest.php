<?php

namespace CainiaoPickupBundle\Tests\Command;

use CainiaoPickupBundle\Command\SyncLogisticsDetailCommand;
use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\LogisticsDetailRepository;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SyncLogisticsDetailCommandTest extends TestCase
{
    private SyncLogisticsDetailCommand $command;
    private EntityManagerInterface&MockObject $entityManager;
    private PickupOrderRepository&MockObject $pickupOrderRepository;
    private LogisticsDetailRepository&MockObject $logisticsDetailRepository;
    private CainiaoHttpClient&MockObject $cainiaoHttpClient;
    private LoggerInterface&MockObject $logger;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->pickupOrderRepository = $this->createMock(PickupOrderRepository::class);
        $this->logisticsDetailRepository = $this->createMock(LogisticsDetailRepository::class);
        $this->cainiaoHttpClient = $this->createMock(CainiaoHttpClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->command = new SyncLogisticsDetailCommand(
            $this->entityManager,
            $this->pickupOrderRepository,
            $this->logisticsDetailRepository,
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
        $mailNo = 'MAIL123';
        
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getMailNo')->willReturn($mailNo);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order);

        $logisticsData = [
            'logisticsDetails' => [
                [
                    'status' => '已揽收',
                    'desc' => '快递员已取件',
                    'time' => '2023-10-01 10:00:00',
                    'city' => '北京市',
                    'area' => '朝阳区',
                    'address' => '某某街道',
                    'courierInfo' => [
                        'name' => '张三',
                        'mobile' => '13800138000',
                    ],
                ],
            ],
        ];

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryLogisticsDetail')
            ->with($order)
            ->willReturn($logisticsData);

        $this->logisticsDetailRepository->expects($this->once())
            ->method('deleteByOrder')
            ->with($order);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(LogisticsDetail::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Synced logistics detail', [
                'orderCode' => $orderCode,
                'mailNo' => $mailNo,
            ]);

        $exitCode = $this->commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Successfully synced logistics for order: TEST123', $this->commandTester->getDisplay());
    }

    public function testExecuteWithoutOrderCodeSyncsAllOrders(): void
    {
        $order1 = $this->createMock(PickupOrder::class);
        $order1->method('getOrderCode')->willReturn('ORDER1');
        $order1->method('getMailNo')->willReturn('MAIL1');

        $order2 = $this->createMock(PickupOrder::class);
        $order2->method('getOrderCode')->willReturn('ORDER2');
        $order2->method('getMailNo')->willReturn('MAIL2');

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByStatus')
            ->with(OrderStatusEnum::WAREHOUSE_CONFIRMED)
            ->willReturn([$order1, $order2]);

        $logisticsData = [
            'logisticsDetails' => [
                [
                    'status' => '已签收',
                    'desc' => '已签收',
                    'time' => '2023-10-02 14:00:00',
                ],
            ],
        ];

        $this->cainiaoHttpClient->expects($this->exactly(2))
            ->method('queryLogisticsDetail')
            ->willReturn($logisticsData);

        $this->logisticsDetailRepository->expects($this->exactly(2))
            ->method('deleteByOrder');

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(LogisticsDetail::class));

        $this->entityManager->expects($this->exactly(2))
            ->method('flush');

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Successfully synced logistics for order: ORDER1', $output);
        $this->assertStringContainsString('Successfully synced logistics for order: ORDER2', $output);
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
            ->with('Failed to sync logistics', $this->anything());

        $exitCode = $this->commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Order not found: NOTFOUND', $this->commandTester->getDisplay());
    }

    public function testExecuteWithOrderWithoutMailNo(): void
    {
        $orderCode = 'TEST123';
        
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getMailNo')->willReturn(null);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to sync logistics', $this->anything());

        $exitCode = $this->commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Order TEST123 has no mail number', $this->commandTester->getDisplay());
    }

    public function testExecuteHandlesApiError(): void
    {
        $orderCode = 'TEST123';
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getMailNo')->willReturn('MAIL123');

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order);

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryLogisticsDetail')
            ->willThrowException(new \RuntimeException('API Error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to sync logistics', $this->anything());

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
        $order1->method('getMailNo')->willReturn('MAIL1');

        $order2 = $this->createMock(PickupOrder::class);
        $order2->method('getOrderCode')->willReturn('ORDER2');
        $order2->method('getMailNo')->willReturn('MAIL2');

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByStatus')
            ->with(OrderStatusEnum::WAREHOUSE_CONFIRMED)
            ->willReturn([$order1, $order2]);

        $this->cainiaoHttpClient->expects($this->exactly(2))
            ->method('queryLogisticsDetail')
            ->willReturnCallback(function ($order) {
                if ($order->getOrderCode() === 'ORDER1') {
                    return ['logisticsDetails' => [['status' => '已签收', 'desc' => '已签收', 'time' => '2023-10-02 14:00:00']]];
                }
                throw new \CainiaoPickupBundle\Exception\CainiaoApiException('API Error for ORDER2');
            });

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to sync logistics', $this->callback(function ($context) {
                return $context['orderCode'] === 'ORDER2' && $context['error'] === 'API Error for ORDER2';
            }));

        $exitCode = $this->commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Successfully synced logistics for order: ORDER1', $output);
        $this->assertStringContainsString('Failed to sync logistics for order ORDER2: API Error for ORDER2', $output);
    }
}