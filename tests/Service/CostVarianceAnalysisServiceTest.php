<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Event\CostVarianceExceededEvent;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Service\CostVarianceAnalysisService;
use Tourze\StockCostBundle\Service\StandardCostServiceInterface;

/**
 * @internal
 */
#[CoversClass(CostVarianceAnalysisService::class)]
class CostVarianceAnalysisServiceTest extends TestCase
{
    private CostVarianceAnalysisService $service;

    private CostRecordRepository $costRecordRepository;

    private StandardCostServiceInterface $standardCostService;

    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->costRecordRepository = $this->createMock(CostRecordRepository::class);
        $this->standardCostService = $this->createMock(StandardCostServiceInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new CostVarianceAnalysisService(
            $this->costRecordRepository,
            $this->standardCostService,
            $this->eventDispatcher,
            0.1 // 10% 差异阈值
        );
    }

    public function testAnalyzeVarianceForSku(): void
    {
        // 设置模拟数据
        $this->costRecordRepository->expects($this->once())
            ->method('getAverageActualCost')
            ->with('SKU-001')
            ->willReturn(15.00)
        ;

        $this->standardCostService->expects($this->once())
            ->method('getStandardCost')
            ->with('SKU-001')
            ->willReturn(14.00)
        ;

        // 不应触发警报（差异在阈值内）
        $this->eventDispatcher->expects($this->never())
            ->method('dispatch')
        ;

        $variance = $this->service->analyzeVarianceForSku('SKU-001');

        $this->assertIsArray($variance);
        $this->assertEquals(1.00, $variance['absoluteVariance']);
        $this->assertEquals(0.071, round($variance['relativeVariance'], 3)); // (15.00-14.00)/14.00
        $this->assertFalse($variance['exceedsThreshold']);
    }

    public function testAnalyzeVarianceTriggersAlertWhenThresholdExceeded(): void
    {
        // 设置模拟数据 - 差异超过10%阈值
        $this->costRecordRepository->expects($this->once())
            ->method('getAverageActualCost')
            ->with('SKU-002')
            ->willReturn(20.00)
        ;

        $this->standardCostService->expects($this->once())
            ->method('getStandardCost')
            ->with('SKU-002')
            ->willReturn(15.00)
        ;

        // 应该触发警报
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(static::isInstanceOf(CostVarianceExceededEvent::class))
        ;

        $variance = $this->service->analyzeVarianceForSku('SKU-002');

        $this->assertIsArray($variance);
        $this->assertEquals(5.00, $variance['absoluteVariance']);
        $this->assertEquals(0.333, round($variance['relativeVariance'], 3)); // (20-15)/15
        $this->assertTrue($variance['exceedsThreshold']);
    }

    public function testAnalyzeVarianceHandlesNegativeVariance(): void
    {
        // 实际成本低于标准成本
        $this->costRecordRepository->expects($this->once())
            ->method('getAverageActualCost')
            ->with('SKU-003')
            ->willReturn(12.00)
        ;

        $this->standardCostService->expects($this->once())
            ->method('getStandardCost')
            ->with('SKU-003')
            ->willReturn(15.00)
        ;

        $variance = $this->service->analyzeVarianceForSku('SKU-003');

        $this->assertIsArray($variance);
        $this->assertEquals(-3.00, $variance['absoluteVariance']);
        $this->assertEquals(-0.2, $variance['relativeVariance']); // (12-15)/15
        $this->assertTrue($variance['exceedsThreshold']); // 20%差异超过10%阈值
    }

    public function testAnalyzeVarianceReturnsNullWhenNoStandardCost(): void
    {
        $this->costRecordRepository->expects($this->once())
            ->method('getAverageActualCost')
            ->with('SKU-999')
            ->willReturn(10.00)
        ;

        $this->standardCostService->expects($this->once())
            ->method('getStandardCost')
            ->with('SKU-999')
            ->willReturn(null)
        ;

        $variance = $this->service->analyzeVarianceForSku('SKU-999');

        $this->assertNull($variance);
    }

    public function testAnalyzeVarianceReturnsNullWhenNoActualCost(): void
    {
        $this->costRecordRepository->expects($this->once())
            ->method('getAverageActualCost')
            ->with('SKU-888')
            ->willReturn(null)
        ;

        // 不应调用标准成本查询
        $this->standardCostService->expects($this->never())
            ->method('getStandardCost')
        ;

        $variance = $this->service->analyzeVarianceForSku('SKU-888');

        $this->assertNull($variance);
    }

    public function testGetCostTrendAnalysis(): void
    {
        $dates = [
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-02-01'),
            new \DateTimeImmutable('2024-03-01'),
        ];

        $costs = [10.00, 12.00, 11.50];

        $this->costRecordRepository->expects($this->once())
            ->method('getCostHistoryForSku')
            ->with('SKU-001', $dates[0], $dates[2])
            ->willReturn([
                ['date' => '2024-01-01', 'avgCost' => 10.00],
                ['date' => '2024-02-01', 'avgCost' => 12.00],
                ['date' => '2024-03-01', 'avgCost' => 11.50],
            ])
        ;

        $trend = $this->service->getCostTrendAnalysis('SKU-001', $dates[0], $dates[2]);

        $this->assertEquals(3, $trend['periods']);
        $this->assertEquals(10.00, $trend['startCost']);
        $this->assertEquals(11.50, $trend['endCost']);
        $this->assertEquals(0.15, $trend['overallChange']); // (11.50-10.00)/10.00
        $this->assertEquals(11.17, round($trend['averageCost'], 2));
    }

    public function testGetCostTrendAnalysisCalculatesVolatility(): void
    {
        $dates = [
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-02-01'),
        ];

        $this->costRecordRepository->expects($this->once())
            ->method('getCostHistoryForSku')
            ->with('SKU-001', $dates[0], $dates[1])
            ->willReturn([
                ['date' => '2024-01-01', 'avgCost' => 10.00],
                ['date' => '2024-02-01', 'avgCost' => 15.00],
            ])
        ;

        $trend = $this->service->getCostTrendAnalysis('SKU-001', $dates[0], $dates[1]);

        $this->assertArrayHasKey('volatility', $trend);
        $this->assertIsFloat($trend['volatility']);
        $this->assertGreaterThan(0, $trend['volatility']); // 应该有波动率
    }

    public function testBatchAnalyzeVariance(): void
    {
        $skuIds = ['SKU-001', 'SKU-002'];

        $this->costRecordRepository->expects($this->exactly(2))
            ->method('getAverageActualCost')
            ->willReturnOnConsecutiveCalls(15.00, 20.00)
        ;

        $this->standardCostService->expects($this->exactly(2))
            ->method('getStandardCost')
            ->willReturnOnConsecutiveCalls(14.00, 16.00)
        ;

        $results = $this->service->batchAnalyzeVariance($skuIds);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey('SKU-001', $results);
        $this->assertArrayHasKey('SKU-002', $results);
        $this->assertIsArray($results['SKU-001']);
        $this->assertIsArray($results['SKU-002']);
    }
}
