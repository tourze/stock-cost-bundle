<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\StockCostBundle\Entity\CostPeriod;
use Tourze\StockCostBundle\Enum\CostPeriodStatus;
use Tourze\StockCostBundle\Enum\CostStrategy;

class CostPeriodFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /** @var CostPeriod $period1 */
        $period1 = new CostPeriod();
        $period1->setPeriodStart(new \DateTimeImmutable('2024-01-01'));
        $period1->setPeriodEnd(new \DateTimeImmutable('2024-01-31'));
        $period1->setDefaultStrategy(CostStrategy::FIFO);
        $period1->setStatus(CostPeriodStatus::CLOSED);

        /** @var CostPeriod $period2 */
        $period2 = new CostPeriod();
        $period2->setPeriodStart(new \DateTimeImmutable('2024-02-01'));
        $period2->setPeriodEnd(new \DateTimeImmutable('2024-02-29'));
        $period2->setDefaultStrategy(CostStrategy::LIFO);
        $period2->setStatus(CostPeriodStatus::OPEN);

        /** @var CostPeriod $period3 */
        $period3 = new CostPeriod();
        $period3->setPeriodStart(new \DateTimeImmutable('2024-03-01'));
        $period3->setPeriodEnd(new \DateTimeImmutable('2024-03-31'));
        $period3->setDefaultStrategy(CostStrategy::WEIGHTED_AVERAGE);
        $period3->setStatus(CostPeriodStatus::OPEN);

        $manager->persist($period1);
        $manager->persist($period2);
        $manager->persist($period3);

        $manager->flush();
    }
}
