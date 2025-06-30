<?php

namespace CainiaoPickupBundle\Tests\Unit\Exception;

use CainiaoPickupBundle\Exception\OrderException;
use PHPUnit\Framework\TestCase;

class OrderExceptionTest extends TestCase
{
    public function testExceptionWithMessage(): void
    {
        $message = 'Order processing failed';
        $exception = new OrderException($message);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
    
    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Order not found';
        $code = 404;
        $exception = new OrderException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }
    
    public function testExceptionWithPreviousException(): void
    {
        $previousException = new \DomainException('Invalid order status');
        $exception = new OrderException('Order error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }
}