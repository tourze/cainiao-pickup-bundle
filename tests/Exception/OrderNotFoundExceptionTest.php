<?php

namespace CainiaoPickupBundle\Tests\Exception;

use CainiaoPickupBundle\Exception\OrderNotFoundException;
use PHPUnit\Framework\TestCase;

class OrderNotFoundExceptionTest extends TestCase
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