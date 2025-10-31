<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\DataFixtures;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PickupOrderFixtures extends Fixture implements DependentFixtureInterface
{
    public const PICKUP_ORDER_REFERENCE = 'pickup-order';

    public function load(ObjectManager $manager): void
    {
        $addressInfo = $this->getReference(AddressInfoFixtures::ADDRESS_INFO_REFERENCE, AddressInfo::class);
        $config = $this->getReference(CainiaoConfigFixtures::CAINIAO_CONFIG_REFERENCE, CainiaoConfig::class);

        $order = new PickupOrder();
        $order->setOrderCode('TEST_ORDER_001');
        $order->setSenderInfo($addressInfo);
        $order->setReceiverInfo($addressInfo);
        $order->setItemType(ItemTypeEnum::DOCUMENT);
        $order->setWeight(0.5);
        $order->setStatus(OrderStatusEnum::CREATE);
        $order->setExternalUserId('test_user_001');
        $order->setExternalUserMobile('13800138000');
        $order->setConfig($config);
        $order->setRemark('测试订单');
        $order->setExpectPickupTimeStart('09:00');
        $order->setExpectPickupTimeEnd('18:00');
        $order->setItemQuantity('1');
        $order->setItemValue(100.00);

        $manager->persist($order);
        $manager->flush();

        $this->addReference(self::PICKUP_ORDER_REFERENCE, $order);
    }

    public function getDependencies(): array
    {
        return [
            AddressInfoFixtures::class,
            CainiaoConfigFixtures::class,
        ];
    }
}
