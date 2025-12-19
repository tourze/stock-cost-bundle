<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Tourze\StockCostBundle\Service\CostServiceInterface;
use Tourze\StockManageBundle\Event\StockAdjustedEvent;

/**
 * 库存变动监听器
 *
 * 实现FR6.1需求：
 * - 当库存数量发生变化时，自动更新相关成本数据
 * - 当新批次入库时，根据成本策略更新平均成本
 */
#[AsEventListener(event: StockAdjustedEvent::class, method: 'onStockAdjusted')]
#[Autoconfigure(public: true)]
final class StockChangeListener
{
    public function __construct(
        private readonly CostServiceInterface $costService,
    ) {
    }

    /**
     * 处理库存调整事件
     */
    public function onStockAdjusted(StockAdjustedEvent $event): void
    {
        $batch = $event->getStockBatch();
        $skuId = $batch->getSku()?->getId();

        if (null === $skuId || '' === $skuId) {
            return;
        }

        $quantity = $batch->getQuantity();

        // 只处理正数量的变化
        if ($quantity <= 0) {
            return;
        }

        // 当库存数量变化时，重新计算成本
        // 使用现有的calculateCost方法计算成本
        $this->costService->calculateCost($skuId, $quantity);
    }
}
