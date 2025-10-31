<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;

class CostRecordFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var CostRecord $record1 */
        $record1 = new CostRecord();
        $record1->setSkuId('SKU001');
        $record1->setBatchNo('BATCH-001');
        $record1->setQuantity(100);
        $record1->setUnitCost(25.50);
        $record1->setTotalCost(2550.00);
        $record1->setCostStrategy(CostStrategy::FIFO);

        /** @var CostRecord $record2 */
        $record2 = new CostRecord();
        $record2->setSkuId('SKU002');
        $record2->setBatchNo('BATCH-002');
        $record2->setQuantity(200);
        $record2->setUnitCost(30.00);
        $record2->setTotalCost(6000.00);
        $record2->setCostStrategy(CostStrategy::LIFO);

        $manager->persist($record1);
        $manager->persist($record2);

        $manager->flush();
    }
}
