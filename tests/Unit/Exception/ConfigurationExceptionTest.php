<?php

namespace CainiaoPickupBundle\Tests\Unit\Exception;

use CainiaoPickupBundle\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

class ConfigurationExceptionTest extends TestCase
{
    public function testExceptionWithMessage(): void
    {
        $message = 'Configuration is invalid';
        $exception = new ConfigurationException($message);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }
    
    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Missing required configuration';
        $code = 500;
        $exception = new ConfigurationException($message, $code);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }
    
    public function testExceptionWithPreviousException(): void
    {
        $previousException = new \InvalidArgumentException('Invalid config value');
        $exception = new ConfigurationException('Config error', 0, $previousException);
        
        $this->assertSame($previousException, $exception->getPrevious());
    }
}