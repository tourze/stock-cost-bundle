<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\StockCostBundle\Service\StandardCostService;
use Tourze\StockCostBundle\Service\StandardCostServiceInterface;

/**
 * @internal
 */
#[CoversClass(StandardCostService::class)]
class StandardCostServiceTest extends TestCase
{
    private StandardCostService $service;

    protected function setUp(): void
    {
        $this->service = new StandardCostService();
    }

    public function testImplementsInterface(): void
    {
        $this->assertInstanceOf(StandardCostServiceInterface::class, $this->service);
    }

    public function testSetAndGetStandardCost(): void
    {
        $this->service->setStandardCost('SKU-001', 15.50);

        $cost = $this->service->getStandardCost('SKU-001');

        $this->assertEquals(15.50, $cost);
    }

    public function testGetStandardCostReturnsNullForUnsetSku(): void
    {
        $cost = $this->service->getStandardCost('NONEXISTENT-SKU');

        $this->assertNull($cost);
    }

    public function testSetStandardCostWithEffectiveDate(): void
    {
        $effectiveDate = new \DateTimeImmutable('2024-01-01');

        $this->service->setStandardCost('SKU-002', 20.00, $effectiveDate);

        $cost = $this->service->getStandardCost('SKU-002');

        $this->assertEquals(20.00, $cost);
    }

    public function testHasStandardCost(): void
    {
        $this->assertFalse($this->service->hasStandardCost('SKU-003'));

        $this->service->setStandardCost('SKU-003', 25.00);

        $this->assertTrue($this->service->hasStandardCost('SKU-003'));
    }

    public function testGetBatchStandardCosts(): void
    {
        $this->service->setStandardCost('SKU-001', 10.00);
        $this->service->setStandardCost('SKU-002', 20.00);
        $this->service->setStandardCost('SKU-003', 30.00);

        $costs = $this->service->getBatchStandardCosts(['SKU-001', 'SKU-002', 'SKU-004']);

        $expected = [
            'SKU-001' => 10.00,
            'SKU-002' => 20.00,
        ];

        $this->assertEquals($expected, $costs);
    }

    public function testGetBatchStandardCostsWithEmptyArray(): void
    {
        $costs = $this->service->getBatchStandardCosts([]);

        $this->assertEquals([], $costs);
    }

    public function testRemoveStandardCost(): void
    {
        $this->service->setStandardCost('SKU-004', 35.00);
        $this->assertTrue($this->service->hasStandardCost('SKU-004'));

        $this->service->removeStandardCost('SKU-004');

        $this->assertFalse($this->service->hasStandardCost('SKU-004'));
        $this->assertNull($this->service->getStandardCost('SKU-004'));
    }

    public function testRemoveNonExistentStandardCost(): void
    {
        // Should not throw exception
        $this->service->removeStandardCost('NONEXISTENT-SKU');

        $this->assertFalse($this->service->hasStandardCost('NONEXISTENT-SKU'));
    }

    public function testGetAllConfiguredSkus(): void
    {
        $this->service->setStandardCost('SKU-A', 10.00);
        $this->service->setStandardCost('SKU-B', 20.00);
        $this->service->setStandardCost('SKU-C', 30.00);

        $skus = $this->service->getAllConfiguredSkus();

        $this->assertCount(3, $skus);
        $this->assertContains('SKU-A', $skus);
        $this->assertContains('SKU-B', $skus);
        $this->assertContains('SKU-C', $skus);
    }

    public function testGetAllConfiguredSkusReturnsEmptyArrayWhenNone(): void
    {
        $skus = $this->service->getAllConfiguredSkus();

        $this->assertEquals([], $skus);
    }

    public function testUpdateExistingStandardCost(): void
    {
        $this->service->setStandardCost('SKU-005', 40.00);
        $this->assertEquals(40.00, $this->service->getStandardCost('SKU-005'));

        $this->service->setStandardCost('SKU-005', 50.00);
        $this->assertEquals(50.00, $this->service->getStandardCost('SKU-005'));
    }

    public function testSetZeroStandardCost(): void
    {
        $this->service->setStandardCost('SKU-ZERO', 0.00);

        $this->assertTrue($this->service->hasStandardCost('SKU-ZERO'));
        $this->assertEquals(0.00, $this->service->getStandardCost('SKU-ZERO'));
    }

    public function testSetNegativeStandardCostIsAllowed(): void
    {
        // Some business cases may require negative standard costs (adjustments, returns, etc.)
        $this->service->setStandardCost('SKU-NEG', -5.00);

        $this->assertTrue($this->service->hasStandardCost('SKU-NEG'));
        $this->assertEquals(-5.00, $this->service->getStandardCost('SKU-NEG'));
    }
}
