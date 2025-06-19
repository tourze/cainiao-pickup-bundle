<?php

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use PHPUnit\Framework\TestCase;

class CainiaoConfigTest extends TestCase
{
    private CainiaoConfig $config;

    protected function setUp(): void
    {
        $this->config = new CainiaoConfig();
    }

    public function testGetterSetter_basicProperties(): void
    {
        $this->config->setName('测试配置');
        $this->config->setAppKey('test_app_key');
        $this->config->setAppSecret('test_app_secret');
        $this->config->setAccessCode('test_access_code');
        $this->config->setProviderId('test_provider_id');
        $this->config->setApiGateway('https://api.test.com');
        
        $this->assertSame('测试配置', $this->config->getName());
        $this->assertSame('test_app_key', $this->config->getAppKey());
        $this->assertSame('test_app_secret', $this->config->getAppSecret());
        $this->assertSame('test_access_code', $this->config->getAccessCode());
        $this->assertSame('test_provider_id', $this->config->getProviderId());
        $this->assertSame('https://api.test.com', $this->config->getApiGateway());
    }

    public function testGetterSetter_optionalProperties(): void
    {
        $this->config->setRemark('测试备注');
        $this->config->setValid(true);
        $this->config->setCreatedBy('admin');
        $this->config->setUpdatedBy('manager');
        
        $this->assertSame('测试备注', $this->config->getRemark());
        $this->assertTrue($this->config->isValid());
        $this->assertSame('admin', $this->config->getCreatedBy());
        $this->assertSame('manager', $this->config->getUpdatedBy());
    }

    public function testFluentInterface_returnsObjectInstance(): void
    {
        $result = $this->config->setName('测试配置');
        $this->assertSame($this->config, $result);
        
        $result = $this->config->setAppKey('test_app_key');
        $this->assertSame($this->config, $result);
        
        $result = $this->config->setAppSecret('test_app_secret');
        $this->assertSame($this->config, $result);
    }

    public function testChainedCalls_setsAllProperties(): void
    {
        $this->config->setName('测试配置')
            ->setAppKey('test_app_key')
            ->setAppSecret('test_app_secret')
            ->setAccessCode('test_access_code')
            ->setProviderId('test_provider_id')
            ->setApiGateway('https://api.test.com')
            ->setRemark('测试备注')
            ->setValid(true);
        
        $this->assertSame('测试配置', $this->config->getName());
        $this->assertSame('test_app_key', $this->config->getAppKey());
        $this->assertSame('test_app_secret', $this->config->getAppSecret());
        $this->assertSame('test_access_code', $this->config->getAccessCode());
        $this->assertSame('test_provider_id', $this->config->getProviderId());
        $this->assertSame('https://api.test.com', $this->config->getApiGateway());
        $this->assertSame('测试备注', $this->config->getRemark());
        $this->assertTrue($this->config->isValid());
    }

    public function testTimestampProperties_canBeSetAndRetrieved(): void
    {
        $now = new \DateTimeImmutable();
        
        $this->config->setCreateTime($now);
        $this->config->setUpdateTime($now);
        
        $this->assertSame($now, $this->config->getCreateTime());
        $this->assertSame($now, $this->config->getUpdateTime());
    }
} 