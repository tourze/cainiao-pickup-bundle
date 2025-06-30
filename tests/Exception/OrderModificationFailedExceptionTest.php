<?php

namespace CainiaoPickupBundle\Tests\Exception;

use CainiaoPickupBundle\Exception\OrderModificationFailedException;
use PHPUnit\Framework\TestCase;

class OrderModificationFailedExceptionTest extends TestCase
{
    public function testIsRuntimeException(): void
    {
        $exception = new OrderModificationFailedException();
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = '订单修改失败：地址无效';
        $exception = new OrderModificationFailedException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = '修改订单失败';
        $code = 400;
        $exception = new OrderModificationFailedException($message, $code);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithPreviousException(): void
    {
        $previous = new \Exception('API调用失败');
        $exception = new OrderModificationFailedException('订单修改失败', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
}