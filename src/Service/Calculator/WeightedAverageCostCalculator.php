<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;
use Tourze\StockCostBundle\Service\StockRecordServiceInterface;

/**
 * 加权平均成本计算器
 *
 * 实现加权平均(Weighted Average)成本计算策略
 */
class WeightedAverageCostCalculator implements CostStrategyCalculatorInterface
{
    public function __construct(
        private StockRecordServiceInterface $stockRecordService,
    ) {
    }

    public function supports(CostStrategy $strategy): bool
    {
        return CostStrategy::WEIGHTED_AVERAGE === $strategy;
    }

    public function getSupportedStrategy(): CostStrategy
    {
        return CostStrategy::WEIGHTED_AVERAGE;
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

        if ([] === $stockRecords) {
            return new CostCalculationResult(
                sku: $sku,
                quantity: $quantity,
                unitCost: 0.00,
                totalCost: 0.00,
                strategy: CostStrategy::WEIGHTED_AVERAGE,
                calculationDetails: ['availableQuantity' => 0, 'shortageQuantity' => $quantity],
                isPartialCalculation: true
            );
        }

        // 计算加权平均单价
        $totalValue = 0.00;
        $totalQuantity = 0;
        $availableRecords = [];

        foreach ($stockRecords as $record) {
            $availableQuantity = $record->getCurrentQuantity();
            if ($availableQuantity <= 0) {
                continue;
            }

            $recordValue = $availableQuantity * $record->getUnitCost();
            $totalValue += $recordValue;
            $totalQuantity += $availableQuantity;

            $availableRecords[] = [
                'recordId' => $record->getId(),
                'date' => $record->getRecordDate()?->format('Y-m-d') ?? '',
                'quantity' => $availableQuantity,
                'unitCost' => $record->getUnitCost(),
                'totalValue' => $recordValue,
            ];
        }

        if ($totalQuantity <= 0) {
            return new CostCalculationResult(
                sku: $sku,
                quantity: $quantity,
                unitCost: 0.00,
                totalCost: 0.00,
                strategy: CostStrategy::WEIGHTED_AVERAGE,
                calculationDetails: ['availableQuantity' => 0, 'shortageQuantity' => $quantity],
                isPartialCalculation: true
            );
        }

        $weightedAverageUnitCost = $totalValue / $totalQuantity;
        $totalCost = $quantity * $weightedAverageUnitCost;
        $isPartial = $quantity > $totalQuantity;

        return new CostCalculationResult(
            sku: $sku,
            quantity: $quantity,
            unitCost: $weightedAverageUnitCost,
            totalCost: $totalCost,
            strategy: CostStrategy::WEIGHTED_AVERAGE,
            calculationDetails: [
                'weightedAverageUnitCost' => $weightedAverageUnitCost,
                'totalAvailableQuantity' => $totalQuantity,
                'totalAvailableValue' => $totalValue,
                'availableRecords' => $availableRecords,
                'availableQuantity' => min($quantity, $totalQuantity),
                'shortageQuantity' => max(0, $quantity - $totalQuantity),
            ],
            isPartialCalculation: $isPartial
        );
    }
}
