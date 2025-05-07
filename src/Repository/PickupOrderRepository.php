<?php

namespace CainiaoPickupBundle\Repository;

use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;

/**
 * @method PickupOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method PickupOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method PickupOrder[]    findAll()
 * @method PickupOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PickupOrderRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PickupOrder::class);
    }

    /**
     * 根据订单号查询订单
     */
    public function findByOrderCode(string $orderCode): ?PickupOrder
    {
        return $this->findOneBy(['orderCode' => $orderCode]);
    }

    /**
     * 根据状态查询订单列表
     */
    public function findByStatus(OrderStatusEnum $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * 查询未完成的订单
     *
     * 未完成的订单包括:
     * - 待处理
     * - 已接单
     * - 取件中
     */
    public function findUnfinishedOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', [
                OrderStatusEnum::CREATE->value,
                OrderStatusEnum::WAREHOUSE_ACCEPT->value,
                OrderStatusEnum::WAREHOUSE_PROCESS->value,
            ])
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
