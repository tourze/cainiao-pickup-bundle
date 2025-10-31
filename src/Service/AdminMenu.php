<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Service;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\LogisticsDetail;
use CainiaoPickupBundle\Entity\PickupOrder;
use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

/**
 * 菜鸟取件服务菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('菜鸟取件服务')) {
            $item->addChild('菜鸟取件服务');
        }

        $cainiaoMenu = $item->getChild('菜鸟取件服务');
        if (null === $cainiaoMenu) {
            return;
        }

        // 取件订单管理菜单
        $cainiaoMenu->addChild('取件订单管理')
            ->setUri($this->linkGenerator->getCurdListPage(PickupOrder::class))
            ->setAttribute('icon', 'fas fa-truck-pickup')
        ;

        // 物流详情管理菜单
        $cainiaoMenu->addChild('物流详情管理')
            ->setUri($this->linkGenerator->getCurdListPage(LogisticsDetail::class))
            ->setAttribute('icon', 'fas fa-route')
        ;

        // 地址信息管理菜单
        $cainiaoMenu->addChild('地址信息管理')
            ->setUri($this->linkGenerator->getCurdListPage(AddressInfo::class))
            ->setAttribute('icon', 'fas fa-map-marker-alt')
        ;

        // 菜鸟配置管理菜单
        $cainiaoMenu->addChild('菜鸟配置管理')
            ->setUri($this->linkGenerator->getCurdListPage(CainiaoConfig::class))
            ->setAttribute('icon', 'fas fa-cogs')
        ;
    }
}
