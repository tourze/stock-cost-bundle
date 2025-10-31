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
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Enum\CostType;

/**
 * @extends AbstractCrudController<CostAllocation>
 */
#[AdminCrud(routePath: '/stock-cost/cost-allocation', routeName: 'stock_cost_cost_allocation')]
final class CostAllocationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CostAllocation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('成本分摊')
            ->setEntityLabelInPlural('成本分摊管理')
            ->setPageTitle('index', '成本分摊列表')
            ->setPageTitle('new', '创建成本分摊')
            ->setPageTitle('edit', '编辑成本分摊')
            ->setPageTitle('detail', '成本分摊详情')
            ->setHelp('index', '成本分摊用于将成本按照指定的方法分配到不同的目标对象')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['allocationName', 'sourceType'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('allocationName', '分摊名称')
            ->setHelp('用于识别这次成本分摊的名称')
            ->setRequired(true)
        ;

        $sourceTypeField = EnumField::new('sourceType', '源成本类型');
        $sourceTypeField->setEnumCases(CostType::cases());
        yield $sourceTypeField->setHelp('需要分摊的成本类型');

        yield MoneyField::new('totalAmount', '总金额')
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('需要分摊的总金额')
        ;

        $allocationMethodField = EnumField::new('allocationMethod', '分摊方法');
        $allocationMethodField->setEnumCases(AllocationMethod::cases());
        yield $allocationMethodField->setHelp('选择成本分摊的计算方法');

        yield DateTimeField::new('allocationDate', '分摊日期')
            ->setFormat('yyyy-MM-dd')
            ->setHelp('执行成本分摊的日期')
        ;

        yield AssociationField::new('period', '所属期间')
            ->hideOnIndex()
            ->setHelp('关联的成本核算期间')
        ;

        if (Crud::PAGE_INDEX === $pageName) {
            // 在索引页面不显示targets字段
        } else {
            yield TextareaField::new('targets', '分摊目标配置')
                ->setHelp('JSON格式的分摊目标配置信息')
                ->setRequired(false)
                ->setValue('[]')
            ;
        }

        yield DateTimeField::new('createdAt', '创建时间')
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
            ->add(TextFilter::new('allocationName', '分摊名称'))
            ->add(NumericFilter::new('totalAmount', '总金额'))
            ->add(DateTimeFilter::new('allocationDate', '分摊日期'))
            ->add(DateTimeFilter::new('createdAt', '创建时间'))
        ;
    }
}
