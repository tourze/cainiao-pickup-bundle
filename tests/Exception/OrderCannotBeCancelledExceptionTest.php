<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Exception;

use CainiaoPickupBundle\Exception\OrderCannotBeCancelledException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(OrderCannotBeCancelledException::class)]
final class OrderCannotBeCancelledExceptionTest extends AbstractExceptionTestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new OrderCannotBeCancelledException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = '订单不能被取消：已经完成';
        $exception = new OrderCannotBeCancelledException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = '订单状态不允许取消';
        $code = 403;
        $exception = new OrderCannotBeCancelledException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithPreviousException(): void
    {
        $previous = new \Exception('业务规则错误');
        $exception = new OrderCannotBeCancelledException('不能取消订单', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
