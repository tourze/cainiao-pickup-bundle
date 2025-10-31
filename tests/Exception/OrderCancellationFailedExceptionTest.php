<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Exception;

use CainiaoPickupBundle\Exception\OrderCancellationFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(OrderCancellationFailedException::class)]
final class OrderCancellationFailedExceptionTest extends AbstractExceptionTestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new OrderCancellationFailedException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = '订单取消失败：订单已发货';
        $exception = new OrderCancellationFailedException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = '订单取消失败';
        $code = 400;
        $exception = new OrderCancellationFailedException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithPreviousException(): void
    {
        $previous = new \Exception('原始错误');
        $exception = new OrderCancellationFailedException('订单取消失败', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
