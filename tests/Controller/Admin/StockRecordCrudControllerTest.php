<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\StockCostBundle\Controller\Admin\StockRecordCrudController;
use Tourze\StockCostBundle\Entity\StockRecord;

/**
 * @internal
 */
#[CoversClass(StockRecordCrudController::class)]
#[RunTestsInSeparateProcesses]
final class StockRecordCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): StockRecordCrudController
    {
        return self::getService(StockRecordCrudController::class);
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): \Generator
    {
        yield 'ID' => ['ID'];
        yield 'SKU编码' => ['SKU编码'];
        yield '记录日期' => ['记录日期'];
        yield '原始库存数量' => ['原始库存数量'];
        yield '当前库存数量' => ['当前库存数量'];
        yield '单位成本' => ['单位成本'];
        yield '总成本' => ['总成本'];
        yield '有库存' => ['有库存'];
        yield '创建时间' => ['创建时间'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'sku' => ['sku'];
        yield 'recordDate' => ['recordDate'];
        yield 'originalQuantity' => ['originalQuantity'];
        yield 'currentQuantity' => ['currentQuantity'];
        yield 'unitCost' => ['unitCost'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideEditPageFields(): \Generator
    {
        yield 'sku' => ['sku'];
        yield 'recordDate' => ['recordDate'];
        yield 'originalQuantity' => ['originalQuantity'];
        yield 'currentQuantity' => ['currentQuantity'];
        yield 'unitCost' => ['unitCost'];
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 获取表单并填入无效数据（负数的库存数量违反Assert\PositiveOrZero约束）
        $form = $crawler->filter('form[name="StockRecord"]')->form();

        $form['StockRecord[sku]'] = 'TEST-SKU-VALID';
        $form['StockRecord[recordDate]'] = '2024-01-15T00:00:00';
        $form['StockRecord[originalQuantity]'] = (string) (-10); // 无效：不能为负数
        $form['StockRecord[currentQuantity]'] = (string) 0;
        $form['StockRecord[unitCost]'] = (string) 10.00;

        // 提交表单
        $crawler = $client->submit($form);

        // 验证返回422状态码（验证失败）
        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateStockRecord(): void
    {
        $entityManager = self::getEntityManager();
        $client = $this->createAuthenticatedClient();

        // 访问新建页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::NEW));
        $this->assertResponseIsSuccessful();

        // 填写表单
        $form = $crawler->filter('form[name="StockRecord"]')->form();
        $form['StockRecord[sku]'] = 'TEST-SKU-STOCK-001';
        $form['StockRecord[recordDate]'] = '2024-01-15T00:00:00';
        $form['StockRecord[originalQuantity]'] = (string) 100;
        $form['StockRecord[currentQuantity]'] = (string) 95;
        $form['StockRecord[unitCost]'] = (string) 25.50;

        // 提交表单
        $client->submit($form);

        // 验证成功重定向（EasyAdmin 默认重定向到 Dashboard）
        $this->assertResponseRedirects();

        // 验证数据库中确实创建了记录
        $stockRecord = $entityManager->getRepository(StockRecord::class)
            ->findOneBy(['sku' => 'TEST-SKU-STOCK-001'])
        ;

        $this->assertInstanceOf(StockRecord::class, $stockRecord);
        $this->assertEquals('TEST-SKU-STOCK-001', $stockRecord->getSku());
        $this->assertInstanceOf(\DateTimeImmutable::class, $stockRecord->getRecordDate());
        $this->assertEquals('2024-01-15', $stockRecord->getRecordDate()->format('Y-m-d'));
        $this->assertEquals(100, $stockRecord->getOriginalQuantity());
        $this->assertEquals(95, $stockRecord->getCurrentQuantity());
        $this->assertEquals(25.50, $stockRecord->getUnitCost());
    }

    public function testEditStockRecord(): void
    {
        // 先创建客户端（这会触发Kernel和Fixture加载）
        $client = $this->createAuthenticatedClient();

        $entityManager = self::getEntityManager();

        // 创建测试数据
        $stockRecord = new StockRecord();
        $stockRecord->setSku('TEST-SKU-STOCK-002');
        $stockRecord->setRecordDate(new \DateTimeImmutable('2024-01-10'));
        $stockRecord->setOriginalQuantity(50);
        $stockRecord->setCurrentQuantity(45);
        $stockRecord->setUnitCost(30.00);

        $entityManager->persist($stockRecord);
        $entityManager->flush();

        // 访问编辑页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::EDIT, ['entityId' => $stockRecord->getId()]));
        $this->assertResponseIsSuccessful();

        // 修改表单
        $form = $crawler->filter('form[name="StockRecord"]')->form();
        $form['StockRecord[currentQuantity]'] = (string) 35;
        $form['StockRecord[unitCost]'] = (string) 32.50;

        // 提交表单
        $client->submit($form);

        // 验证成功重定向（EasyAdmin 默认重定向到 Dashboard）
        $this->assertResponseRedirects();

        // 验证数据库中确实更新了记录
        $entityManager->clear();
        $updatedRecord = $entityManager->getRepository(StockRecord::class)
            ->find($stockRecord->getId())
        ;

        $this->assertInstanceOf(StockRecord::class, $updatedRecord);
        $this->assertEquals(35, $updatedRecord->getCurrentQuantity());
        $this->assertEquals(32.50, $updatedRecord->getUnitCost());
    }

    public function testListStockRecords(): void
    {
        // 先创建客户端（这会触发Kernel和Fixture加载）
        $client = $this->createAuthenticatedClient();

        $entityManager = self::getEntityManager();

        // 清理现有数据（包括fixture），确保测试数据隔离
        $entityManager->createQuery('DELETE FROM ' . StockRecord::class)->execute();

        // 创建测试数据
        $records = [];
        for ($i = 1; $i <= 3; ++$i) {
            $stockRecord = new StockRecord();
            $stockRecord->setSku("TEST-SKU-LIST-{$i}");
            $stockRecord->setRecordDate(new \DateTimeImmutable(sprintf('2024-01-%02d', $i)));
            $stockRecord->setOriginalQuantity(100 * $i);
            $stockRecord->setCurrentQuantity(80 * $i);
            $stockRecord->setUnitCost(15.0 * $i);

            $entityManager->persist($stockRecord);
            $records[] = $stockRecord;
        }
        $entityManager->flush();

        // 访问列表页面
        $crawler = $client->request('GET', $this->generateAdminUrl(Action::INDEX));
        $this->assertResponseIsSuccessful();

        // 验证页面包含我们创建的记录
        foreach ($records as $record) {
            $this->assertStringContainsString($record->getSku(), $crawler->text());
        }

        // 验证表头存在
        $this->assertStringContainsString('ID', $crawler->text());
        $this->assertStringContainsString('SKU编码', $crawler->text());
        $this->assertStringContainsString('记录日期', $crawler->text());
        $this->assertStringContainsString('当前库存数量', $crawler->text());
    }
}
