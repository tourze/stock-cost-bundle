<?php

declare(strict_types=1);

namespace Tourze\StockCostBundle\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\StockCostBundle\Controller\Admin\CostRecordCrudController;
use Tourze\StockCostBundle\Entity\CostRecord;
use Tourze\StockCostBundle\Enum\CostStrategy;
use Tourze\StockCostBundle\Enum\CostType;

/**
 * @internal
 */
#[CoversClass(CostRecordCrudController::class)]
#[RunTestsInSeparateProcesses]
final class CostRecordCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): CostRecordCrudController
    {
        return self::getService(CostRecordCrudController::class);
    }

    /** @return \Generator<string, array{string}> */
    public static function provideIndexPageHeaders(): \Generator
    {
        yield 'ID' => ['ID'];
        yield 'SKU标识' => ['SKU标识'];
        yield '批次号' => ['批次号'];
        yield '单位成本' => ['单位成本'];
        yield '数量' => ['数量'];
        yield '总成本' => ['总成本'];
        yield '成本策略' => ['成本策略'];
        yield '成本类型' => ['成本类型'];
        yield '记录时间' => ['记录时间'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideNewPageFields(): \Generator
    {
        yield 'skuId' => ['skuId'];
        yield 'batchNo' => ['batchNo'];
        yield 'unitCost' => ['unitCost'];
        yield 'quantity' => ['quantity'];
        yield 'totalCost' => ['totalCost'];
        yield 'costStrategy' => ['costStrategy'];
        yield 'costType' => ['costType'];
        yield 'period' => ['period'];
        yield 'operator' => ['operator'];
        yield 'stockBatch' => ['stockBatch'];
        yield 'metadata' => ['metadata'];
    }

    /** @return \Generator<string, array{string}> */
    public static function provideEditPageFields(): \Generator
    {
        yield 'skuId' => ['skuId'];
        yield 'batchNo' => ['batchNo'];
        yield 'unitCost' => ['unitCost'];
        yield 'quantity' => ['quantity'];
        yield 'totalCost' => ['totalCost'];
        yield 'costStrategy' => ['costStrategy'];
        yield 'costType' => ['costType'];
        yield 'period' => ['period'];
        yield 'operator' => ['operator'];
        yield 'stockBatch' => ['stockBatch'];
        yield 'metadata' => ['metadata'];
    }

    public function testValidationErrors(): void
    {
        $client = static::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        // 访问新建页面
        $crawler = $client->request('GET', '/admin/cost-record/new');
        $this->assertResponseIsSuccessful();

        // 获取表单并清空必填字段
        $form = $crawler->filter('form[name="CostRecord"]')->form();
        $form['CostRecord[skuId]'] = '';
        $form['CostRecord[unitCost]'] = '';
        $form['CostRecord[quantity]'] = '';

        // 提交表单
        $client->submit($form);

        // 验证返回422状态码
        $this->assertResponseStatusCodeSame(422);

        // 验证响应内容包含必填字段错误信息
        $responseContent = $client->getResponse()->getContent();
        $this->assertIsString($responseContent);
        $this->assertStringContainsString('This value should not be blank', $responseContent);
    }

    public function testCreateCostRecord(): void
    {
        $entityManager = self::getEntityManager();

        $client = static::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        // 访问新建页面
        $crawler = $client->request('GET', '/admin/cost-record/new');
        $this->assertResponseIsSuccessful();

        // 填写表单
        $form = $crawler->filter('form[name="CostRecord"]')->form();
        $form['CostRecord[skuId]'] = 'TEST-SKU-001';
        $form['CostRecord[batchNo]'] = 'BATCH-001';
        $form['CostRecord[unitCost]'] = (string) 15.50;
        $form['CostRecord[quantity]'] = (string) 100;
        $form['CostRecord[totalCost]'] = (string) 1550.00;
        $form['CostRecord[costStrategy]'] = CostStrategy::FIFO->value;
        $form['CostRecord[costType]'] = CostType::DIRECT->value;
        $form['CostRecord[operator]'] = 'test_user';

        // 提交表单
        $client->submit($form);

        // 验证重定向到列表页面
        $this->assertResponseRedirects('/admin/cost-record');

        // 验证数据库中确实创建了记录
        $costRecord = $entityManager->getRepository(CostRecord::class)
            ->findOneBy(['skuId' => 'TEST-SKU-001'])
        ;

        $this->assertInstanceOf(CostRecord::class, $costRecord);
        $this->assertEquals('TEST-SKU-001', $costRecord->getSkuId());
        $this->assertEquals('BATCH-001', $costRecord->getBatchNo());
        $this->assertEquals(15.50, $costRecord->getUnitCost());
        $this->assertEquals(100, $costRecord->getQuantity());
        $this->assertEquals(1550.00, $costRecord->getTotalCost());
        $this->assertEquals(CostStrategy::FIFO, $costRecord->getCostStrategy());
        $this->assertEquals(CostType::DIRECT, $costRecord->getCostType());
        $this->assertEquals('test_user', $costRecord->getOperator());
    }

    public function testEditCostRecord(): void
    {
        $entityManager = self::getEntityManager();

        // 创建测试数据
        $costRecord = new CostRecord();
        $costRecord->setSkuId('TEST-SKU-002');
        $costRecord->setBatchNo('BATCH-002');
        $costRecord->setUnitCost(20.00);
        $costRecord->setQuantity(50);
        $costRecord->setTotalCost(1000.00);
        $costRecord->setCostStrategy(CostStrategy::LIFO);
        $costRecord->setCostType(CostType::INDIRECT);
        $costRecord->setOperator('test_user');

        $entityManager->persist($costRecord);
        $entityManager->flush();

        $client = static::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        // 访问编辑页面
        $crawler = $client->request('GET', "/admin/cost-record/{$costRecord->getId()}/edit");
        $this->assertResponseIsSuccessful();

        // 修改表单
        $form = $crawler->filter('form[name="CostRecord"]')->form();
        $form['CostRecord[unitCost]'] = (string) 25.00;
        $form['CostRecord[totalCost]'] = (string) 1250.00;

        // 提交表单
        $client->submit($form);

        // 验证重定向到列表页面
        $this->assertResponseRedirects('/admin/cost-record');

        // 验证数据库中确实更新了记录
        $entityManager->clear();
        $updatedRecord = $entityManager->getRepository(CostRecord::class)
            ->find($costRecord->getId())
        ;

        $this->assertInstanceOf(CostRecord::class, $updatedRecord);
        $this->assertEquals(25.00, $updatedRecord->getUnitCost());
        $this->assertEquals(1250.00, $updatedRecord->getTotalCost());
    }

    public function testDeleteCostRecord(): void
    {
        $entityManager = self::getEntityManager();

        // 创建测试数据
        $costRecord = new CostRecord();
        $costRecord->setSkuId('TEST-SKU-003');
        $costRecord->setBatchNo('BATCH-003');
        $costRecord->setUnitCost(30.00);
        $costRecord->setQuantity(25);
        $costRecord->setTotalCost(750.00);
        $costRecord->setCostStrategy(CostStrategy::WEIGHTED_AVERAGE);
        $costRecord->setCostType(CostType::OVERHEAD);
        $costRecord->setOperator('test_user');

        $entityManager->persist($costRecord);
        $entityManager->flush();

        $recordId = $costRecord->getId();

        $client = static::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        // 访问删除操作（通常通过CSRF Token保护）
        /** @var CsrfTokenManagerInterface $tokenManager */
        $tokenManager = self::getService(CsrfTokenManagerInterface::class);
        $csrfToken = $tokenManager->getToken('delete_cost_record');

        $client->request('POST', "/admin/cost-record/{$recordId}/delete", [
            '_token' => $csrfToken->getValue(),
        ]);

        // 验证重定向到列表页面
        $this->assertResponseRedirects('/admin/cost-record');

        // 验证数据库中确实删除了记录
        $deletedRecord = $entityManager->getRepository(CostRecord::class)
            ->find($recordId)
        ;

        $this->assertNull($deletedRecord);
    }

    public function testListCostRecords(): void
    {
        $entityManager = self::getEntityManager();

        // 创建测试数据
        $records = [];
        for ($i = 1; $i <= 3; ++$i) {
            $costRecord = new CostRecord();
            $costRecord->setSkuId("TEST-SKU-LIST-{$i}");
            $costRecord->setBatchNo("BATCH-LIST-{$i}");
            $costRecord->setUnitCost(10.0 * $i);
            $costRecord->setQuantity(100 * $i);
            $costRecord->setTotalCost(1000.0 * $i);
            $costRecord->setCostStrategy(CostStrategy::FIFO);
            $costRecord->setCostType(CostType::DIRECT);
            $costRecord->setOperator('test_user');

            $entityManager->persist($costRecord);
            $records[] = $costRecord;
        }
        $entityManager->flush();

        $client = static::createClient();
        $client->loginUser(new InMemoryUser('admin', 'password', ['ROLE_ADMIN']));

        // 访问列表页面
        $crawler = $client->request('GET', '/admin/cost-record');
        $this->assertResponseIsSuccessful();

        // 验证页面包含我们创建的记录
        foreach ($records as $record) {
            $this->assertStringContainsString($record->getSkuId(), $crawler->text());
            $batchNo = $record->getBatchNo();
            if (null !== $batchNo) {
                $this->assertStringContainsString($batchNo, $crawler->text());
            }
        }

        // 验证表头存在
        $this->assertStringContainsString('ID', $crawler->text());
        $this->assertStringContainsString('SKU标识', $crawler->text());
        $this->assertStringContainsString('批次号', $crawler->text());
        $this->assertStringContainsString('单位成本', $crawler->text());
    }
}
