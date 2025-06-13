<?php

namespace CainiaoPickupBundle\Tests\Integration;

use CainiaoPickupBundle\CainiaoPickupBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;

class TestKernel extends IntegrationTestKernel
{
    public function __construct()
    {
        parent::__construct(
            'test',
            false,
            [
                DoctrineBundle::class => ['all' => true],
                MonologBundle::class => ['all' => true],
                CainiaoPickupBundle::class => ['all' => true],
            ],
            [
                'CainiaoPickupBundle\Entity' => dirname(__DIR__, 2) . '/src/Entity',
            ]
        );
    }
    
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);
        
        $loader->load(function (ContainerBuilder $container) {
            // 配置 HTTP 客户端
            if ($container->hasExtension('framework')) {
                $container->prependExtensionConfig('framework', [
                    'http_client' => [
                        'default_options' => [
                            'timeout' => 30,
                        ],
                    ],
                ]);
            }
            
            // 加载测试配置文件
            $yamlLoader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader(
                $container,
                new \Symfony\Component\Config\FileLocator(__DIR__ . '/../config')
            );
            
            if (file_exists(__DIR__ . '/../config/services_test.yaml')) {
                $yamlLoader->load('services_test.yaml');
            }
        });
    }
    
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        
        // 编译后设置服务为公共
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $services = [
                    'CainiaoPickupBundle\Service\PickupService',
                    'CainiaoPickupBundle\Service\CainiaoHttpClient',
                    'CainiaoPickupBundle\Repository\PickupOrderRepository',
                    'CainiaoPickupBundle\Repository\CainiaoConfigRepository',
                    'CainiaoPickupBundle\Repository\LogisticsDetailRepository',
                ];
                
                foreach ($services as $service) {
                    if ($container->hasDefinition($service)) {
                        $definition = $container->getDefinition($service);
                        $definition->setPublic(true);
                        // 确保保留自动装配
                        $definition->setAutowired(true);
                    }
                }
            }
        }, \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
    }
}
