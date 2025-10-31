<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
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

    private EntityManagerInterface $entityManager;

    protected function onSetUp(): void
    {
        $this->entityManager = self::getEntityManager();
        // 规避基类 bug：AbstractWebTestCase::createAuthenticatedClient() 未正确注册客户端
        static::createClient();
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
        $client = self::createClient();
        $client->loginUser($this->createAdminUser('admin', 'password'));

        // 访问新建页面
        $crawler = $client->request('GET', '/admin/stock-record/new');
        $this->assertResponseIsSuccessful();

        // 获取表单并清空必填字段
        $form = $crawler->filter('form[name="StockRecord"]')->form();
        $form['StockRecord[sku]'] = '';
        $form['StockRecord[originalQuantity]'] = '';
        $form['StockRecord[currentQuantity]'] = '';

        // 提交表单
        $client->submit($form);

        // 验证返回422状态码
        $this->assertResponseStatusCodeSame(422);

        // 验证响应内容包含必填字段错误信息
        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent);
        $this->assertStringContainsString('This value should not be blank', $responseContent);
    }

    public function testCreateStockRecord(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createAdminUser('admin', 'password'));

        // 访问新建页面
        $crawler = $client->request('GET', '/admin/stock-record/new');
        $this->assertResponseIsSuccessful();

        // 填写表单
        $form = $crawler->filter('form[name="StockRecord"]')->form();
        $form['StockRecord[sku]'] = 'TEST-SKU-STOCK-001';
        $form['StockRecord[recordDate]'] = '2024-01-15';
        $form['StockRecord[originalQuantity]'] = (string) 100;
        $form['StockRecord[currentQuantity]'] = (string) 95;
        $form['StockRecord[unitCost]'] = (string) 25.50;

        // 提交表单
        $client->submit($form);

        // 验证重定向到列表页面
        $this->assertResponseRedirects('/admin/stock-record');

        // 验证数据库中确实创建了记录
        $stockRecord = $this->entityManager->getRepository(StockRecord::class)
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
        // 创建测试数据
        $stockRecord = new StockRecord();
        $stockRecord->setSku('TEST-SKU-STOCK-002');
        $stockRecord->setRecordDate(new \DateTimeImmutable('2024-01-10'));
        $stockRecord->setOriginalQuantity(50);
        $stockRecord->setCurrentQuantity(45);
        $stockRecord->setUnitCost(30.00);

        $this->entityManager->persist($stockRecord);
        $this->entityManager->flush();

        $client = self::createClient();
        $client->loginUser($this->createAdminUser('admin', 'password'));

        // 访问编辑页面
        $crawler = $client->request('GET', "/admin/stock-record/{$stockRecord->getId()}/edit");
        $this->assertResponseIsSuccessful();

        // 修改表单
        $form = $crawler->filter('form[name="StockRecord"]')->form();
        $form['StockRecord[currentQuantity]'] = (string) 35;
        $form['StockRecord[unitCost]'] = (string) 32.50;

        // 提交表单
        $client->submit($form);

        // 验证重定向到列表页面
        $this->assertResponseRedirects('/admin/stock-record');

        // 验证数据库中确实更新了记录
        $this->entityManager->clear();
        $updatedRecord = $this->entityManager->getRepository(StockRecord::class)
            ->find($stockRecord->getId())
        ;

        $this->assertInstanceOf(StockRecord::class, $updatedRecord);
        $this->assertEquals(35, $updatedRecord->getCurrentQuantity());
        $this->assertEquals(32.50, $updatedRecord->getUnitCost());
    }

    public function testListStockRecords(): void
    {
        // 创建测试数据
        $records = [];
        for ($i = 1; $i <= 3; ++$i) {
            $stockRecord = new StockRecord();
            $stockRecord->setSku("TEST-SKU-LIST-{$i}");
            $stockRecord->setRecordDate(new \DateTimeImmutable(sprintf('2024-01-%02d', $i)));
            $stockRecord->setOriginalQuantity(100 * $i);
            $stockRecord->setCurrentQuantity(80 * $i);
            $stockRecord->setUnitCost(15.0 * $i);

            $this->entityManager->persist($stockRecord);
            $records[] = $stockRecord;
        }
        $this->entityManager->flush();

        $client = self::createClient();
        $client->loginUser($this->createAdminUser('admin', 'password'));

        // 访问列表页面
        $crawler = $client->request('GET', '/admin/stock-record');
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
