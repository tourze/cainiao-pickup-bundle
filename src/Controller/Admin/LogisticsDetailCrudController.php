<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Controller\Admin;

use CainiaoPickupBundle\Entity\LogisticsDetail;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * 物流详情管理控制器
 *
 * @extends AbstractCrudController<LogisticsDetail>
 */
#[AdminCrud(routePath: '/cainiao-pickup/logistics-detail', routeName: 'cainiao_pickup_logistics_detail')]
final class LogisticsDetailCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LogisticsDetail::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('物流详情')
            ->setEntityLabelInPlural('物流详情管理')
            ->setPageTitle('index', '物流详情列表')
            ->setPageTitle('new', '新建物流详情')
            ->setPageTitle('edit', '编辑物流详情')
            ->setPageTitle('detail', '物流详情详情')
            ->setHelp('index', '管理取件订单的物流跟踪详情信息，包括物流状态、时间和描述')
            ->setDefaultSort(['logisticsTime' => 'DESC'])
            ->setSearchFields(['mailNo', 'logisticsStatus', 'logisticsDescription', 'courierName'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
            ->setMaxLength(9999)
        ;

        yield AssociationField::new('order', '取件订单')
            ->setRequired(true)
            ->setCrudController(PickupOrderCrudController::class)
            ->autocomplete()
            ->setHelp('该物流详情所属的取件订单')
        ;

        yield TextField::new('mailNo', '运单号')
            ->setHelp('快递公司的运单号码')
            ->setRequired(true)
        ;

        yield TextField::new('logisticsStatus', '物流状态')
            ->setHelp('当前物流状态')
            ->setRequired(true)
        ;

        yield TextareaField::new('logisticsDescription', '物流描述')
            ->setHelp('物流状态的详细描述')
            ->setRequired(true)
            ->setNumOfRows(2)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('logisticsTime', '物流时间')
            ->setHelp('物流状态更新时间')
            ->setRequired(true)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield TextField::new('city', '所在城市')
            ->setHelp('当前物流所在城市')
            ->hideOnIndex()
        ;

        yield TextField::new('area', '所在地区')
            ->setHelp('当前物流所在地区')
            ->hideOnIndex()
        ;

        yield TextField::new('address', '详细地址')
            ->setHelp('当前物流的详细地址')
            ->hideOnIndex()
        ;

        yield TextField::new('courierName', '快递员姓名')
            ->setHelp('负责配送的快递员姓名')
            ->hideOnIndex()
        ;

        yield TelephoneField::new('courierPhone', '快递员电话')
            ->setHelp('负责配送的快递员联系电话')
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
            ->add(EntityFilter::new('order', '取件订单'))
            ->add(TextFilter::new('mailNo', '运单号'))
            ->add(TextFilter::new('logisticsStatus', '物流状态'))
            ->add(TextFilter::new('city', '城市'))
            ->add(TextFilter::new('courierName', '快递员姓名'))
        ;
    }
}
