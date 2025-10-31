<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;
use Tourze\StockCostBundle\Service\StockRecordServiceInterface;

/**
 * FIFO成本计算器
 *
 * 实现先进先出(First In First Out)成本计算策略
 */
class FifoCostCalculator implements CostStrategyCalculatorInterface
{
    public function __construct(
        private StockRecordServiceInterface $stockRecordService,
    ) {
    }

    public function supports(CostStrategy $strategy): bool
    {
        return CostStrategy::FIFO === $strategy;
    }

    public function getSupportedStrategy(): CostStrategy
    {
        return CostStrategy::FIFO;
    }

    /**
     * @param array<string> $skus
     * @return array<CostCalculationResult>
     */
    public function recalculate(array $skus): array
    {
        $results = [];
        foreach ($skus as $sku) {
            $currentStock = $this->stockRecordService->getCurrentStock($sku);
            if ($currentStock > 0) {
                $results[] = $this->calculate($sku, $currentStock);
            }
        }

        return $results;
    }

    public function canCalculate(string $sku, int $quantity): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        $stockRecords = $this->stockRecordService->getStockRecordsForSku($sku);

        return [] !== $stockRecords;
    }

    public function calculate(string $sku, int $quantity): CostCalculationResult
    {
        $stockRecords = $this->stockRecordService->getStockRecordsForSku($sku);

        // 按时间排序（最早的在前）
        usort($stockRecords, fn ($a, $b) => $a->getRecordDate() <=> $b->getRecordDate());

        $remainingQuantity = $quantity;
        $totalCost = 0.00;
        $usedRecords = [];
        $isPartial = false;

        foreach ($stockRecords as $record) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $availableQuantity = $record->getCurrentQuantity();
            if ($availableQuantity <= 0) {
                continue;
            }

            $usedFromThisRecord = min($remainingQuantity, $availableQuantity);
            $costFromThisRecord = $usedFromThisRecord * $record->getUnitCost();

            $totalCost += $costFromThisRecord;
            $remainingQuantity -= $usedFromThisRecord;

            $usedRecords[] = [
                'recordId' => $record->getId(),
                'date' => $record->getRecordDate()?->format('Y-m-d') ?? '',
                'unitCost' => $record->getUnitCost(),
                'quantityUsed' => $usedFromThisRecord,
                'costUsed' => $costFromThisRecord,
            ];
        }

        // 如果还有剩余数量未分配，标记为部分计算
        if ($remainingQuantity > 0) {
            $isPartial = true;

            // 对于无库存部分，使用最后一个有效记录的单价，如果没有则为0
            if ([] !== $usedRecords) {
                $lastRecord = end($usedRecords);
                $lastUnitCost = $lastRecord['unitCost'];
                $totalCost += $remainingQuantity * $lastUnitCost;
            }
        }

        $unitCost = $quantity > 0 ? $totalCost / $quantity : 0.00;

        return new CostCalculationResult(
            sku: $sku,
            quantity: $quantity,
            unitCost: $unitCost,
            totalCost: $totalCost,
            strategy: CostStrategy::FIFO,
            calculationDetails: [
                'usedRecords' => $usedRecords,
                'availableQuantity' => $quantity - $remainingQuantity,
                'shortageQuantity' => $remainingQuantity,
            ],
            isPartialCalculation: $isPartial
        );
    }
}
