<?php

namespace CainiaoPickupBundle\Tests\Unit;

use CainiaoPickupBundle\CainiaoPickupBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CainiaoPickupBundleTest extends TestCase
{
    private CainiaoPickupBundle $bundle;
    
    protected function setUp(): void
    {
        $this->bundle = new CainiaoPickupBundle();
    }
    
    public function testBundleIsInstanceOfBundle(): void
    {
        $this->assertInstanceOf(Bundle::class, $this->bundle);
    }
    
    public function testGetPath(): void
    {
        // Bundle 类默认返回类文件所在目录，即 src 目录
        $expectedPath = dirname(__DIR__, 2) . '/src';
        $this->assertEquals($expectedPath, $this->bundle->getPath());
    }
    
    public function testBuild(): void
    {
        $container = new ContainerBuilder();
        
        // 确保 build 方法可以正常执行
        $this->bundle->build($container);
        
        // 验证容器仍然是有效的
        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
}