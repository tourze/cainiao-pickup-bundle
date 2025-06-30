<?php

namespace CainiaoPickupBundle\Tests\Unit\Exception;

use CainiaoPickupBundle\Exception\CainiaoApiException;
use PHPUnit\Framework\TestCase;

class CainiaoApiExceptionTest extends TestCase
{
    public function testExceptionWithMessage(): void
    {
        $message = 'API request failed';
        $exception = new CainiaoApiException($message);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
    
    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'API rate limit exceeded';
        $code = 429;
        $exception = new CainiaoApiException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }
    
    public function testExceptionWithPreviousException(): void
    {
        $previousException = new \RuntimeException('Network error');
        $exception = new CainiaoApiException('API failed', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }
}