<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Repository;

use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<PickupOrder>
 */
#[AsRepository(entityClass: PickupOrder::class)]
#[Autoconfigure(public: true)]
class PickupOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        /** @var class-string<PickupOrder> */
        $entityClass = PickupOrder::class;
        /** @phpstan-ignore-next-line */
        parent::__construct($registry, $entityClass);
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
     * @return list<PickupOrder>
     */
    public function findByStatus(OrderStatusEnum $status): array
    {
        $result = $this->findBy(['status' => $status]);

        /** @phpstan-ignore-next-line */
        return array_values($result);
    }

    /**
     * 查询未完成的订单
     *
     * 未完成的订单包括:
     * - 待处理
     * - 已接单
     * - 取件中
     */
    /**
     * @return list<PickupOrder>
     */
    public function findUnfinishedOrders(): array
    {
        $result = $this->createQueryBuilder('o')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', [
                OrderStatusEnum::CREATE->value,
                OrderStatusEnum::WAREHOUSE_ACCEPT->value,
                OrderStatusEnum::WAREHOUSE_PROCESS->value,
            ])
            ->orderBy('o.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        if (!is_array($result)) {
            return [];
        }

        /** @phpstan-ignore-next-line */
        return array_values($result);
    }

    public function save(PickupOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PickupOrder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
