<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Enum\CostType;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;
use Tourze\StockCostBundle\Model\CostReportData;

/**
 * @internal
 */
#[CoversClass(CostReportData::class)]
class CostReportDataTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');

        $report = new CostReportData(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            totalCost: 125000.00,
            costByType: [
                CostType::DIRECT->value => 75000.00,
                CostType::INDIRECT->value => 30000.00,
                CostType::MANUFACTURING->value => 20000.00,
            ]
        );

        $this->assertInstanceOf(CostReportData::class, $report);
        $this->assertEquals($periodStart, $report->getPeriodStart());
        $this->assertEquals($periodEnd, $report->getPeriodEnd());
        $this->assertEquals(125000.00, $report->getTotalCost());
        $this->assertEquals(75000.00, $report->getCostByType(CostType::DIRECT));
    }

    public function testCanIncludeSkuDetails(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');

        $skuDetails = [
            'SKU-001' => ['quantity' => 100, 'unitCost' => 15.50, 'totalCost' => 1550.00],
            'SKU-002' => ['quantity' => 200, 'unitCost' => 25.00, 'totalCost' => 5000.00],
        ];

        $report = new CostReportData(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            totalCost: 6550.00,
            costByType: [CostType::DIRECT->value => 6550.00],
            skuDetails: $skuDetails
        );

        $this->assertEquals($skuDetails, $report->getSkuDetails());
    }

    public function testSupportsJsonSerialization(): void
    {
        $periodStart = new \DateTimeImmutable('2024-01-01');
        $periodEnd = new \DateTimeImmutable('2024-01-31');

        $report = new CostReportData(
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            totalCost: 125000.00,
            costByType: [
                CostType::DIRECT->value => 75000.00,
                CostType::INDIRECT->value => 50000.00,
            ]
        );

        $serialized = $report->jsonSerialize();
        $this->assertIsArray($serialized);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $serialized['periodStart']);
        $this->assertEquals('2024-01-31T00:00:00+00:00', $serialized['periodEnd']);
        $this->assertEquals(125000.00, $serialized['totalCost']);
        $this->assertArrayHasKey('DIRECT', $serialized['costByType']);
        $this->assertEquals(75000.00, $serialized['costByType']['DIRECT']);

        $json = json_encode($report);
        $this->assertIsString($json);

        $data = json_decode($json, true);
        $this->assertEquals('2024-01-01T00:00:00+00:00', $data['periodStart']);
        $this->assertEquals('2024-01-31T00:00:00+00:00', $data['periodEnd']);
        $this->assertEquals(125000.00, $data['totalCost']);
        $this->assertArrayHasKey('DIRECT', $data['costByType']);
        $this->assertEquals(75000.00, $data['costByType']['DIRECT']);
    }

    public function testCanBeCreatedFromArray(): void
    {
        $data = [
            'periodStart' => '2024-01-01T00:00:00+00:00',
            'periodEnd' => '2024-01-31T00:00:00+00:00',
            'totalCost' => 125000.00,
            'costByType' => [
                'DIRECT' => 75000.00,
                'INDIRECT' => 50000.00,
            ],
        ];

        $report = CostReportData::fromArray($data);

        $this->assertEquals(new \DateTimeImmutable('2024-01-01'), $report->getPeriodStart());
        $this->assertEquals(new \DateTimeImmutable('2024-01-31'), $report->getPeriodEnd());
        $this->assertEquals(125000.00, $report->getTotalCost());
    }

    public function testValidatesPeriodOrder(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('Period start must be before period end');

        new CostReportData(
            periodStart: new \DateTimeImmutable('2024-01-31'),
            periodEnd: new \DateTimeImmutable('2024-01-01'),
            totalCost: 125000.00,
            costByType: []
        );
    }

    public function testValidatesNegativeCost(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('Total cost cannot be negative');

        new CostReportData(
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            totalCost: -1000.00,
            costByType: []
        );
    }

    public function testPeriodCalculations(): void
    {
        $report = new CostReportData(
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            totalCost: 125000.00,
            costByType: []
        );

        $this->assertEquals(30, $report->getPeriodDays());
        $this->assertEquals('2024-01-01 - 2024-01-31', $report->getFormattedPeriod());
    }

    public function testFormattedTotalCost(): void
    {
        $report = new CostReportData(
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            totalCost: 125000.567,
            costByType: []
        );

        $this->assertEquals('125,000.57', $report->getFormattedTotalCost());
        $this->assertEquals('125,000.6', $report->getFormattedTotalCost(1));
    }

    public function testCostTypePercentage(): void
    {
        $report = new CostReportData(
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            totalCost: 100000.00,
            costByType: [
                CostType::DIRECT->value => 75000.00,
                CostType::INDIRECT->value => 25000.00,
            ]
        );

        $this->assertEquals(75.0, $report->getCostTypePercentage(CostType::DIRECT));
        $this->assertEquals(25.0, $report->getCostTypePercentage(CostType::INDIRECT));
        $this->assertEquals(0.0, $report->getCostTypePercentage(CostType::MANUFACTURING));
    }

    public function testCostTypePercentageWithZeroTotal(): void
    {
        $report = new CostReportData(
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            totalCost: 0.0,
            costByType: []
        );

        $this->assertEquals(0.0, $report->getCostTypePercentage(CostType::DIRECT));
    }

    public function testJsonSerialize(): void
    {
        $report = new CostReportData(
            periodStart: new \DateTimeImmutable('2024-01-01'),
            periodEnd: new \DateTimeImmutable('2024-01-31'),
            totalCost: 125000.00,
            costByType: [
                CostType::DIRECT->value => 75000.00,
                CostType::INDIRECT->value => 50000.00,
            ]
        );

        $serialized = $report->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertArrayHasKey('periodStart', $serialized);
        $this->assertArrayHasKey('periodEnd', $serialized);
        $this->assertArrayHasKey('totalCost', $serialized);
        $this->assertArrayHasKey('costByType', $serialized);
        $this->assertArrayHasKey('skuDetails', $serialized);
    }
}
