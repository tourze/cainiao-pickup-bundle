<?php

namespace CainiaoPickupBundle\Tests\Integration\Repository;

use CainiaoPickupBundle\CainiaoPickupBundle;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;

class CainiaoConfigRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CainiaoConfigRepository $repository;

    protected static function createKernel(array $options = []): KernelInterface
    {
        $env = $options['environment'] ?? $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'test';
        $debug = $options['debug'] ?? $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? true;

        return new IntegrationTestKernel($env, $debug, [
            // Doctrine extensions
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            // Core bundles
            CainiaoPickupBundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->repository = $container->get(CainiaoConfigRepository::class);

        // 创建Schema
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testFindValidConfig_returnsValidConfig(): void
    {
        // 创建无效配置
        $invalidConfig = new CainiaoConfig();
        $invalidConfig->setName('无效配置');
        $invalidConfig->setAppKey('invalid_key');
        $invalidConfig->setAppSecret('invalid_secret');
        $invalidConfig->setAccessCode('invalid_code');
        $invalidConfig->setProviderId('invalid_provider');
        $invalidConfig->setValid(false);
        $this->entityManager->persist($invalidConfig);

        // 创建有效配置
        $validConfig = new CainiaoConfig();
        $validConfig->setName('有效配置');
        $validConfig->setAppKey('valid_key');
        $validConfig->setAppSecret('valid_secret');
        $validConfig->setAccessCode('valid_code');
        $validConfig->setProviderId('valid_provider');
        $validConfig->setValid(true);
        $this->entityManager->persist($validConfig);

        $this->entityManager->flush();

        // 测试查找有效配置
        $result = $this->repository->findValidConfig();
        $this->assertNotNull($result);
        $this->assertSame($validConfig, $result);
        $this->assertTrue($result->isValid());
        $this->assertEquals('有效配置', $result->getName());
    }

    public function testFindValidConfig_returnsNullWhenNoValidConfig(): void
    {
        // 创建无效配置
        $invalidConfig = new CainiaoConfig();
        $invalidConfig->setName('无效配置');
        $invalidConfig->setAppKey('invalid_key');
        $invalidConfig->setAppSecret('invalid_secret');
        $invalidConfig->setAccessCode('invalid_code');
        $invalidConfig->setProviderId('invalid_provider');
        $invalidConfig->setValid(false);
        $this->entityManager->persist($invalidConfig);
        $this->entityManager->flush();

        // 测试查找有效配置应返回null
        $result = $this->repository->findValidConfig();
        $this->assertNull($result);
    }

    public function testFindValidConfig_returnsNullWhenNoConfigs(): void
    {
        // 不创建任何配置，测试返回null
        $result = $this->repository->findValidConfig();
        $this->assertNull($result);
    }

    public function testFindValidConfig_returnsFirstValidConfigWhenMultiple(): void
    {
        // 创建第一个有效配置
        $validConfig1 = new CainiaoConfig();
        $validConfig1->setName('有效配置1');
        $validConfig1->setAppKey('valid_key1');
        $validConfig1->setAppSecret('valid_secret1');
        $validConfig1->setAccessCode('valid_code1');
        $validConfig1->setProviderId('valid_provider1');
        $validConfig1->setValid(true);
        $this->entityManager->persist($validConfig1);

        // 创建第二个有效配置
        $validConfig2 = new CainiaoConfig();
        $validConfig2->setName('有效配置2');
        $validConfig2->setAppKey('valid_key2');
        $validConfig2->setAppSecret('valid_secret2');
        $validConfig2->setAccessCode('valid_code2');
        $validConfig2->setProviderId('valid_provider2');
        $validConfig2->setValid(true);
        $this->entityManager->persist($validConfig2);

        $this->entityManager->flush();

        // 测试返回其中一个有效配置
        $result = $this->repository->findValidConfig();
        $this->assertNotNull($result);
        $this->assertTrue($result->isValid());
        $this->assertContains($result->getName(), ['有效配置1', '有效配置2']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // 清理以避免内存泄漏
        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }
    }
}