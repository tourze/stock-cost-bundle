<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service\Calculator;

use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Model\CostCalculationResult;
use Tourze\StockCostBundle\Service\CostStrategyCalculatorInterface;
use Tourze\StockCostBundle\Service\StandardCostServiceInterface;
use Tourze\StockCostBundle\Service\StockRecordServiceInterface;

/**
 * 标准成本计算器
 *
 * 实现标准成本(Standard Cost)计算策略
 */
class StandardCostCalculator implements CostStrategyCalculatorInterface
{
    public function __construct(
        private StandardCostServiceInterface $standardCostService,
        private StockRecordServiceInterface $stockRecordService,
    ) {
    }

    public function supports(CostStrategy $strategy): bool
    {
        return CostStrategy::STANDARD_COST === $strategy;
    }

    public function getSupportedStrategy(): CostStrategy
    {
        return CostStrategy::STANDARD_COST;
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

        return $this->standardCostService->hasStandardCost($sku);
    }

    public function calculate(string $sku, int $quantity): CostCalculationResult
    {
        $standardCost = $this->standardCostService->getStandardCost($sku);

        if (null === $standardCost) {
            return new CostCalculationResult(
                sku: $sku,
                quantity: $quantity,
                unitCost: 0.00,
                totalCost: 0.00,
                strategy: CostStrategy::STANDARD_COST,
                calculationDetails: [
                    'standardCost' => null,
                    'calculationMethod' => 'standard_cost',
                    'hasStandardCost' => false,
                ],
                isPartialCalculation: true
            );
        }

        $totalCost = $quantity * $standardCost;

        return new CostCalculationResult(
            sku: $sku,
            quantity: $quantity,
            unitCost: $standardCost,
            totalCost: $totalCost,
            strategy: CostStrategy::STANDARD_COST,
            calculationDetails: [
                'standardCost' => $standardCost,
                'calculationMethod' => 'standard_cost',
                'hasStandardCost' => true,
            ],
            isPartialCalculation: false
        );
    }
}
