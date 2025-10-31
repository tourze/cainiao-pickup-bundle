<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\DataFixtures;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LogisticsDetailFixtures extends Fixture implements DependentFixtureInterface
{
    public const LOGISTICS_DETAIL_REFERENCE = 'logistics-detail';

    public function load(ObjectManager $manager): void
    {
        $order = $this->getReference(PickupOrderFixtures::PICKUP_ORDER_REFERENCE, PickupOrder::class);

        $logisticsDetail = new LogisticsDetail();
        $logisticsDetail->setOrder($order);
        $logisticsDetail->setMailNo('YTO123456789');
        $logisticsDetail->setLogisticsStatus('已揽收');
        $logisticsDetail->setLogisticsDescription('快递员已取件');
        $logisticsDetail->setLogisticsTime(new \DateTimeImmutable('2023-10-01 10:00:00'));
        $logisticsDetail->setCity('北京市');
        $logisticsDetail->setArea('朝阳区');
        $logisticsDetail->setAddress('建国门外大街1号');
        $logisticsDetail->setCourierName('李四');
        $logisticsDetail->setCourierPhone('13900139000');

        $manager->persist($logisticsDetail);
        $manager->flush();

        $this->addReference(self::LOGISTICS_DETAIL_REFERENCE, $logisticsDetail);
    }

    public function getDependencies(): array
    {
        return [
            PickupOrderFixtures::class,
        ];
    }
}
