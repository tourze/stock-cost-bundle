<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\StockCostBundle\Entity\StockRecord;

class StockRecordFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var StockRecord $record1 */
        $record1 = new StockRecord();
        $record1->setSku('PRODUCT-A001');
        $record1->setRecordDate(new \DateTimeImmutable('2024-01-15'));
        $record1->setOriginalQuantity(100);
        $record1->setCurrentQuantity(75);
        $record1->setUnitCost(25.50);

        /** @var StockRecord $record2 */
        $record2 = new StockRecord();
        $record2->setSku('PRODUCT-A002');
        $record2->setRecordDate(new \DateTimeImmutable('2024-01-20'));
        $record2->setOriginalQuantity(200);
        $record2->setCurrentQuantity(150);
        $record2->setUnitCost(30.00);

        /** @var StockRecord $record3 */
        $record3 = new StockRecord();
        $record3->setSku('PRODUCT-B001');
        $record3->setRecordDate(new \DateTimeImmutable('2024-01-25'));
        $record3->setOriginalQuantity(50);
        $record3->setCurrentQuantity(0);
        $record3->setUnitCost(45.75);

        $manager->persist($record1);
        $manager->persist($record2);
        $manager->persist($record3);

        $manager->flush();
    }
}
