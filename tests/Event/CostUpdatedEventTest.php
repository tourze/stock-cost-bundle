<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use Tourze\StockCostBundle\Event\CostUpdatedEvent;

/**
 * @internal
 */
#[CoversClass(CostUpdatedEvent::class)]
class CostUpdatedEventTest extends AbstractEventTestCase
{
    public function testEventCanBeInstantiated(): void
    {
        $event = new CostUpdatedEvent(
            'SKU-001',
            100,
            15.50,
            1550.00,
            12.00,
            1200.00
        );

        $this->assertInstanceOf(CostUpdatedEvent::class, $event);
    }

    public function testEventHasRequiredGetters(): void
    {
        $event = new CostUpdatedEvent(
            'SKU-001',
            100,
            15.50,
            1550.00,
            12.00,
            1200.00
        );

        $this->assertEquals('SKU-001', $event->getSku());
        $this->assertEquals(100, $event->getQuantity());
        $this->assertEquals(15.50, $event->getNewUnitCost());
        $this->assertEquals(1550.00, $event->getNewTotalCost());
        $this->assertEquals(12.00, $event->getOldUnitCost());
        $this->assertEquals(1200.00, $event->getOldTotalCost());
    }

    public function testEventHasCostDifferenceMethods(): void
    {
        $event = new CostUpdatedEvent(
            'SKU-001',
            100,
            15.50,
            1550.00,
            12.00,
            1200.00
        );

        $this->assertEquals(3.50, $event->getUnitCostDifference());
        $this->assertEquals(350.00, $event->getTotalCostDifference());
        $this->assertTrue($event->isCostIncreased());
        $this->assertFalse($event->isCostDecreased());
    }

    public function testEventWithDecreasedCost(): void
    {
        $event = new CostUpdatedEvent(
            'SKU-002',
            50,
            10.00,
            500.00,
            12.00,
            600.00
        );

        $this->assertEquals(-2.00, $event->getUnitCostDifference());
        $this->assertEquals(-100.00, $event->getTotalCostDifference());
        $this->assertFalse($event->isCostIncreased());
        $this->assertTrue($event->isCostDecreased());
    }

    public function testEventWithSameCost(): void
    {
        $event = new CostUpdatedEvent(
            'SKU-003',
            25,
            20.00,
            500.00,
            20.00,
            500.00
        );

        $this->assertEquals(0.00, $event->getUnitCostDifference());
        $this->assertEquals(0.00, $event->getTotalCostDifference());
        $this->assertFalse($event->isCostIncreased());
        $this->assertFalse($event->isCostDecreased());
    }

    public function testEventHasTimestamp(): void
    {
        $event = new CostUpdatedEvent(
            'SKU-001',
            100,
            15.50,
            1550.00,
            12.00,
            1200.00
        );

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());
        $this->assertEqualsWithDelta(time(), $event->getOccurredAt()->getTimestamp(), 2);
    }

    public function testToArray(): void
    {
        $event = new CostUpdatedEvent(
            'SKU-001',
            100,
            15.50,
            1550.00,
            12.00,
            1200.00
        );

        $array = $event->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('SKU-001', $array['sku']);
        $this->assertEquals(100, $array['quantity']);
        $this->assertEquals(15.50, $array['newUnitCost']);
        $this->assertEquals(1550.00, $array['newTotalCost']);
        $this->assertEquals(12.00, $array['oldUnitCost']);
        $this->assertEquals(1200.00, $array['oldTotalCost']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $array['occurredAt']);
    }
}
