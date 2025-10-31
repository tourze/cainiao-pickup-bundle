<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Exception;

use CainiaoPickupBundle\Exception\OrderNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(OrderNotFoundException::class)]
final class OrderNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new OrderNotFoundException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = '订单不存在：ORDER123';
        $exception = new OrderNotFoundException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = '找不到订单';
        $code = 404;
        $exception = new OrderNotFoundException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithPreviousException(): void
    {
        $previous = new \Exception('数据库查询错误');
        $exception = new OrderNotFoundException('订单未找到', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
