<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\DataFixtures;

use CainiaoPickupBundle\Entity\AddressInfo;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AddressInfoFixtures extends Fixture
{
    public const ADDRESS_INFO_REFERENCE = 'address-info';

    public function load(ObjectManager $manager): void
    {
        $addressInfo = new AddressInfo();
        $addressInfo->setName('张三');
        $addressInfo->setMobile('13800138000');
        $addressInfo->setFullAddressDetail('北京市朝阳区建国门外大街1号');
        $addressInfo->setProvinceName('北京市');
        $addressInfo->setCityName('北京市');
        $addressInfo->setAreaName('朝阳区');
        $addressInfo->setAddress('建国门外大街1号');

        $manager->persist($addressInfo);
        $manager->flush();

        $this->addReference(self::ADDRESS_INFO_REFERENCE, $addressInfo);
    }
}
