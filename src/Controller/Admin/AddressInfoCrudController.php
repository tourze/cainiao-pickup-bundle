<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Controller\Admin;

use CainiaoPickupBundle\Entity\AddressInfo;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * 地址信息管理控制器
 *
 * @extends AbstractCrudController<AddressInfo>
 */
#[AdminCrud(routePath: '/cainiao-pickup/address-info', routeName: 'cainiao_pickup_address_info')]
final class AddressInfoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AddressInfo::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('地址信息')
            ->setEntityLabelInPlural('地址信息管理')
            ->setPageTitle('index', '地址信息列表')
            ->setPageTitle('new', '新建地址信息')
            ->setPageTitle('edit', '编辑地址信息')
            ->setPageTitle('detail', '地址信息详情')
            ->setHelp('index', '管理取件和配送的地址信息，包括联系人信息和详细地址')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['name', 'mobile', 'fullAddressDetail', 'provinceName', 'cityName'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
            ->setMaxLength(9999)
        ;

        yield TextField::new('name', '联系人姓名')
            ->setHelp('联系人的真实姓名')
            ->setRequired(true)
        ;

        yield TelephoneField::new('mobile', '联系电话')
            ->setHelp('联系人的手机号码')
            ->setRequired(true)
        ;

        yield TextareaField::new('fullAddressDetail', '完整地址')
            ->setHelp('完整的地址详情描述')
            ->setRequired(true)
            ->setNumOfRows(3)
        ;

        yield TextField::new('provinceName', '省份')
            ->setHelp('所在省份名称')
            ->hideOnIndex()
        ;

        yield TextField::new('cityName', '城市')
            ->setHelp('所在城市名称')
        ;

        yield TextField::new('areaName', '区域')
            ->setHelp('所在区/县名称')
            ->hideOnIndex()
        ;

        yield TextField::new('address', '详细地址')
            ->setHelp('街道及门牌号等详细地址')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '联系人姓名'))
            ->add(TextFilter::new('mobile', '联系电话'))
            ->add(TextFilter::new('cityName', '城市'))
            ->add(TextFilter::new('fullAddressDetail', '完整地址'))
        ;
    }
}
