<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Exception\InvalidCostDataException;
use Tourze\StockCostBundle\Model\CostCalculationResult;

/**
 * @internal
 */
#[CoversClass(CostCalculationResult::class)]
class CostCalculationResultTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $result = new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO
        );

        $this->assertInstanceOf(CostCalculationResult::class, $result);
        $this->assertEquals('TEST-SKU-001', $result->getSku());
        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(15.50, $result->getUnitCost());
        $this->assertEquals(1550.00, $result->getTotalCost());
        $this->assertEquals(CostStrategy::FIFO, $result->getStrategy());
    }

    public function testCanIncludeCalculationDetails(): void
    {
        $details = [
            'batch_1' => ['quantity' => 50, 'unit_cost' => 15.00],
            'batch_2' => ['quantity' => 50, 'unit_cost' => 16.00],
        ];

        $result = new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO,
            calculationDetails: $details
        );

        $this->assertEquals($details, $result->getCalculationDetails());
    }

    public function testSupportsJsonSerialization(): void
    {
        $result = new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO
        );

        $serialized = $result->jsonSerialize();
        $this->assertIsArray($serialized);
        $this->assertEquals('TEST-SKU-001', $serialized['sku']);
        $this->assertEquals(100, $serialized['quantity']);
        $this->assertEquals(15.50, $serialized['unitCost']);
        $this->assertEquals(1550.00, $serialized['totalCost']);
        $this->assertEquals('FIFO', $serialized['strategy']);

        $json = json_encode($result);
        $this->assertIsString($json);

        $data = json_decode($json, true);
        $this->assertEquals('TEST-SKU-001', $data['sku']);
        $this->assertEquals(100, $data['quantity']);
        $this->assertEquals(15.50, $data['unitCost']);
        $this->assertEquals(1550.00, $data['totalCost']);
        $this->assertEquals('FIFO', $data['strategy']);
    }

    public function testCanBeCreatedFromArray(): void
    {
        $data = [
            'sku' => 'TEST-SKU-001',
            'quantity' => 100,
            'unitCost' => 15.50,
            'totalCost' => 1550.00,
            'strategy' => 'FIFO',
        ];

        $result = CostCalculationResult::fromArray($data);

        $this->assertEquals('TEST-SKU-001', $result->getSku());
        $this->assertEquals(100, $result->getQuantity());
        $this->assertEquals(CostStrategy::FIFO, $result->getStrategy());
    }

    public function testValidatesRequiredFields(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('SKU cannot be empty');

        new CostCalculationResult(
            sku: '',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO
        );
    }

    public function testValidatesNegativeQuantity(): void
    {
        $this->expectException(InvalidCostDataException::class);
        $this->expectExceptionMessage('Quantity must be positive');

        new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: -10,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO
        );
    }

    public function testHasCalculationDetails(): void
    {
        $resultWithDetails = new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO,
            calculationDetails: ['batch_1' => ['quantity' => 100]]
        );

        $resultWithoutDetails = new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO
        );

        $this->assertTrue($resultWithDetails->hasCalculationDetails());
        $this->assertFalse($resultWithoutDetails->hasCalculationDetails());
    }

    public function testFormattedCosts(): void
    {
        $result = new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: 100,
            unitCost: 15.567,
            totalCost: 1556.70,
            strategy: CostStrategy::FIFO
        );

        $this->assertEquals('15.57', $result->getFormattedUnitCost());
        $this->assertEquals('1,556.70', $result->getFormattedTotalCost());
        $this->assertEquals('15.6', $result->getFormattedUnitCost(1));
    }

    public function testJsonSerialize(): void
    {
        $result = new CostCalculationResult(
            sku: 'TEST-SKU-001',
            quantity: 100,
            unitCost: 15.50,
            totalCost: 1550.00,
            strategy: CostStrategy::FIFO
        );

        $serialized = $result->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertArrayHasKey('sku', $serialized);
        $this->assertArrayHasKey('quantity', $serialized);
        $this->assertArrayHasKey('unitCost', $serialized);
        $this->assertArrayHasKey('totalCost', $serialized);
        $this->assertArrayHasKey('strategy', $serialized);
        $this->assertArrayHasKey('calculationDetails', $serialized);
    }
}
