<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;

/**
 * @internal
 */
#[CoversClass(CostPeriod::class)]
class CostPeriodTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CostPeriod();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'periodStart' => ['periodStart', new \DateTimeImmutable('2024-01-01')],
            'periodEnd' => ['periodEnd', new \DateTimeImmutable('2024-01-31')],
            'defaultStrategy' => ['defaultStrategy', CostStrategy::LIFO],
            'status' => ['status', CostPeriodStatus::CLOSED],
        ];
    }

    public function testEntityHasDefaultValues(): void
    {
        $entity = new CostPeriod();

        $this->assertEquals(CostPeriodStatus::OPEN, $entity->getStatus());
        $this->assertEquals(CostStrategy::FIFO, $entity->getDefaultStrategy());
        $this->assertNull($entity->getCreateTime());
        $this->assertNull($entity->getUpdateTime());
        $this->assertNull($entity->getDeleteTime());
        $this->assertFalse($entity->isDeleted());
    }

    public function testSoftDelete(): void
    {
        $entity = new CostPeriod();

        $this->assertFalse($entity->isDeleted());
        $this->assertNull($entity->getDeleteTime());

        $entity->softDelete();

        $this->assertTrue($entity->isDeleted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getDeleteTime());
    }

    public function testUpdateTimeIsSetOnModifications(): void
    {
        $entity = new CostPeriod();

        $this->assertNull($entity->getUpdateTime());

        $now = new \DateTimeImmutable();
        $entity->setUpdateTime($now);
        $this->assertEquals($now, $entity->getUpdateTime());

        $laterTime = $now->modify('+1 second');
        $entity->setUpdateTime($laterTime);
        $this->assertEquals($laterTime, $entity->getUpdateTime());
    }
}
