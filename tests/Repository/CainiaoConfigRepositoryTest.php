<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Repository;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(CainiaoConfigRepository::class)]
#[RunTestsInSeparateProcesses]
final class CainiaoConfigRepositoryTest extends AbstractRepositoryTestCase
{
    private CainiaoConfigRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(CainiaoConfigRepository::class);

        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' !== $currentTest) {
            // 大多数原有测试假设数据库是空的
            $this->clearAllConfigs();
        }
    }

    private function clearAllConfigs(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM ' . CainiaoConfig::class)->execute();
    }

    public function testFindValidConfigReturnsValidConfig(): void
    {
        // 创建有效配置
        $validConfig = new CainiaoConfig();
        $validConfig->setName('测试配置');
        $validConfig->setAppKey('test_key');
        $validConfig->setAppSecret('test_secret');
        $validConfig->setAccessCode('test_code');
        $validConfig->setProviderId('test_provider');
        $validConfig->setValid(true);
        self::getEntityManager()->persist($validConfig);
        self::getEntityManager()->flush();

        // 测试查找有效配置
        $result = $this->repository->findValidConfig();
        $this->assertNotNull($result);
        $this->assertTrue($result->isValid());
        $this->assertEquals('测试配置', $result->getName());
    }

    public function testFindValidConfigReturnsNullWhenNoValidConfig(): void
    {
        // 创建无效配置
        $invalidConfig = new CainiaoConfig();
        $invalidConfig->setName('无效配置');
        $invalidConfig->setAppKey('invalid_key');
        $invalidConfig->setAppSecret('invalid_secret');
        $invalidConfig->setAccessCode('invalid_code');
        $invalidConfig->setProviderId('invalid_provider');
        $invalidConfig->setValid(false);
        self::getEntityManager()->persist($invalidConfig);
        self::getEntityManager()->flush();

        // 测试查找有效配置应返回null
        $result = $this->repository->findValidConfig();
        $this->assertNull($result);
    }

    public function testFindValidConfigReturnsNullWhenNoConfigs(): void
    {
        // 清空所有配置，测试在没有配置时返回null

        $result = $this->repository->findValidConfig();
        $this->assertNull($result);
    }

    public function testFindValidConfigReturnsFirstValidConfigWhenMultiple(): void
    {
        // 清空所有配置，确保只有测试创建的配置

        // 创建第一个有效配置
        $validConfig1 = new CainiaoConfig();
        $validConfig1->setName('有效配置1');
        $validConfig1->setAppKey('valid_key1');
        $validConfig1->setAppSecret('valid_secret1');
        $validConfig1->setAccessCode('valid_code1');
        $validConfig1->setProviderId('valid_provider1');
        $validConfig1->setValid(true);
        self::getEntityManager()->persist($validConfig1);

        // 创建第二个有效配置
        $validConfig2 = new CainiaoConfig();
        $validConfig2->setName('有效配置2');
        $validConfig2->setAppKey('valid_key2');
        $validConfig2->setAppSecret('valid_secret2');
        $validConfig2->setAccessCode('valid_code2');
        $validConfig2->setProviderId('valid_provider2');
        $validConfig2->setValid(true);
        self::getEntityManager()->persist($validConfig2);

        self::getEntityManager()->flush();

        // 测试返回其中一个有效配置
        $result = $this->repository->findValidConfig();
        $this->assertNotNull($result);
        $this->assertTrue($result->isValid());
        $this->assertContains($result->getName(), ['有效配置1', '有效配置2']);
    }

    public function testSave(): void
    {
        // 创建新实体
        $config = new CainiaoConfig();
        $config->setName('新配置');
        $config->setAppKey('new_key');
        $config->setAppSecret('new_secret');
        $config->setAccessCode('new_code');
        $config->setProviderId('new_provider');
        $config->setValid(true);

        // 测试save方法
        $this->repository->save($config);

        // 验证实体已被保存
        $this->assertNotNull($config->getId());
        $found = $this->repository->find($config->getId());
        $this->assertNotNull($found);
        self::assertInstanceOf(CainiaoConfig::class, $found);
        $this->assertEquals('新配置', $found->getName());
        $this->assertEquals('new_key', $found->getAppKey());

        // 测试save但不flush
        $config2 = new CainiaoConfig();
        $config2->setName('另一个配置');
        $config2->setAppKey('another_key');
        $config2->setAppSecret('another_secret');
        $config2->setAccessCode('another_code');
        $config2->setProviderId('another_provider');
        $config2->setValid(false);

        $this->repository->save($config2, false);
        self::getEntityManager()->flush();

        $this->assertNotNull($config2->getId());
        $found2 = $this->repository->find($config2->getId());
        $this->assertNotNull($found2);
        self::assertInstanceOf(CainiaoConfig::class, $found2);
        $this->assertEquals('另一个配置', $found2->getName());
    }

    public function testRemove(): void
    {
        // 创建测试数据
        $config = new CainiaoConfig();
        $config->setName('待删除配置');
        $config->setAppKey('delete_key');
        $config->setAppSecret('delete_secret');
        $config->setAccessCode('delete_code');
        $config->setProviderId('delete_provider');
        $config->setValid(true);
        self::getEntityManager()->persist($config);
        self::getEntityManager()->flush();

        $id = $config->getId();
        $this->assertNotNull($id);

        // 验证实体存在
        $found = $this->repository->find($id);
        $this->assertNotNull($found);

        // 测试remove方法
        $this->repository->remove($config);

        // 验证实体已被删除
        $found = $this->repository->find($id);
        $this->assertNull($found);

        // 测试remove但不flush
        $config2 = new CainiaoConfig();
        $config2->setName('另一个待删除配置');
        $config2->setAppKey('another_delete_key');
        $config2->setAppSecret('another_delete_secret');
        $config2->setAccessCode('another_delete_code');
        $config2->setProviderId('another_delete_provider');
        $config2->setValid(false);
        self::getEntityManager()->persist($config2);
        self::getEntityManager()->flush();

        $id2 = $config2->getId();
        $this->repository->remove($config2, false);
        self::getEntityManager()->flush();

        $found2 = $this->repository->find($id2);
        $this->assertNull($found2);
    }

    public function testFindByWithNullableFieldIsNullQuery(): void
    {
        // 创建有remark的配置
        $configWithRemark = new CainiaoConfig();
        $configWithRemark->setName('有备注配置');
        $configWithRemark->setAppKey('key1');
        $configWithRemark->setAppSecret('secret1');
        $configWithRemark->setAccessCode('code1');
        $configWithRemark->setProviderId('provider1');
        $configWithRemark->setRemark('这是备注');
        $configWithRemark->setValid(true);
        self::getEntityManager()->persist($configWithRemark);

        // 创建没有remark的配置
        $configWithoutRemark = new CainiaoConfig();
        $configWithoutRemark->setName('无备注配置');
        $configWithoutRemark->setAppKey('key2');
        $configWithoutRemark->setAppSecret('secret2');
        $configWithoutRemark->setAccessCode('code2');
        $configWithoutRemark->setProviderId('provider2');
        $configWithoutRemark->setRemark(null);
        $configWithoutRemark->setValid(true);
        self::getEntityManager()->persist($configWithoutRemark);

        self::getEntityManager()->flush();

        // 测试查询remark为null的记录
        $results = $this->repository->findBy(['remark' => null]);
        $this->assertCount(1, $results);
        $this->assertEquals('无备注配置', $results[0]->getName());
        $this->assertNull($results[0]->getRemark());

        // 测试查询有remark的记录
        $results = $this->repository->findBy(['remark' => '这是备注']);
        $this->assertCount(1, $results);
        $this->assertEquals('有备注配置', $results[0]->getName());
        $this->assertEquals('这是备注', $results[0]->getRemark());
    }

    public function testCountWithNullableFieldIsNullQuery(): void
    {
        // 创建有remark的配置
        $configWithRemark = new CainiaoConfig();
        $configWithRemark->setName('有备注配置');
        $configWithRemark->setAppKey('key1');
        $configWithRemark->setAppSecret('secret1');
        $configWithRemark->setAccessCode('code1');
        $configWithRemark->setProviderId('provider1');
        $configWithRemark->setRemark('这是备注');
        $configWithRemark->setValid(true);
        self::getEntityManager()->persist($configWithRemark);

        // 创建没有remark的配置
        $configWithoutRemark = new CainiaoConfig();
        $configWithoutRemark->setName('无备注配置');
        $configWithoutRemark->setAppKey('key2');
        $configWithoutRemark->setAppSecret('secret2');
        $configWithoutRemark->setAccessCode('code2');
        $configWithoutRemark->setProviderId('provider2');
        $configWithoutRemark->setRemark(null);
        $configWithoutRemark->setValid(true);
        self::getEntityManager()->persist($configWithoutRemark);

        self::getEntityManager()->flush();

        // 测试统计remark为null的记录数
        $count = $this->repository->count(['remark' => null]);
        $this->assertEquals(1, $count);

        // 测试统计有remark的记录数
        $count = $this->repository->count(['remark' => '这是备注']);
        $this->assertEquals(1, $count);
    }

    /**
     * @return CainiaoConfigRepository
     */
    protected function getRepository(): CainiaoConfigRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): object
    {
        $config = new CainiaoConfig();
        $config->setName('测试配置_' . uniqid());
        $config->setAppKey('test_app_key_' . uniqid());
        $config->setAppSecret('test_app_secret_' . uniqid());
        $config->setAccessCode('test_access_code_' . uniqid());
        $config->setProviderId('test_provider_' . uniqid());
        $config->setApiGateway('https://test.example.com');
        $config->setRemark('测试备注_' . uniqid());
        $config->setValid(true);

        return $config;
    }
}
