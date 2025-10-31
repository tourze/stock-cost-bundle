<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;

/**
 * @extends AbstractCrudController<CostRecord>
 */
#[AdminCrud(routePath: '/stock-cost/cost-record', routeName: 'stock_cost_cost_record')]
final class CostRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CostRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('成本记录')
            ->setEntityLabelInPlural('成本记录管理')
            ->setPageTitle('index', '成本记录列表')
            ->setPageTitle('new', '创建成本记录')
            ->setPageTitle('edit', '编辑成本记录')
            ->setPageTitle('detail', '成本记录详情')
            ->setHelp('index', '成本记录用于追踪商品的成本变化历史')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['skuId', 'batchNo', 'operator'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('skuId', 'SKU标识')
            ->setHelp('商品SKU唯一标识符')
            ->setRequired(true)
        ;

        yield TextField::new('batchNo', '批次号')
            ->setHelp('商品批次编号，可为空')
        ;

        yield MoneyField::new('unitCost', '单位成本')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('商品的单位成本价格')
        ;

        yield IntegerField::new('quantity', '数量')
            ->setHelp('库存数量，必须为正数')
        ;

        yield MoneyField::new('totalCost', '总成本')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('总成本 = 单位成本 × 数量')
        ;

        $costStrategyField = EnumField::new('costStrategy', '成本策略');
        $costStrategyField->setEnumCases(CostStrategy::cases());
        yield $costStrategyField->setHelp('使用的成本计算策略');

        $costTypeField = EnumField::new('costType', '成本类型');
        $costTypeField->setEnumCases(CostType::cases());
        yield $costTypeField->setHelp('成本的分类类型');

        yield AssociationField::new('period', '所属期间')
            ->hideOnIndex()
            ->setHelp('关联的成本核算期间')
        ;

        yield TextField::new('operator', '操作员')
            ->hideOnIndex()
            ->setHelp('执行此次成本记录操作的人员')
        ;

        yield AssociationField::new('stockBatch', '库存批次')
            ->hideOnIndex()
            ->setHelp('关联的库存批次信息')
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            // 在索引页面不显示metadata字段
        } else {
            yield TextareaField::new('metadata', '元数据')
                ->setHelp('存储额外的元数据信息')
                ->setRequired(false)
                ->setValue('{}')
            ;
        }

        yield DateTimeField::new('recordedAt', '记录时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updatedAt', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
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
            ->add(TextFilter::new('skuId', 'SKU标识'))
            ->add(TextFilter::new('batchNo', '批次号'))
            ->add(NumericFilter::new('unitCost', '单位成本'))
            ->add(NumericFilter::new('quantity', '数量'))
            ->add(NumericFilter::new('totalCost', '总成本'))
            ->add(TextFilter::new('operator', '操作员'))
            ->add(DateTimeFilter::new('recordedAt', '记录时间'))
        ;
    }
}
