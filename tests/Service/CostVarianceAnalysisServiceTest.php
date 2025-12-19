<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Repository\CostRecordRepository;
use Tourze\StockCostBundle\Service\CostVarianceAnalysisService;
use Tourze\StockCostBundle\Service\StandardCostServiceInterface;
use Tourze\StockCostBundle\StockCostBundle;
use Tourze\StockManageBundle\StockManageBundle;

/**
 * @internal
 */
#[CoversClass(CostVarianceAnalysisService::class)]
#[RunTestsInSeparateProcesses]
final class CostVarianceAnalysisServiceTest extends AbstractIntegrationTestCase
{
    private CostRecordRepository $costRecordRepository;

    private CostVarianceAnalysisServiceTestFactory $serviceFactory;

    /**
     * @return array<class-string, array<string, bool>>
     */
    public static function configureBundles(): array
    {
        return [
            FrameworkBundle::class => ['all' => true],
            DoctrineIndexedBundle::class => ['all' => true],
            StockManageBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
            StockCostBundle::class => ['all' => true],
        ];
    }

    protected function onSetUp(): void
    {
        $this->costRecordRepository = self::getService(CostRecordRepository::class);
        $this->serviceFactory = self::getService(CostVarianceAnalysisServiceTestFactory::class);
    }

    public function testAnalyzeVarianceForSku(): void
    {
        // 创建成本记录
        $skuId = 'TEST-SKU-001';
        $this->createCostRecord($skuId, 15.00, 10);
        $this->createCostRecord($skuId, 15.00, 5);

        // 模拟标准成本为 14.00
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);
        $standardCostService->method('getStandardCost')->willReturn(14.00);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $variance = $service->analyzeVarianceForSku($skuId);

        $this->assertIsArray($variance);
        $this->assertEquals(1.00, $variance['absoluteVariance']);
        $this->assertEquals(0.071, round($variance['relativeVariance'], 3));
        $this->assertFalse($variance['exceedsThreshold']);
    }

    public function testAnalyzeVarianceTriggersAlertWhenThresholdExceeded(): void
    {
        // 创建成本记录
        $skuId = 'TEST-SKU-002';
        $this->createCostRecord($skuId, 20.00, 10);

        // 模拟标准成本为 15.00
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);
        $standardCostService->method('getStandardCost')->willReturn(15.00);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $variance = $service->analyzeVarianceForSku($skuId);

        $this->assertIsArray($variance);
        $this->assertEquals(5.00, $variance['absoluteVariance']);
        $this->assertEquals(0.333, round($variance['relativeVariance'], 3));
        $this->assertTrue($variance['exceedsThreshold']);
    }

    public function testAnalyzeVarianceHandlesNegativeVariance(): void
    {
        // 创建成本记录
        $skuId = 'TEST-SKU-003';
        $this->createCostRecord($skuId, 12.00, 10);

        // 模拟标准成本为 15.00
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);
        $standardCostService->method('getStandardCost')->willReturn(15.00);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $variance = $service->analyzeVarianceForSku($skuId);

        $this->assertIsArray($variance);
        $this->assertEquals(-3.00, $variance['absoluteVariance']);
        $this->assertEquals(-0.2, $variance['relativeVariance']);
        $this->assertTrue($variance['exceedsThreshold']);
    }

    public function testAnalyzeVarianceReturnsNullWhenNoStandardCost(): void
    {
        // 创建成本记录
        $skuId = 'TEST-SKU-999';
        $this->createCostRecord($skuId, 10.00, 10);

        // 模拟没有标准成本
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);
        $standardCostService->method('getStandardCost')->willReturn(null);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $variance = $service->analyzeVarianceForSku($skuId);

        $this->assertNull($variance);
    }

    public function testAnalyzeVarianceReturnsNullWhenNoActualCost(): void
    {
        // 不创建任何成本记录
        $skuId = 'TEST-SKU-888';

        // 模拟标准成本服务
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $variance = $service->analyzeVarianceForSku($skuId);

        $this->assertNull($variance);
    }

    public function testGetCostTrendAnalysis(): void
    {
        $skuId = 'TEST-SKU-TREND-001';
        $dates = [
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-02-01'),
            new \DateTimeImmutable('2024-03-01'),
        ];

        // 创建历史成本记录
        $this->createCostRecordWithDate($skuId, 10.00, 10, $dates[0]);
        $this->createCostRecordWithDate($skuId, 12.00, 10, $dates[1]);
        $this->createCostRecordWithDate($skuId, 11.50, 10, $dates[2]);

        // 模拟标准成本服务
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $trend = $service->getCostTrendAnalysis($skuId, $dates[0], $dates[2]);

        $this->assertEquals(3, $trend['periods']);
        $this->assertEquals(10.00, $trend['startCost']);
        $this->assertEquals(11.50, $trend['endCost']);
        $this->assertEquals(0.15, $trend['overallChange']);
        $this->assertEquals(11.17, round($trend['averageCost'], 2));
    }

    public function testGetCostTrendAnalysisCalculatesVolatility(): void
    {
        $skuId = 'TEST-SKU-TREND-002';
        $dates = [
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-02-01'),
        ];

        // 创建历史成本记录
        $this->createCostRecordWithDate($skuId, 10.00, 10, $dates[0]);
        $this->createCostRecordWithDate($skuId, 15.00, 10, $dates[1]);

        // 模拟标准成本服务
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $trend = $service->getCostTrendAnalysis($skuId, $dates[0], $dates[1]);

        $this->assertArrayHasKey('volatility', $trend);
        $this->assertIsFloat($trend['volatility']);
        $this->assertGreaterThan(0, $trend['volatility']);
    }

    public function testBatchAnalyzeVariance(): void
    {
        // 创建两个不同 SKU 的成本记录
        $skuIds = ['TEST-SKU-BATCH-001', 'TEST-SKU-BATCH-002'];
        $this->createCostRecord($skuIds[0], 15.00, 10);
        $this->createCostRecord($skuIds[1], 20.00, 10);

        // 模拟标准成本服务
        $standardCostService = $this->createMock(StandardCostServiceInterface::class);
        $standardCostService->method('getStandardCost')
            ->willReturnMap([
                [$skuIds[0], 14.00],
                [$skuIds[1], 16.00],
            ]);

        // 通过工厂创建服务实例
        $service = $this->serviceFactory->create($standardCostService);

        $results = $service->batchAnalyzeVariance($skuIds);

        $this->assertCount(2, $results);
        $this->assertArrayHasKey($skuIds[0], $results);
        $this->assertArrayHasKey($skuIds[1], $results);
        $this->assertIsArray($results[$skuIds[0]]);
        $this->assertIsArray($results[$skuIds[1]]);
    }

    /**
     * 创建成本记录
     */
    private function createCostRecord(string $skuId, float $unitCost, int $quantity): CostRecord
    {
        return $this->createCostRecordWithDate($skuId, $unitCost, $quantity, new \DateTimeImmutable());
    }

    /**
     * 创建带日期的成本记录
     */
    private function createCostRecordWithDate(
        string $skuId,
        float $unitCost,
        int $quantity,
        \DateTimeImmutable $recordedAt
    ): CostRecord {
        $costRecord = new CostRecord();
        $costRecord->setSkuId($skuId);
        $costRecord->setUnitCost($unitCost);
        $costRecord->setQuantity($quantity);
        $costRecord->setTotalCost($unitCost * $quantity);
        $costRecord->setCostStrategy(CostStrategy::FIFO);
        $costRecord->setCostType(CostType::DIRECT);
        $costRecord->setRecordedAt($recordedAt);

        self::getEntityManager()->persist($costRecord);
        self::getEntityManager()->flush();

        return $costRecord;
    }
}
