<?php

namespace CainiaoPickupBundle\Tests\Unit\Exception;

use CainiaoPickupBundle\Exception\InvalidResponseException;
use PHPUnit\Framework\TestCase;

class InvalidResponseExceptionTest extends TestCase
{
    public function testExceptionWithMessage(): void
    {
        $message = 'Invalid response format';
        $exception = new InvalidResponseException($message);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
    
    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Response missing required fields';
        $code = 422;
        $exception = new InvalidResponseException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }
    
    public function testExceptionWithPreviousException(): void
    {
        $previousException = new \JsonException('Invalid JSON');
        $exception = new InvalidResponseException('Parse error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }
}