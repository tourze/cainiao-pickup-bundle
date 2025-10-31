<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Controller\Admin;

use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\EnumType;

/**
 * 取件订单管理控制器
 *
 * @extends AbstractCrudController<PickupOrder>
 */
#[AdminCrud(routePath: '/cainiao-pickup/pickup-order', routeName: 'cainiao_pickup_pickup_order')]
final class PickupOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PickupOrder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('取件订单')
            ->setEntityLabelInPlural('取件订单管理')
            ->setPageTitle('index', '取件订单列表')
            ->setPageTitle('new', '新建取件订单')
            ->setPageTitle('edit', '编辑取件订单')
            ->setPageTitle('detail', '取件订单详情')
            ->setHelp('index', '管理菜鸟上门取件订单，包括发件人、收件人信息和订单状态跟踪')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['orderCode', 'cainiaoOrderCode', 'mailNo', 'externalUserId'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
            ->setMaxLength(9999)
        ;

        yield TextField::new('orderCode', '取件单号')
            ->setHelp('系统生成的取件订单编号')
            ->setRequired(true)
        ;

        yield AssociationField::new('config', '菜鸟配置')
            ->setRequired(true)
            ->setCrudController(CainiaoConfigCrudController::class)
            ->autocomplete()
            ->setHelp('使用的菜鸟开放平台配置')
        ;

        yield AssociationField::new('senderInfo', '发件人信息')
            ->setRequired(true)
            ->setCrudController(AddressInfoCrudController::class)
            ->autocomplete()
            ->setHelp('发件人的联系信息和地址')
        ;

        yield AssociationField::new('receiverInfo', '收件人信息')
            ->setRequired(true)
            ->setCrudController(AddressInfoCrudController::class)
            ->autocomplete()
            ->setHelp('收件人的联系信息和地址')
        ;

        yield ChoiceField::new('itemType', '物品类型')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => ItemTypeEnum::class])
            ->formatValue(function ($value) {
                return $value instanceof ItemTypeEnum ? $value->getLabel() : '';
            })
            ->setRequired(true)
            ->setHelp('选择寄送物品的类型')
        ;

        yield NumberField::new('weight', '物品重量(kg)')
            ->setHelp('物品的重量，单位为千克')
            ->setRequired(true)
            ->setNumDecimals(2)
        ;

        yield ChoiceField::new('status', '订单状态')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions(['class' => OrderStatusEnum::class])
            ->formatValue(function ($value) {
                return $value instanceof OrderStatusEnum ? $value->getLabel() : '';
            })
            ->setRequired(true)
            ->setHelp('当前订单的处理状态')
        ;

        yield TextField::new('externalUserId', '外部用户ID')
            ->setHelp('外部系统的用户标识')
            ->hideOnIndex()
        ;

        yield TelephoneField::new('externalUserMobile', '外部用户手机号')
            ->setHelp('外部用户的手机号码')
            ->hideOnIndex()
        ;

        yield TextField::new('expectPickupTimeStart', '预约开始时间')
            ->setHelp('预约取件的开始时间')
            ->hideOnIndex()
        ;

        yield TextField::new('expectPickupTimeEnd', '预约结束时间')
            ->setHelp('预约取件的结束时间')
            ->hideOnIndex()
        ;

        yield TextField::new('cainiaoOrderCode', '菜鸟订单号')
            ->setHelp('菜鸟系统返回的订单号')
            ->hideOnIndex()
        ;

        yield TextField::new('mailNo', '运单号')
            ->setHelp('快递公司的运单号')
        ;

        yield TextField::new('itemQuantity', '物品数量')
            ->setHelp('寄送物品的数量')
            ->hideOnIndex()
        ;

        yield MoneyField::new('itemValue', '物品价值')
            ->setCurrency('CNY')
            ->setHelp('物品的声明价值')
            ->hideOnIndex()
        ;

        yield TextField::new('courierName', '快递员姓名')
            ->setHelp('负责取件的快递员姓名')
            ->hideOnIndex()
        ;

        yield TelephoneField::new('courierPhone', '快递员电话')
            ->setHelp('负责取件的快递员联系电话')
            ->hideOnIndex()
        ;

        yield TextField::new('cpCode', '快递公司编码')
            ->setHelp('快递公司的系统编码')
            ->hideOnIndex()
        ;

        yield TextField::new('cpName', '快递公司名称')
            ->setHelp('快递公司的名称')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('pickupTime', '取件时间')
            ->setHelp('实际取件的时间')
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('lastUpdateTime', '最后更新时间')
            ->setHelp('订单信息最后更新的时间')
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield TextareaField::new('cancelReason', '取消原因')
            ->setHelp('订单取消的原因说明')
            ->setNumOfRows(3)
            ->hideOnIndex()
        ;

        yield DateTimeField::new('cancelTime', '取消时间')
            ->setHelp('订单取消的时间')
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield TextareaField::new('remark', '备注')
            ->setHelp('订单的备注信息')
            ->setNumOfRows(3)
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
        // 构建物品类型选项
        $itemTypeChoices = [];
        foreach (ItemTypeEnum::cases() as $case) {
            $itemTypeChoices[$case->getLabel()] = $case->value;
        }

        // 构建订单状态选项
        $statusChoices = [];
        foreach (OrderStatusEnum::cases() as $case) {
            $statusChoices[$case->getLabel()] = $case->value;
        }

        return $filters
            ->add(EntityFilter::new('config', '菜鸟配置'))
            ->add(TextFilter::new('orderCode', '取件单号'))
            ->add(TextFilter::new('mailNo', '运单号'))
            ->add(TextFilter::new('externalUserId', '外部用户ID'))
            ->add(ChoiceFilter::new('itemType', '物品类型')->setChoices($itemTypeChoices))
            ->add(ChoiceFilter::new('status', '订单状态')->setChoices($statusChoices))
            ->add(TextFilter::new('cpName', '快递公司名称'))
        ;
    }
}
