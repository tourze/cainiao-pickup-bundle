<?php

namespace CainiaoPickupBundle\Tests\Entity;

use CainiaoPickupBundle\Entity\AddressInfo;
use CainiaoPickupBundle\Entity\CainiaoConfig;
use CainiaoPickupBundle\Entity\PickupOrder;
use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use PHPUnit\Framework\TestCase;

class PickupOrderTest extends TestCase
{
    private PickupOrder $order;
    private AddressInfo $senderInfo;
    private AddressInfo $receiverInfo;
    private CainiaoConfig $config;

    protected function setUp(): void
    {
        $this->senderInfo = new AddressInfo();
        $this->senderInfo->setName('发件人')
            ->setMobile('13800138000')
            ->setFullAddressDetail('北京市朝阳区三里屯街道10号');

        $this->receiverInfo = new AddressInfo();
        $this->receiverInfo->setName('收件人')
            ->setMobile('13900139000')
            ->setFullAddressDetail('上海市浦东新区张江高科园区88号');

        $this->config = new CainiaoConfig();
        $this->config->setName('测试配置')
            ->setAppKey('app_key')
            ->setAppSecret('app_secret')
            ->setProviderId('cainiao_provider')
            ->setAccessCode('access_code')
            ->setApiGateway('https://api.example.com/cainiao')
            ->setValid(true);

        $this->order = new PickupOrder();
        $this->order->setOrderCode('TEST123456')
            ->setSenderInfo($this->senderInfo)
            ->setReceiverInfo($this->receiverInfo)
            ->setItemType(ItemTypeEnum::DOCUMENT)
            ->setWeight(1.5)
            ->setConfig($this->config);
    }

    public function testGetterSetter_basicProperties(): void
    {
        $this->assertSame('TEST123456', $this->order->getOrderCode());
        $this->assertSame(1.5, $this->order->getWeight());
        $this->assertSame(ItemTypeEnum::DOCUMENT, $this->order->getItemType());
        $this->assertSame(OrderStatusEnum::CREATE, $this->order->getStatus());
        
        // 测试设置和获取其他基本属性
        $this->order->setRemark('测试备注');
        $this->assertSame('测试备注', $this->order->getRemark());
        
        $this->order->setExpectPickupTimeStart('2023-08-01 09:00:00');
        $this->assertSame('2023-08-01 09:00:00', $this->order->getExpectPickupTimeStart());
        
        $this->order->setExpectPickupTimeEnd('2023-08-01 18:00:00');
        $this->assertSame('2023-08-01 18:00:00', $this->order->getExpectPickupTimeEnd());
        
        $this->order->setCainiaoOrderCode('CN123456');
        $this->assertSame('CN123456', $this->order->getCainiaoOrderCode());
        
        $this->order->setMailNo('SF123456789');
        $this->assertSame('SF123456789', $this->order->getMailNo());
    }

    public function testGetterSetter_relationships(): void
    {
        $this->assertSame($this->senderInfo, $this->order->getSenderInfo());
        $this->assertSame($this->receiverInfo, $this->order->getReceiverInfo());
        $this->assertSame($this->config, $this->order->getConfig());
        
        // 测试修改关联关系
        $newSenderInfo = new AddressInfo();
        $newSenderInfo->setName('新发件人')
            ->setMobile('13888888888')
            ->setFullAddressDetail('广州市天河区天河路1号');
        
        $this->order->setSenderInfo($newSenderInfo);
        $this->assertSame($newSenderInfo, $this->order->getSenderInfo());
        $this->assertSame('新发件人', $this->order->getSenderInfo()->getName());
    }

    public function testToPreQueryApiFormat_returnsCorrectFormat(): void
    {
        $this->order->setExternalUserId('user123');
        
        $result = $this->order->toPreQueryApiFormat();
        $this->assertArrayHasKey('queryCondition', $result);
        $this->assertArrayHasKey('senderInfo', $result['queryCondition']);
        $this->assertArrayHasKey('receiverInfo', $result['queryCondition']);
        $this->assertArrayHasKey('itemCodeList', $result['queryCondition']);
        $this->assertArrayHasKey('externalUserId', $result['queryCondition']);
        
        $this->assertSame('发件人', $result['queryCondition']['senderInfo']['name']);
        $this->assertSame('13800138000', $result['queryCondition']['senderInfo']['mobile']);
        $this->assertSame('北京市朝阳区三里屯街道10号', $result['queryCondition']['senderInfo']['fullAddressDetail']);
    }

    public function testToCreateOrderApiFormat_returnsCorrectFormat(): void
    {
        $this->order->setItemQuantity('2');
        $this->order->setItemValue(100.00);
        $this->order->setExternalUserId('user123');
        $this->order->setExternalUserMobile('13888888888');
        
        $result = $this->order->toCreateOrderApiFormat();
        $this->assertArrayHasKey('senderInfo', $result);
        $this->assertArrayHasKey('receiverInfo', $result);
        $this->assertArrayHasKey('itemType', $result);
        $this->assertArrayHasKey('weight', $result);
        $this->assertArrayHasKey('externalUserId', $result);
        $this->assertArrayHasKey('externalUserMobile', $result);
        $this->assertArrayHasKey('outOrderInfoList', $result);
        
        $this->assertSame('发件人', $result['senderInfo']['name']);
        $this->assertSame('收件人', $result['receiverInfo']['name']);
        $this->assertSame(2, $result['itemType']);
        $this->assertSame(1.5, $result['weight']);
        $this->assertSame('user123', $result['externalUserId']);
        $this->assertSame('13888888888', $result['externalUserMobile']);
        $this->assertSame('document', $result['outOrderInfoList'][0]['itemType']);
    }

    public function testToModifyOrderApiFormat_returnsCorrectFormat(): void
    {
        $this->order->setCainiaoOrderCode('CN123456');
        $this->order->setExternalUserId('user123');
        
        $result = $this->order->toModifyOrderApiFormat();
        $this->assertArrayHasKey('orderId', $result);
        $this->assertArrayHasKey('cnAccountId', $result);
        $this->assertArrayHasKey('senderInfo', $result);
        $this->assertArrayHasKey('receiverInfo', $result);
        
        $this->assertSame('CN123456', $result['orderId']);
        $this->assertSame('app_key', $result['cnAccountId']);
        $this->assertSame('发件人', $result['senderInfo']['name']);
        $this->assertSame('收件人', $result['receiverInfo']['name']);
    }

    public function testToCancelOrderApiFormat_returnsCorrectFormat(): void
    {
        $this->order->setCainiaoOrderCode('CN123456');
        $this->order->setCancelReason('用户取消');
        
        $result = $this->order->toCancelOrderApiFormat();
        $this->assertArrayHasKey('orderId', $result);
        $this->assertArrayHasKey('reasonDesc', $result);
        $this->assertArrayHasKey('cnAccountId', $result);
        
        $this->assertSame('CN123456', $result['orderId']);
        $this->assertSame('用户取消', $result['reasonDesc']);
        $this->assertSame('app_key', $result['cnAccountId']);
    }

    public function testUpdateFromApiResponse_updatesPropertiesCorrectly(): void
    {
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
                'totalPrice' => 20.00
            ],
            'latestLogisticsDetail' => [
                'updateTime' => '2023-08-01 10:00:00'
            ],
            'packageInfo' => [
                'mailNo' => 'SF123456789',
                'cpCode' => 'SF',
                'cpName' => '顺丰速运',
            ]
        ];
        
        $this->order->setCainiaoOrderCode('CN123456');
        $this->order->updateFromApiResponse($apiResponse);
        
        $this->assertSame('CN123456', $this->order->getCainiaoOrderCode());
        $this->assertSame('SF123456789', $this->order->getMailNo());
        $this->assertSame('SF', $this->order->getCpCode());
        $this->assertSame('顺丰速运', $this->order->getCpName());
        $this->assertSame('张三', $this->order->getCourierName());
        $this->assertSame('13777777777', $this->order->getCourierPhone());
        $this->assertSame(OrderStatusEnum::WAREHOUSE_ACCEPT, $this->order->getStatus());
        $this->assertSame(2.0, $this->order->getWeight());
        $this->assertSame(20.00, $this->order->getItemValue());
        $this->assertInstanceOf(\DateTime::class, $this->order->getLastUpdateTime());
    }
} 