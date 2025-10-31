<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\StockCostBundle\Entity\CostAllocation;
use Tourze\StockCostBundle\Enum\AllocationMethod;
use Tourze\StockCostBundle\Enum\CostType;

class CostAllocationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var CostAllocation $allocation1 */
        $allocation1 = new CostAllocation();
        $allocation1->setAllocationName('Manufacturing Overhead Allocation');
        $allocation1->setSourceType(CostType::OVERHEAD);
        $allocation1->setTotalAmount(5000.00);
        $allocation1->setAllocationMethod(AllocationMethod::RATIO);
        $allocation1->setAllocationDate(new \DateTimeImmutable('2024-01-31'));
        $allocation1->setTargets([
            ['sku_id' => 'SKU001', 'ratio' => 0.4],
            ['sku_id' => 'SKU002', 'ratio' => 0.6],
        ]);

        /** @var CostAllocation $allocation2 */
        $allocation2 = new CostAllocation();
        $allocation2->setAllocationName('Labor Cost Distribution');
        $allocation2->setSourceType(CostType::LABOR);
        $allocation2->setTotalAmount(12000.00);
        $allocation2->setAllocationMethod(AllocationMethod::QUANTITY);
        $allocation2->setAllocationDate(new \DateTimeImmutable('2024-02-28'));
        $allocation2->setTargets([
            ['sku_id' => 'SKU003', 'quantity' => 100],
            ['sku_id' => 'SKU004', 'quantity' => 50],
        ]);

        $manager->persist($allocation1);
        $manager->persist($allocation2);

        $manager->flush();
    }
}
