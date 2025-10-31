<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Controller\Admin;

use CainiaoPickupBundle\Entity\CainiaoConfig;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

/**
 * 菜鸟配置管理控制器
 *
 * @extends AbstractCrudController<CainiaoConfig>
 */
#[AdminCrud(routePath: '/cainiao-pickup/config', routeName: 'cainiao_pickup_config')]
final class CainiaoConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CainiaoConfig::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('菜鸟配置')
            ->setEntityLabelInPlural('菜鸟配置管理')
            ->setPageTitle('index', '菜鸟配置列表')
            ->setPageTitle('new', '新建菜鸟配置')
            ->setPageTitle('edit', '编辑菜鸟配置')
            ->setPageTitle('detail', '菜鸟配置详情')
            ->setHelp('index', '管理菜鸟开放平台的API配置信息，包括AppKey、AppSecret等认证信息')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['name', 'appKey', 'providerId'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
            ->setMaxLength(9999)
        ;

        yield BooleanField::new('valid', '启用状态')
            ->setHelp('配置是否启用')
        ;

        yield TextField::new('name', '配置名称')
            ->setHelp('便于识别的配置名称')
            ->setRequired(true)
        ;

        yield TextField::new('appKey', 'AppKey')
            ->setHelp('菜鸟开放平台提供的应用Key')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        yield TextField::new('appSecret', 'AppSecret')
            ->setHelp('菜鸟开放平台提供的应用Secret')
            ->setRequired(true)
            ->hideOnIndex()
            ->formatValue(function ($value): string {
                if (null === $value || '' === $value) {
                    return '';
                }
                $stringValue = (string) $value;

                return str_repeat('*', 8) . substr($stringValue, -4);
            })
        ;

        yield TextField::new('accessCode', 'AccessCode')
            ->setHelp('菜鸟开放平台的访问码')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        yield TextField::new('providerId', 'Provider ID')
            ->setHelp('物流服务商ID')
            ->hideOnIndex()
        ;

        yield UrlField::new('apiGateway', 'API网关地址')
            ->setHelp('菜鸟开放平台的API网关地址')
            ->setRequired(true)
            ->hideOnIndex()
        ;

        yield TextareaField::new('remark', '备注')
            ->setHelp('配置的备注说明')
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
        return $filters
            ->add(BooleanFilter::new('valid', '启用状态'))
            ->add(TextFilter::new('name', '配置名称'))
            ->add(TextFilter::new('appKey', 'AppKey'))
            ->add(TextFilter::new('providerId', 'Provider ID'))
        ;
    }
}
