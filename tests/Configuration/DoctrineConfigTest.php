<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;

/**
 * @internal
 */
#[CoversClass(CostPeriod::class)]
class DoctrineConfigTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CostPeriod();
    }

    /**
     * @return array<array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            ['periodStart', new \DateTimeImmutable('2024-01-01')],
            ['periodEnd', new \DateTimeImmutable('2024-01-31')],
            ['defaultStrategy', CostStrategy::FIFO],
            ['status', CostPeriodStatus::OPEN],
        ];
    }

    public function testEntityCanBeInstantiated(): void
    {
        $entity = new CostPeriod();
        $this->assertInstanceOf(CostPeriod::class, $entity);
        $this->assertInstanceOf(\Stringable::class, $entity);
    }

    public function testEntityHasCorrectNamespace(): void
    {
        $reflection = new \ReflectionClass(CostPeriod::class);
        $this->assertEquals('Tourze\StockCostBundle\Entity', $reflection->getNamespaceName());
    }

    public function testEntityHasDoctrineAnnotations(): void
    {
        $reflection = new \ReflectionClass(CostPeriod::class);
        $attributes = $reflection->getAttributes();

        $hasEntityAttribute = false;
        $hasTableAttribute = false;

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            if (str_contains($attributeName, 'Entity')) {
                $hasEntityAttribute = true;
            }
            if (str_contains($attributeName, 'Table')) {
                $hasTableAttribute = true;
            }
        }

        $this->assertTrue($hasEntityAttribute, 'Entity should have ORM\Entity attribute');
        $this->assertTrue($hasTableAttribute, 'Entity should have ORM\Table attribute');
    }

    public function testEntitySupportsTimestamps(): void
    {
        $entity = new CostPeriod();

        $this->assertNull($entity->getCreateTime());
        $this->assertNull($entity->getUpdateTime());
        $this->assertNull($entity->getDeleteTime());
    }

    public function testEntitySupportsSoftDelete(): void
    {
        $entity = new CostPeriod();

        $this->assertFalse($entity->isDeleted());

        $entity->softDelete();

        $this->assertTrue($entity->isDeleted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getDeleteTime());
    }
}
