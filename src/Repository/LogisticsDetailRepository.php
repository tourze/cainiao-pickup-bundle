<?php

namespace CainiaoPickupBundle\Repository;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @method LogisticsDetail|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogisticsDetail|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogisticsDetail[]    findAll()
 * @method LogisticsDetail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogisticsDetailRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LogisticsDetail::class);
    }

    /**
     * 获取订单的物流详情列表
     */
    public function findByOrder(PickupOrder $order): array
    {
        return $this->findBy(
            ['order' => $order],
            ['logisticsTime' => 'DESC']
        );
    }

    /**
     * 获取订单最新的物流详情
     */
    public function findLatestByOrder(PickupOrder $order): ?LogisticsDetail
    {
        return $this->findOneBy(
            ['order' => $order],
            ['logisticsTime' => 'DESC']
        );
    }

    /**
     * 删除订单的所有物流详情
     */
    public function deleteByOrder(PickupOrder $order): void
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->where('l.order = :order')
            ->setParameter('order', $order)
            ->getQuery()
            ->execute();
    }
}
