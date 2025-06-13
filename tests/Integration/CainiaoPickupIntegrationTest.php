<?php

namespace CainiaoPickupBundle\Tests\Integration;

use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use CainiaoPickupBundle\Service\CainiaoHttpClient;
use CainiaoPickupBundle\Service\PickupService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @group integration
 */
class CainiaoPickupIntegrationTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel([
            'environment' => 'test',
            'debug' => false,
        ]);
    }

    /**
     * 返回测试所需的Kernel类
     */
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testServiceWiring_servicesAreRegistered(): void
    {
        $container = self::getContainer();
        
        $this->assertTrue($container->has(PickupService::class));
        $this->assertTrue($container->has(CainiaoHttpClient::class));
        $this->assertTrue($container->has(PickupOrderRepository::class));
        $this->assertTrue($container->has(CainiaoConfigRepository::class));
    }

    public function testPickupService_isAvailableFromContainer(): void
    {
        $container = self::getContainer();
        
        $pickupService = $container->get(PickupService::class);
        
        $this->assertInstanceOf(PickupService::class, $pickupService);
    }

    public function testCainiaoHttpClient_isAvailableFromContainer(): void
    {
        $container = self::getContainer();
        
        $cainiaoHttpClient = $container->get(CainiaoHttpClient::class);
        
        $this->assertInstanceOf(CainiaoHttpClient::class, $cainiaoHttpClient);
    }

    public function testRepositories_areRegisteredCorrectly(): void
    {
        $container = self::getContainer();
        
        $pickupOrderRepository = $container->get(PickupOrderRepository::class);
        $cainiaoConfigRepository = $container->get(CainiaoConfigRepository::class);
        
        $this->assertInstanceOf(PickupOrderRepository::class, $pickupOrderRepository);
        $this->assertInstanceOf(CainiaoConfigRepository::class, $cainiaoConfigRepository);
    }

    /**
     * 测试服务依赖注入是否正确
     */
    public function testPickupService_hasDependenciesInjected(): void
    {
        $container = self::getContainer();
        
        $pickupService = $container->get(PickupService::class);
        $this->assertInstanceOf(PickupService::class, $pickupService);
        
        // 使用反射验证依赖注入
        $reflection = new \ReflectionClass($pickupService);
        
        // 检查entityManager注入
        $entityManagerProp = $reflection->getProperty('entityManager');
        $entityManagerProp->setAccessible(true);
        $this->assertNotNull($entityManagerProp->getValue($pickupService));
        
        // 检查pickupOrderRepository注入
        $pickupOrderRepositoryProp = $reflection->getProperty('pickupOrderRepository');
        $pickupOrderRepositoryProp->setAccessible(true);
        $this->assertInstanceOf(PickupOrderRepository::class, $pickupOrderRepositoryProp->getValue($pickupService));
        
        // 检查cainiaoHttpClient注入
        $cainiaoHttpClientProp = $reflection->getProperty('cainiaoHttpClient');
        $cainiaoHttpClientProp->setAccessible(true);
        $this->assertInstanceOf(CainiaoHttpClient::class, $cainiaoHttpClientProp->getValue($pickupService));
    }
} 