<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\StockCostBundle\Event\CostVarianceExceededEvent;
use Tourze\StockCostBundle\Repository\CostRecordRepository;

class CostVarianceAnalysisService implements CostVarianceAnalysisServiceInterface
{
    public function __construct(
        private readonly CostRecordRepository $costRecordRepository,
        private readonly StandardCostServiceInterface $standardCostService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly float $varianceThreshold = 0.1, // 默认10%阈值
    ) {
    }

    /**
     * 分析SKU的成本差异
     *
     * @return array{absoluteVariance: float, relativeVariance: float, exceedsThreshold: bool}|null
     */
    public function analyzeVarianceForSku(string $skuId): ?array
    {
        // 获取实际成本
        $actualCost = $this->costRecordRepository->getAverageActualCost($skuId);
        if (null === $actualCost) {
            return null;
        }

        // 获取标准成本
        $standardCost = $this->standardCostService->getStandardCost($skuId);
        if (null === $standardCost || 0.0 === $standardCost) {
            return null;
        }

        // 计算绝对差异和相对差异
        $absoluteVariance = $actualCost - $standardCost;
        $relativeVariance = $absoluteVariance / $standardCost;

        // 判断是否超过阈值（使用绝对值比较）
        $exceedsThreshold = abs($relativeVariance) > $this->varianceThreshold;

        // 如果超过阈值，触发事件
        if ($exceedsThreshold) {
            $event = new CostVarianceExceededEvent(
                $skuId,
                $actualCost,
                $standardCost,
                $absoluteVariance,
                $relativeVariance
            );
            $this->eventDispatcher->dispatch($event);
        }

        return [
            'absoluteVariance' => $absoluteVariance,
            'relativeVariance' => $relativeVariance,
            'exceedsThreshold' => $exceedsThreshold,
        ];
    }

    /**
     * 获取成本趋势分析
     *
     * @return array{periods: int, startCost: float, endCost: float, overallChange: float, averageCost: float, volatility: float}
     */
    public function getCostTrendAnalysis(string $skuId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $costHistory = $this->costRecordRepository->getCostHistoryForSku($skuId, $startDate, $endDate);

        if ([] === $costHistory) {
            return [
                'periods' => 0,
                'startCost' => 0.0,
                'endCost' => 0.0,
                'overallChange' => 0.0,
                'averageCost' => 0.0,
                'volatility' => 0.0,
            ];
        }

        $periods = count($costHistory);
        $startCost = (float) $costHistory[0]['avgCost'];
        $endCost = (float) $costHistory[$periods - 1]['avgCost'];

        // 计算总体变化率
        $overallChange = $startCost > 0 ? ($endCost - $startCost) / $startCost : 0.0;

        // 计算平均成本
        $totalCost = array_sum(array_column($costHistory, 'avgCost'));
        $averageCost = $totalCost / $periods;

        // 计算波动率（标准差）
        $volatility = $this->calculateVolatility($costHistory, $averageCost);

        return [
            'periods' => $periods,
            'startCost' => $startCost,
            'endCost' => $endCost,
            'overallChange' => $overallChange,
            'averageCost' => $averageCost,
            'volatility' => $volatility,
        ];
    }

    /**
     * 批量分析多个SKU的成本差异
     *
     * @param string[] $skuIds
     * @return array<string, array<string, mixed>|null>
     */
    public function batchAnalyzeVariance(array $skuIds): array
    {
        $results = [];

        foreach ($skuIds as $skuId) {
            $results[$skuId] = $this->analyzeVarianceForSku($skuId);
        }

        return $results;
    }

    /**
     * 计算成本波动率
     *
     * @param array<int, array{date: string, avgCost: float}> $costHistory
     */
    private function calculateVolatility(array $costHistory, float $averageCost): float
    {
        if (count($costHistory) < 2) {
            return 0.0;
        }

        $sumSquaredDifferences = 0.0;
        foreach ($costHistory as $record) {
            $cost = (float) $record['avgCost'];
            $sumSquaredDifferences += ($cost - $averageCost) ** 2;
        }

        $variance = $sumSquaredDifferences / count($costHistory);

        return sqrt($variance);
    }
}
