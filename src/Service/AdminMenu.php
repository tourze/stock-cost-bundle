<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Entity\StockRecord;

/**
 * 库存成本模块的管理菜单
 */
#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('库存成本管理')) {
            $item->addChild('库存成本管理');
        }

        $stockCostMenu = $item->getChild('库存成本管理');

        if (null === $stockCostMenu) {
            return;
        }

        // 成本期间管理
        $stockCostMenu
            ->addChild('成本期间')
            ->setUri($this->linkGenerator->getCurdListPage(CostPeriod::class))
            ->setAttribute('icon', 'fas fa-calendar')
        ;

        // 库存记录
        $stockCostMenu
            ->addChild('库存记录')
            ->setUri($this->linkGenerator->getCurdListPage(StockRecord::class))
            ->setAttribute('icon', 'fas fa-boxes')
        ;

        // 成本记录
        $stockCostMenu
            ->addChild('成本记录')
            ->setUri($this->linkGenerator->getCurdListPage(CostRecord::class))
            ->setAttribute('icon', 'fas fa-receipt')
        ;

        // 成本分摊
        $stockCostMenu
            ->addChild('成本分摊')
            ->setUri($this->linkGenerator->getCurdListPage(CostAllocation::class))
            ->setAttribute('icon', 'fas fa-share-alt')
        ;
    }
}
