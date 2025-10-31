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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\StockCostBundle\Entity\StockRecord;

/**
 * @extends AbstractCrudController<StockRecord>
 */
#[AdminCrud(routePath: '/stock-cost/stock-record', routeName: 'stock_cost_stock_record')]
final class StockRecordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StockRecord::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('库存记录')
            ->setEntityLabelInPlural('库存记录管理')
            ->setPageTitle('index', '库存记录列表')
            ->setPageTitle('new', '创建库存记录')
            ->setPageTitle('edit', '编辑库存记录')
            ->setPageTitle('detail', '库存记录详情')
            ->setHelp('index', '库存记录用于追踪商品库存的历史变化信息')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['sku'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('sku', 'SKU编码')
            ->setHelp('商品SKU唯一标识符')
            ->setRequired(true)
        ;

        yield DateTimeField::new('recordDate', '记录日期')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('库存记录的生成日期')
            ->setRequired(true)
        ;

        yield IntegerField::new('originalQuantity', '原始库存数量')
            ->setHelp('变更前的库存数量')
        ;

        yield IntegerField::new('currentQuantity', '当前库存数量')
            ->setHelp('变更后的当前库存数量')
        ;

        yield MoneyField::new('unitCost', '单位成本')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('商品的单位成本')
        ;

        yield MoneyField::new('totalCost', '总成本')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('总成本 = 当前数量 × 单位成本')
            ->hideOnForm()
        ;

        yield BooleanField::new('isAvailable', '有库存')
            ->hideOnForm()
            ->setHelp('当前是否还有可用库存')
        ;

        yield DateTimeField::new('createdAt', '创建时间')
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
            ->add(TextFilter::new('sku', 'SKU编码'))
            ->add(NumericFilter::new('originalQuantity', '原始数量'))
            ->add(NumericFilter::new('currentQuantity', '当前数量'))
            ->add(NumericFilter::new('unitCost', '单位成本'))
            ->add(NumericFilter::new('totalCost', '总成本'))
            ->add(BooleanFilter::new('isAvailable', '有库存'))
            ->add(DateTimeFilter::new('recordDate', '记录日期'))
            ->add(DateTimeFilter::new('createdAt', '创建时间'))
        ;
    }
}
