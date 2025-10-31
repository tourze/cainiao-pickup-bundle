<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\DataFixtures;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CainiaoConfigFixtures extends Fixture
{
    public const CAINIAO_CONFIG_REFERENCE = 'cainiao-config';

    public function load(ObjectManager $manager): void
    {
        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('test_app_key');
        $config->setAppSecret('test_app_secret');
        $config->setAccessCode('test_access_code');
        $config->setProviderId('test_provider_id');
        $config->setApiGateway('https://global.link.cainiao.com');
        $config->setValid(true);
        $config->setRemark('测试环境菜鸟配置');

        $manager->persist($config);
        $manager->flush();

        $this->addReference(self::CAINIAO_CONFIG_REFERENCE, $config);
    }
}
