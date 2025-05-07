<?php

namespace CainiaoPickupBundle\Repository;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;

/**
 * @method CainiaoConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method CainiaoConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method CainiaoConfig[]    findAll()
 * @method CainiaoConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CainiaoConfigRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CainiaoConfig::class);
    }

    /**
     * 获取有效的配置
     */
    public function findValidConfig(): ?CainiaoConfig
    {
        return $this->findOneBy(['valid' => true]);
    }
}
