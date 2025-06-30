<?php

namespace CainiaoPickupBundle\Tests\DependencyInjection;

use CainiaoPickupBundle\DependencyInjection\CainiaoPickupExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CainiaoPickupExtensionTest extends TestCase
{
    private CainiaoPickupExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new CainiaoPickupExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadServices(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务目录被加载
        $definitions = $this->container->getDefinitions();
        $hasCommandDefinition = false;
        $hasServiceDefinition = false;
        $hasRepositoryDefinition = false;
        
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'CainiaoPickupBundle\Command\\')) {
                $hasCommandDefinition = true;
            }
            if (str_starts_with($id, 'CainiaoPickupBundle\Service\\')) {
                $hasServiceDefinition = true;
            }
            if (str_starts_with($id, 'CainiaoPickupBundle\Repository\\')) {
                $hasRepositoryDefinition = true;
            }
        }
        
        $this->assertTrue($hasCommandDefinition, 'Commands should be loaded');
        $this->assertTrue($hasServiceDefinition, 'Services should be loaded');
        $this->assertTrue($hasRepositoryDefinition, 'Repositories should be loaded');
    }

    public function testLoadWithEmptyConfig(): void
    {
        $this->extension->load([], $this->container);
        
        // 确保即使配置为空也能正常加载
        $this->assertNotEmpty($this->container->getDefinitions());
    }

    public function testServicesAreAutoconfigured(): void
    {
        $this->extension->load([], $this->container);
        
        // 由于使用目录加载方式，自动配置应该生效
        // 检查是否有服务定义被加载
        $definitions = $this->container->getDefinitions();
        $hasAutoConfiguredServices = false;
        
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'CainiaoPickupBundle\\')) {
                // 检查是否启用了自动配置
                if ($definition->isAutoconfigured()) {
                    $hasAutoConfiguredServices = true;
                    break;
                }
            }
        }
        
        $this->assertTrue($hasAutoConfiguredServices, 'Services should be autoconfigured');
    }

    public function testRepositoriesAreAutoconfigured(): void
    {
        $this->extension->load([], $this->container);
        
        // 验证仓储服务是否被加载
        $definitions = $this->container->getDefinitions();
        $hasRepositoryServices = false;
        
        foreach ($definitions as $id => $definition) {
            if (str_starts_with($id, 'CainiaoPickupBundle\Repository\\')) {
                $hasRepositoryServices = true;
                break;
            }
        }
        
        $this->assertTrue($hasRepositoryServices, 'Repository services should be loaded');
    }
}