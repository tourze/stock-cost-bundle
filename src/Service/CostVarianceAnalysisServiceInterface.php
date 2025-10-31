<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Service;

/**
 * 成本差异分析服务接口
 *
 * 实现FR4.1和FR4.2需求：
 * - 预算成本对比
 * - 成本趋势分析
 * - 差异超过阈值时触发警报
 */
interface CostVarianceAnalysisServiceInterface
{
    /**
     * 分析SKU的成本差异
     *
     * @return array{absoluteVariance: float, relativeVariance: float, exceedsThreshold: bool}|null
     */
    public function analyzeVarianceForSku(string $skuId): ?array;

    /**
     * 获取成本趋势分析
     *
     * @return array{periods: int, startCost: float, endCost: float, overallChange: float, averageCost: float, volatility: float}
     */
    public function getCostTrendAnalysis(string $skuId, \DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;

    /**
     * 批量分析多个SKU的成本差异
     *
     * @param string[] $skuIds
     * @return array<string, array<string, mixed>|null>
     */
    public function batchAnalyzeVariance(array $skuIds): array;
}
