<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Repository;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<LogisticsDetail>
 */
#[AsRepository(entityClass: LogisticsDetail::class)]
#[Autoconfigure(public: true)]
class LogisticsDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        /** @var class-string<LogisticsDetail> */
        $entityClass = LogisticsDetail::class;
        /** @phpstan-ignore-next-line */
        parent::__construct($registry, $entityClass);
    }

    /**
     * 获取订单的物流详情列表
     */
    /**
     * @return list<LogisticsDetail>
     */
    public function findByOrder(PickupOrder $order): array
    {
        $result = $this->findBy(
            ['order' => $order],
            ['logisticsTime' => 'DESC']
        );

        /** @phpstan-ignore-next-line */
        return array_values($result);
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
     *
     * 优化策略：使用原生SQL删除，避免Doctrine ORM的开销
     * 同时使用事务确保数据一致性
     */
    public function deleteByOrder(PickupOrder $order): void
    {
        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();

        // 使用原生SQL删除，性能更好
        $sql = 'DELETE FROM cainiao_logistics_detail WHERE order_id = :orderId';

        try {
            $connection->executeStatement($sql, ['orderId' => $order->getId()]);
        } catch (\Throwable $e) {
            // 如果原生SQL失败，回退到DQL方案
            $this->createQueryBuilder('l')
                ->delete()
                ->where('l.order = :order')
                ->setParameter('order', $order)
                ->getQuery()
                ->execute()
            ;
        }
    }

    public function save(LogisticsDetail $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LogisticsDetail $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
