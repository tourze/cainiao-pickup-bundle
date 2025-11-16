<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Repository;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<CainiaoConfig>
 */
#[AsRepository(entityClass: CainiaoConfig::class)]
#[Autoconfigure(public: true)]
class CainiaoConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        /** @var class-string<CainiaoConfig> */
        $entityClass = CainiaoConfig::class;
        parent::__construct($registry, $entityClass);
    }

    /**
     * 获取有效的配置
     */
    public function findValidConfig(): ?CainiaoConfig
    {
        return $this->findOneBy(['valid' => true]);
    }

    public function save(CainiaoConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CainiaoConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
