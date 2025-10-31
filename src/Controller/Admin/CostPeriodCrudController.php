<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;

/**
 * @extends AbstractCrudController<CostPeriod>
 */
#[AdminCrud(routePath: '/stock-cost/cost-period', routeName: 'stock_cost_cost_period')]
final class CostPeriodCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CostPeriod::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('成本期间')
            ->setEntityLabelInPlural('成本期间管理')
            ->setPageTitle('index', '成本期间列表')
            ->setPageTitle('new', '创建成本期间')
            ->setPageTitle('edit', '编辑成本期间')
            ->setPageTitle('detail', '成本期间详情')
            ->setHelp('index', '成本期间用于管理成本核算的时间范围和状态')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['periodStart', 'periodEnd', 'status'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield DateField::new('periodStart', '期间开始日期')
            ->setFormat('yyyy-MM-dd')
            ->setHelp('成本核算期间的开始日期')
            ->setRequired(true)
        ;

        yield DateField::new('periodEnd', '期间结束日期')
            ->setFormat('yyyy-MM-dd')
            ->setHelp('成本核算期间的结束日期')
            ->setRequired(true)
        ;

        $defaultStrategyField = EnumField::new('defaultStrategy', '默认成本策略');
        $defaultStrategyField->setEnumCases(CostStrategy::cases());
        yield $defaultStrategyField->setHelp('该期间默认使用的成本计算策略');

        $statusField = EnumField::new('status', '期间状态');
        $statusField->setEnumCases(CostPeriodStatus::cases());
        yield $statusField->setHelp('期间的当前状态：开放、已关闭或已冻结');

        yield BooleanField::new('isDeleted', '已删除')
            ->hideOnForm()
            ->setHelp('标记该期间是否已被软删除')
        ;

        yield DateTimeField::new('deleteTime', '删除时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
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
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('periodStart', '开始日期'))
            ->add(DateTimeFilter::new('periodEnd', '结束日期'))
            ->add(BooleanFilter::new('isDeleted', '已删除'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
