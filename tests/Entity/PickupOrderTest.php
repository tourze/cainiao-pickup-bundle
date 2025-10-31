<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(PickupOrder::class)]
final class PickupOrderTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new PickupOrder();
    }

    /**
     * 提供属性及其样本值的 Data Provider.
     *
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'orderCode' => ['orderCode', 'TEST123456'];
        yield 'weight' => ['weight', 1.5];
        yield 'itemType' => ['itemType', ItemTypeEnum::DOCUMENT];
        yield 'status' => ['status', OrderStatusEnum::CREATE];
        yield 'externalUserId' => ['externalUserId', 'user123'];
        yield 'remark' => ['remark', '测试备注'];
        yield 'expectPickupTimeStart' => ['expectPickupTimeStart', '2023-08-01 09:00:00'];
        yield 'expectPickupTimeEnd' => ['expectPickupTimeEnd', '2023-08-01 18:00:00'];
        yield 'cainiaoOrderCode' => ['cainiaoOrderCode', 'CN123456'];
        yield 'mailNo' => ['mailNo', 'SF123456789'];
        yield 'itemQuantity' => ['itemQuantity', '2'];
        yield 'itemValue' => ['itemValue', 100.00];
        yield 'courierName' => ['courierName', '张三'];
        yield 'courierPhone' => ['courierPhone', '13800138000'];
        yield 'cpCode' => ['cpCode', 'SF'];
        yield 'cpName' => ['cpName', '顺丰速运'];
        yield 'pickupTime' => ['pickupTime', new \DateTimeImmutable('2023-08-01 10:00:00')];
        yield 'lastUpdateTime' => ['lastUpdateTime', new \DateTimeImmutable('2023-08-01 10:00:00')];
        yield 'cancelReason' => ['cancelReason', '用户取消'];
        yield 'cancelTime' => ['cancelTime', new \DateTimeImmutable('2023-08-01 10:00:00')];
        yield 'externalUserMobile' => ['externalUserMobile', '13888888888'];
    }

    /**
     * 测试关联关系
     */
    public function testRelationships(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(PickupOrder::class, $entity);

        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人');
        $senderInfo->setMobile('13800138000');
        $senderInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人');
        $receiverInfo->setMobile('13900139000');
        $receiverInfo->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('app_key');
        $config->setAppSecret('app_secret');

        $entity->setSenderInfo($senderInfo);
        $entity->setReceiverInfo($receiverInfo);
        $entity->setConfig($config);

        $this->assertSame($senderInfo, $entity->getSenderInfo());
        $this->assertSame($receiverInfo, $entity->getReceiverInfo());
        $this->assertSame($config, $entity->getConfig());
    }

    /**
     * 测试 __toString 方法
     */
    public function testToString(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(PickupOrder::class, $entity);
        $entity->setOrderCode('TEST123456');
        $entity->setStatus(OrderStatusEnum::CREATE);

        $this->assertSame('TEST123456 (0)', (string) $entity);
    }

    /**
     * 测试 toPreQueryApiFormat 方法
     */
    public function testToPreQueryApiFormatReturnsCorrectFormat(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(PickupOrder::class, $entity);

        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人');
        $senderInfo->setMobile('13800138000');
        $senderInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人');
        $receiverInfo->setMobile('13900139000');
        $receiverInfo->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('app_key');
        $config->setAppSecret('app_secret');

        $entity->setSenderInfo($senderInfo);
        $entity->setReceiverInfo($receiverInfo);
        $entity->setConfig($config);
        $entity->setExternalUserId('user123');

        $result = $entity->toPreQueryApiFormat();
        $this->assertArrayHasKey('queryCondition', $result);

        /** @var array<string, mixed> $queryCondition */
        $queryCondition = $result['queryCondition'];
        $this->assertIsArray($queryCondition);

        $this->assertArrayHasKey('senderInfo', $queryCondition);
        $this->assertArrayHasKey('receiverInfo', $queryCondition);
        $this->assertArrayHasKey('itemCodeList', $queryCondition);
        $this->assertArrayHasKey('externalUserId', $queryCondition);

        /** @var array<string, mixed> $senderInfo */
        $senderInfo = $queryCondition['senderInfo'];
        $this->assertIsArray($senderInfo);
        $this->assertSame('发件人', $senderInfo['name']);
        $this->assertSame('13800138000', $senderInfo['mobile']);
        $this->assertSame('北京市朝阳区三里屯街道10号', $senderInfo['fullAddressDetail']);
    }

    /**
     * 测试 toCreateOrderApiFormat 方法
     */
    public function testToCreateOrderApiFormatReturnsCorrectFormat(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(PickupOrder::class, $entity);

        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人');
        $senderInfo->setMobile('13800138000');
        $senderInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人');
        $receiverInfo->setMobile('13900139000');
        $receiverInfo->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('app_key');
        $config->setAppSecret('app_secret');

        $entity->setSenderInfo($senderInfo);
        $entity->setReceiverInfo($receiverInfo);
        $entity->setConfig($config);
        $entity->setOrderCode('TEST123456');
        $entity->setItemType(ItemTypeEnum::DOCUMENT);
        $entity->setWeight(1.5);
        $entity->setItemQuantity('2');
        $entity->setItemValue(100.00);
        $entity->setExternalUserId('user123');
        $entity->setExternalUserMobile('13888888888');

        $result = $entity->toCreateOrderApiFormat();
        $this->assertArrayHasKey('senderInfo', $result);
        $this->assertArrayHasKey('receiverInfo', $result);
        $this->assertArrayHasKey('itemType', $result);
        $this->assertArrayHasKey('weight', $result);
        $this->assertArrayHasKey('externalUserId', $result);
        $this->assertArrayHasKey('externalUserMobile', $result);
        $this->assertArrayHasKey('outOrderInfoList', $result);

        /** @var array<string, mixed> $senderInfo */
        $senderInfo = $result['senderInfo'];
        $this->assertIsArray($senderInfo);

        /** @var array<string, mixed> $receiverInfo */
        $receiverInfo = $result['receiverInfo'];
        $this->assertIsArray($receiverInfo);

        /** @var array<mixed> $outOrderInfoList */
        $outOrderInfoList = $result['outOrderInfoList'];
        $this->assertIsArray($outOrderInfoList);

        $this->assertSame('发件人', $senderInfo['name']);
        $this->assertSame('收件人', $receiverInfo['name']);
        $this->assertSame(2, $result['itemType']);
        $this->assertSame(1.5, $result['weight']);
        $this->assertSame('user123', $result['externalUserId']);
        $this->assertSame('13888888888', $result['externalUserMobile']);

        /** @var array<string, mixed> $firstOrderInfo */
        $firstOrderInfo = $outOrderInfoList[0];
        $this->assertIsArray($firstOrderInfo);
        $this->assertSame('document', $firstOrderInfo['itemType']);
    }

    /**
     * 测试 toModifyOrderApiFormat 方法
     */
    public function testToModifyOrderApiFormatReturnsCorrectFormat(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(PickupOrder::class, $entity);

        $senderInfo = new AddressInfo();
        $senderInfo->setName('发件人');
        $senderInfo->setMobile('13800138000');
        $senderInfo->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $receiverInfo = new AddressInfo();
        $receiverInfo->setName('收件人');
        $receiverInfo->setMobile('13900139000');
        $receiverInfo->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('app_key');
        $config->setAppSecret('app_secret');

        $entity->setSenderInfo($senderInfo);
        $entity->setReceiverInfo($receiverInfo);
        $entity->setConfig($config);
        $entity->setCainiaoOrderCode('CN123456');
        $entity->setExternalUserId('user123');

        $result = $entity->toModifyOrderApiFormat();
        $this->assertArrayHasKey('orderId', $result);
        $this->assertArrayHasKey('cnAccountId', $result);
        $this->assertArrayHasKey('senderInfo', $result);
        $this->assertArrayHasKey('receiverInfo', $result);

        $this->assertSame('CN123456', $result['orderId']);
        $this->assertSame('app_key', $result['cnAccountId']);

        /** @var array<string, mixed> $senderInfo */
        $senderInfo = $result['senderInfo'];
        $this->assertIsArray($senderInfo);

        /** @var array<string, mixed> $receiverInfo */
        $receiverInfo = $result['receiverInfo'];
        $this->assertIsArray($receiverInfo);

        $this->assertSame('发件人', $senderInfo['name']);
        $this->assertSame('收件人', $receiverInfo['name']);
    }

    /**
     * 测试 toCancelOrderApiFormat 方法
     */
    public function testToCancelOrderApiFormatReturnsCorrectFormat(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(PickupOrder::class, $entity);

        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('app_key');
        $config->setAppSecret('app_secret');

        $entity->setConfig($config);
        $entity->setCainiaoOrderCode('CN123456');
        $entity->setCancelReason('用户取消');

        $result = $entity->toCancelOrderApiFormat();
        $this->assertArrayHasKey('orderId', $result);
        $this->assertArrayHasKey('reasonDesc', $result);
        $this->assertArrayHasKey('cnAccountId', $result);

        $this->assertSame('CN123456', $result['orderId']);
        $this->assertSame('用户取消', $result['reasonDesc']);
        $this->assertSame('app_key', $result['cnAccountId']);
    }

    /**
     * 测试 updateFromApiResponse 方法
     */
    public function testUpdateFromApiResponseUpdatesPropertiesCorrectly(): void
    {
        $entity = $this->createEntity();
        self::assertInstanceOf(PickupOrder::class, $entity);

        $config = new CainiaoConfig();
        $config->setName('测试配置');
        $config->setAppKey('app_key');
        $config->setAppSecret('app_secret');

        $entity->setConfig($config);
        $entity->setCainiaoOrderCode('CN123456');

        $apiResponse = [
            'logisticsOrderCode' => 'CN123456',
            'mailNo' => 'SF123456789',
            'cpCode' => 'SF',
            'cpName' => '顺丰速运',
            'pickupTime' => '2023-08-01 10:00:00',
            'status' => '100',
            'orderStatusCode' => '100',
            'courierInfo' => [
                'name' => '张三',
                'mobile' => '13777777777',
            ],
            'payInfo' => [
                'weight' => 2.0,
                'totalPrice' => 20.00,
            ],
            'latestLogisticsDetail' => [
                'updateTime' => '2023-08-01 10:00:00',
            ],
            'packageInfo' => [
                'mailNo' => 'SF123456789',
                'cpCode' => 'SF',
                'cpName' => '顺丰速运',
            ],
        ];

        $entity->updateFromApiResponse($apiResponse);

        $this->assertSame('CN123456', $entity->getCainiaoOrderCode());
        $this->assertSame('SF123456789', $entity->getMailNo());
        $this->assertSame('SF', $entity->getCpCode());
        $this->assertSame('顺丰速运', $entity->getCpName());
        $this->assertSame('张三', $entity->getCourierName());
        $this->assertSame('13777777777', $entity->getCourierPhone());
        $this->assertSame(OrderStatusEnum::WAREHOUSE_ACCEPT, $entity->getStatus());
        $this->assertSame(2.0, $entity->getWeight());
        $this->assertSame(20.00, $entity->getItemValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getLastUpdateTime());
    }
}
