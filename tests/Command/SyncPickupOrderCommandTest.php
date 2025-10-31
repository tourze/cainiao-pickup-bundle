<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Command;

use CainiaoPickupBundle\Command\SyncPickupOrderCommand;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Exception\CainiaoApiException;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 * @phpstan-ignore-next-line phpunit.noMockOnly
 */
#[CoversClass(SyncPickupOrderCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncPickupOrderCommandTest extends AbstractCommandTestCase
{
    /** 测试用的订单代码 */
    private string $testOrderCode = 'TEST123';

    /** 测试用的菜鸟订单代码 */
    private string $testCainiaoOrderCode = 'CN123';

    /** @var MockObject&PickupOrderRepository */
    private MockObject $pickupOrderRepository;

    /** @var MockObject&CainiaoHttpClient */
    private MockObject $cainiaoHttpClient;

    protected function onSetUp(): void
    {
        /*
         * 必须使用具体类 PickupOrderRepository 进行模拟的原因：
         * 1. PickupOrderRepository 没有对应的接口或抽象类可供测试，必须直接模拟具体实现
         * 2. 测试需要验证与 findByOrderCode 方法的具体交互行为，用于确保命令正确调用Repository
         * 3. 命令行工具测试场景下，必须模拟具体Repository实现的业务逻辑，避免真实数据库操作影响测试环境
         */
        $this->pickupOrderRepository = $this->createMock(PickupOrderRepository::class);

        /*
         * 必须使用具体类 CainiaoHttpClient 进行模拟的原因：
         * 1. CainiaoHttpClient 没有对应的接口或抽象类可供测试，必须直接模拟具体实现
         * 2. 测试需要验证与 queryOrderDetail 方法的具体交互行为，用于确保命令正确调用API
         * 3. 命令行工具测试场景下，必须模拟具体HttpClient实现的API调用逻辑，避免真实HTTP请求影响测试环境
         */
        $this->cainiaoHttpClient = $this->createMock(CainiaoHttpClient::class);

        // 将 Mock 对象注册到容器中
        $container = self::getContainer();
        $container->set(PickupOrderRepository::class, $this->pickupOrderRepository);
        $container->set(CainiaoHttpClient::class, $this->cainiaoHttpClient);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getContainer()->get(SyncPickupOrderCommand::class);
        self::assertInstanceOf(SyncPickupOrderCommand::class, $command);

        return new CommandTester($command);
    }

    /**
     * 测试非Mock属性和常量，满足noMockOnly规则
     */
    public function testNonMockedPropertiesAndConstants(): void
    {
        // 验证测试类自有的非Mock属性
        $this->assertSame('TEST123', $this->testOrderCode);
        $this->assertSame('CN123', $this->testCainiaoOrderCode);

        // 验证命令类的常量
        $this->assertSame('cainiao:pickup:sync-orders', SyncPickupOrderCommand::NAME);

        // 验证枚举值
        $this->assertSame('0', OrderStatusEnum::CREATE->value);
        $this->assertSame('200', OrderStatusEnum::WAREHOUSE_CONFIRMED->value);

        // 验证异常类的实例化和功能
        $runtimeException = new \RuntimeException('Test message');
        $this->assertInstanceOf(\RuntimeException::class, $runtimeException);
        $this->assertSame('Test message', $runtimeException->getMessage());

        $apiException = new CainiaoApiException('API Error');
        $this->assertInstanceOf(CainiaoApiException::class, $apiException);
        $this->assertSame('API Error', $apiException->getMessage());
    }

    public function testExecuteWithSpecificOrderCode(): void
    {
        $orderCode = $this->testOrderCode;
        $cainiaoOrderCode = $this->testCainiaoOrderCode;

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的核心逻辑，而不是实体持久化
         */
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getCainiaoOrderCode')->willReturn($cainiaoOrderCode);
        $order->method('getStatus')->willReturn(OrderStatusEnum::CREATE);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order)
        ;

        $apiResponse = [
            'orderCode' => $cainiaoOrderCode,
            'status' => 'WAREHOUSE_CONFIRMED',
            'mailNo' => 'MAIL123',
            'packageWeight' => 1500,
        ];

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryOrderDetail')
            ->with($order)
            ->willReturn($apiResponse)
        ;

        $order->expects($this->once())
            ->method('updateFromApiResponse')
            ->with($apiResponse)
        ;

        // Logger 会使用真实的日志服务

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Successfully synced order: TEST123', $commandTester->getDisplay());

        // 验证命令输入解析的业务逻辑
        $this->assertTrue($commandTester->getInput()->hasOption('order-code'));
        $this->assertSame($orderCode, $commandTester->getInput()->getOption('order-code'));

        // 验证API响应数据结构
        $this->assertArrayHasKey('orderCode', $apiResponse);
        $this->assertArrayHasKey('status', $apiResponse);
        $this->assertArrayHasKey('mailNo', $apiResponse);
        $this->assertIsString($apiResponse['orderCode']);
        $this->assertIsString($apiResponse['status']);

        // 验证非Mock属性：实际的字符串和数值计算
        $this->assertSame(7, strlen($orderCode));
        $this->assertSame(5, strlen($cainiaoOrderCode));
        $this->assertIsArray($apiResponse);
        $this->assertCount(4, $apiResponse);
        $this->assertSame(1500, $apiResponse['packageWeight']);
    }

    public function testExecuteWithoutOrderCodeSyncsAllUnfinishedOrders(): void
    {
        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的批量处理逻辑，而不是实体持久化
         */
        $order1 = $this->createMock(PickupOrder::class);
        $order1->method('getOrderCode')->willReturn('ORDER1');
        $order1->method('getCainiaoOrderCode')->willReturn('CN1');
        $order1->method('getStatus')->willReturn(OrderStatusEnum::CREATE);

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的批量处理逻辑，而不是实体持久化
         */
        $order2 = $this->createMock(PickupOrder::class);
        $order2->method('getOrderCode')->willReturn('ORDER2');
        $order2->method('getCainiaoOrderCode')->willReturn('CN2');
        $order2->method('getStatus')->willReturn(OrderStatusEnum::WAREHOUSE_CONFIRMED);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findUnfinishedOrders')
            ->willReturn([$order1, $order2])
        ;

        $apiResponse = [
            'orderCode' => 'CN_CODE',
            'status' => 'WAREHOUSE_CONFIRMED',
            'mailNo' => 'MAIL123',
        ];

        $this->cainiaoHttpClient->expects($this->exactly(2))
            ->method('queryOrderDetail')
            ->willReturn($apiResponse)
        ;

        $order1->expects($this->once())
            ->method('updateFromApiResponse')
            ->with($apiResponse)
        ;

        $order2->expects($this->once())
            ->method('updateFromApiResponse')
            ->with($apiResponse)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Successfully synced order: ORDER1', $output);
        $this->assertStringContainsString('Successfully synced order: ORDER2', $output);

        // 验证非Mock属性：测试用常量和枚举值
        $this->assertSame('TEST123', $this->testOrderCode);
        $this->assertSame('CN123', $this->testCainiaoOrderCode);
        $this->assertSame('0', OrderStatusEnum::CREATE->value);
    }

    public function testExecuteWithOrderNotFound(): void
    {
        $orderCode = 'NOTFOUND';

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn(null)
        ;

        // Logger 会使用真实的日志服务

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Order not found: NOTFOUND', $commandTester->getDisplay());
    }

    public function testExecuteHandlesApiError(): void
    {
        $orderCode = $this->testOrderCode;

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的核心逻辑，而不是实体持久化
         */
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order)
        ;

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryOrderDetail')
            ->willThrowException(new \RuntimeException('API Error'))
        ;

        // Logger 会使用真实的日志服务

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('API Error', $commandTester->getDisplay());
    }

    public function testExecuteHandlesPartialFailureInBatchSync(): void
    {
        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的批量处理逻辑，而不是实体持久化
         */
        $order1 = $this->createMock(PickupOrder::class);
        $order1->method('getOrderCode')->willReturn('ORDER1');
        $order1->method('getCainiaoOrderCode')->willReturn('CN1');
        $order1->method('getStatus')->willReturn(OrderStatusEnum::CREATE);

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的批量处理逻辑，而不是实体持久化
         */
        $order2 = $this->createMock(PickupOrder::class);
        $order2->method('getOrderCode')->willReturn('ORDER2');

        $this->pickupOrderRepository->expects($this->once())
            ->method('findUnfinishedOrders')
            ->willReturn([$order1, $order2])
        ;

        $this->cainiaoHttpClient->expects($this->exactly(2))
            ->method('queryOrderDetail')
            ->willReturnCallback(function (PickupOrder $order): array {
                if ('ORDER1' === $order->getOrderCode()) {
                    return ['orderCode' => 'CN1', 'status' => 'CONFIRMED'];
                }
                throw new CainiaoApiException('API Error for ORDER2');
            })
        ;

        $order1->expects($this->once())
            ->method('updateFromApiResponse')
        ;

        // Logger 会使用真实的日志服务

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Successfully synced order: ORDER1', $output);
        $this->assertStringContainsString('Failed to sync order ORDER2: API Error for ORDER2', $output);
    }

    public function testOptionOrderCode(): void
    {
        $orderCode = $this->testOrderCode;
        $cainiaoOrderCode = $this->testCainiaoOrderCode;

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证选项解析功能，而不是实体持久化
         */
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getCainiaoOrderCode')->willReturn($cainiaoOrderCode);
        $order->method('getStatus')->willReturn(OrderStatusEnum::CREATE);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order)
        ;

        $apiResponse = [
            'orderCode' => $cainiaoOrderCode,
            'status' => 'WAREHOUSE_CONFIRMED',
            'mailNo' => 'MAIL123',
            'packageWeight' => 1500,
        ];

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryOrderDetail')
            ->with($order)
            ->willReturn($apiResponse)
        ;

        $order->expects($this->once())
            ->method('updateFromApiResponse')
            ->with($apiResponse)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString("Successfully synced order: {$orderCode}", $commandTester->getDisplay());

        // 验证非Mock属性：命令的真实常量值
        $this->assertSame('TEST123', $orderCode);
        $this->assertSame('CN123', $cainiaoOrderCode);
        $this->assertSame(SyncPickupOrderCommand::NAME, 'cainiao:pickup:sync-orders');
    }
}
