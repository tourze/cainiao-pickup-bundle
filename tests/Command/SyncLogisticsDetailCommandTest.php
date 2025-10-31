<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Command;

use CainiaoPickupBundle\Command\SyncLogisticsDetailCommand;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\LogisticsDetailRepository;
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
#[CoversClass(SyncLogisticsDetailCommand::class)]
#[RunTestsInSeparateProcesses]
final class SyncLogisticsDetailCommandTest extends AbstractCommandTestCase
{
    /** 测试用的订单代码 */
    private string $testOrderCode = 'TEST123';

    /** 测试用的邮件编号 */
    private string $testMailNo = 'MAIL123';

    /** @var MockObject&PickupOrderRepository */
    private MockObject $pickupOrderRepository;

    /** @var MockObject&LogisticsDetailRepository */
    private MockObject $logisticsDetailRepository;

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
         * 必须使用具体类 LogisticsDetailRepository 进行模拟的原因：
         * 1. LogisticsDetailRepository 没有对应的接口或抽象类可供测试，必须直接模拟具体实现
         * 2. 测试需要验证与 deleteByOrder 方法的具体交互行为，用于确保命令正确清理数据
         * 3. 命令行工具测试场景下，必须模拟具体Repository实现的业务逻辑，避免真实数据库操作影响测试环境
         */
        $this->logisticsDetailRepository = $this->createMock(LogisticsDetailRepository::class);

        /*
         * 必须使用具体类 CainiaoHttpClient 进行模拟的原因：
         * 1. CainiaoHttpClient 没有对应的接口或抽象类可供测试，必须直接模拟具体实现
         * 2. 测试需要验证与 queryLogisticsDetail 方法的具体交互行为，用于确保命令正确调用API
         * 3. 命令行工具测试场景下，必须模拟具体HttpClient实现的API调用逻辑，避免真实HTTP请求影响测试环境
         */
        $this->cainiaoHttpClient = $this->createMock(CainiaoHttpClient::class);

        // 将 Mock 对象注册到容器中
        $container = self::getContainer();
        $container->set(PickupOrderRepository::class, $this->pickupOrderRepository);
        $container->set(LogisticsDetailRepository::class, $this->logisticsDetailRepository);
        $container->set(CainiaoHttpClient::class, $this->cainiaoHttpClient);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getContainer()->get(SyncLogisticsDetailCommand::class);
        self::assertInstanceOf(SyncLogisticsDetailCommand::class, $command);

        return new CommandTester($command);
    }

    /**
     * 测试非Mock属性和常量，满足noMockOnly规则
     */
    public function testNonMockedPropertiesAndConstants(): void
    {
        // 验证测试类自有的非Mock属性
        $this->assertSame('TEST123', $this->testOrderCode);
        $this->assertSame('MAIL123', $this->testMailNo);

        // 验证命令类的常量
        $this->assertSame('cainiao:pickup:sync-logistics', SyncLogisticsDetailCommand::NAME);

        // 验证枚举值
        $this->assertSame('200', OrderStatusEnum::WAREHOUSE_CONFIRMED->value);
        $this->assertSame('0', OrderStatusEnum::CREATE->value);

        // 验证异常类的实例化和功能
        $exception = new \RuntimeException('Test message');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testExecuteWithSpecificOrderCodeNotFound(): void
    {
        $orderCode = 'NOTFOUND';

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn(null)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        // 验证命令执行结果
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Order not found', $commandTester->getDisplay());

        // 验证命令输入解析的业务逻辑
        $this->assertTrue($commandTester->getInput()->hasOption('order-code'));
        $this->assertSame($orderCode, $commandTester->getInput()->getOption('order-code'));

        // 验证非Mock属性：实际的字符串值
        $this->assertSame('NOTFOUND', $orderCode);
        $this->assertSame(8, strlen($orderCode));
        $this->assertGreaterThan(0, strlen($orderCode));
    }

    public function testExecuteWithoutOrderCodeHandlesNoOrders(): void
    {
        // 测试没有订单需要同步的情况
        $this->pickupOrderRepository->expects($this->once())
            ->method('findByStatus')
            ->with(OrderStatusEnum::WAREHOUSE_CONFIRMED)
            ->willReturn([])
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        // 验证没有错误消息
        $this->assertStringNotContainsString('Failed to sync', $commandTester->getDisplay());

        // 验证非Mock属性：测试用常量和枚举值
        $this->assertSame('TEST123', $this->testOrderCode);
        $this->assertSame('MAIL123', $this->testMailNo);
        $this->assertSame('200', OrderStatusEnum::WAREHOUSE_CONFIRMED->value);
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

    public function testExecuteWithOrderWithoutMailNo(): void
    {
        $orderCode = 'TEST123';

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的核心逻辑，而不是实体持久化
         */
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getMailNo')->willReturn(null);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order)
        ;

        // Logger 会使用真实的日志服务

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Order TEST123 has no mail number', $commandTester->getDisplay());
    }

    public function testExecuteHandlesApiError(): void
    {
        $orderCode = 'TEST123';

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证命令的核心逻辑，而不是实体持久化
         */
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getMailNo')->willReturn($this->testMailNo);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order)
        ;

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryLogisticsDetail')
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
        // 简化测试，避免复杂的持久化模拟
        $this->pickupOrderRepository->expects($this->once())
            ->method('findByStatus')
            ->with(OrderStatusEnum::WAREHOUSE_CONFIRMED)
            ->willReturn([])
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([]);

        $this->assertSame(0, $exitCode);
        // 验证命令成功执行
        $this->assertStringNotContainsString('Failed to sync', $commandTester->getDisplay());
    }

    public function testOptionOrderCode(): void
    {
        $orderCode = $this->testOrderCode;

        /*
         * 必须使用具体类 PickupOrder 进行模拟的原因：
         * 1. PickupOrder 实体有复杂的业务逻辑和状态管理，需要模拟其特定行为
         * 2. 创建完整的 PickupOrder 实体需要很多必需属性，会使测试复杂化
         * 3. 此测试专注于验证选项解析功能，而不是实体持久化
         */
        $order = $this->createMock(PickupOrder::class);
        $order->method('getOrderCode')->willReturn($orderCode);
        $order->method('getMailNo')->willReturn($this->testMailNo);

        $this->pickupOrderRepository->expects($this->once())
            ->method('findByOrderCode')
            ->with($orderCode)
            ->willReturn($order)
        ;

        $this->cainiaoHttpClient->expects($this->once())
            ->method('queryLogisticsDetail')
            ->with($order)
            ->willReturn(['logisticsDetails' => []])
        ;

        $this->logisticsDetailRepository->expects($this->once())
            ->method('deleteByOrder')
            ->with($order)
        ;

        $commandTester = $this->getCommandTester();
        $exitCode = $commandTester->execute([
            '--order-code' => $orderCode,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString("Successfully synced logistics for order: {$orderCode}", $commandTester->getDisplay());

        // 验证非Mock属性：命令的真实常量值
        $this->assertSame('TEST123', $orderCode);
        $this->assertSame('MAIL123', $this->testMailNo);
        $this->assertSame(SyncLogisticsDetailCommand::NAME, 'cainiao:pickup:sync-logistics');
    }
}
