<?php

namespace CainiaoPickupBundle\Entity;

use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: PickupOrderRepository::class)]
#[ORM\Table(name: 'cainiao_pickup_order', options: ['comment' => '菜鸟上门取件订单'])]
class PickupOrder implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;


    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '取件单号'])]
    private string $orderCode;

    #[ORM\OneToOne(targetEntity: AddressInfo::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private AddressInfo $senderInfo;

    #[ORM\OneToOne(targetEntity: AddressInfo::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private AddressInfo $receiverInfo;

    #[ORM\Column(type: Types::STRING, enumType: ItemTypeEnum::class, options: ['comment' => '物品类型'])]
    private ItemTypeEnum $itemType;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '物品重量(kg)'])]
    private float $weight;

    #[ORM\Column(type: Types::STRING, enumType: OrderStatusEnum::class, options: ['comment' => '订单状态'])]
    private OrderStatusEnum $status = OrderStatusEnum::CREATE;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '外部用户ID'])]
    private ?string $externalUserId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '预约取件时间段开始时间'])]
    private ?string $expectPickupTimeStart = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '预约取件时间段结束时间'])]
    private ?string $expectPickupTimeEnd = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '菜鸟订单号'])]
    private ?string $cainiaoOrderCode = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '运单号'])]
    private ?string $mailNo = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '物品数量'])]
    private ?string $itemQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '物品价值(元)'])]
    private ?float $itemValue = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '快递员姓名'])]
    private ?string $courierName = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => '快递员电话'])]
    private ?string $courierPhone = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '快递公司编码'])]
    private ?string $cpCode = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '快递公司名称'])]
    private ?string $cpName = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '取件时间'])]
    private ?\DateTimeImmutable $pickupTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后更新时间'])]
    private ?\DateTimeImmutable $lastUpdateTime = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '取消原因'])]
    private ?string $cancelReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '取消时间'])]
    private ?\DateTimeImmutable $cancelTime = null;

    #[ORM\ManyToOne(targetEntity: CainiaoConfig::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '菜鸟开放平台配置'])]
    private CainiaoConfig $config;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '外部用户对应的手机号'])]
    private ?string $externalUserMobile = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->orderCode, $this->status->value);
    }

    // Getters and Setters
    public function getOrderCode(): string
    {
        return $this->orderCode;
    }

    public function setOrderCode(string $orderCode): self
    {
        $this->orderCode = $orderCode;

        return $this;
    }

    public function getSenderInfo(): AddressInfo
    {
        return $this->senderInfo;
    }

    public function setSenderInfo(AddressInfo $senderInfo): self
    {
        $this->senderInfo = $senderInfo;

        return $this;
    }

    public function getReceiverInfo(): AddressInfo
    {
        return $this->receiverInfo;
    }

    public function setReceiverInfo(AddressInfo $receiverInfo): self
    {
        $this->receiverInfo = $receiverInfo;

        return $this;
    }

    public function getItemType(): ItemTypeEnum
    {
        return $this->itemType;
    }

    public function setItemType(ItemTypeEnum $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getStatus(): OrderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getExternalUserId(): ?string
    {
        return $this->externalUserId;
    }

    public function setExternalUserId(?string $externalUserId): self
    {
        $this->externalUserId = $externalUserId;

        return $this;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): self
    {
        $this->remark = $remark;

        return $this;
    }

    public function getExpectPickupTimeStart(): ?string
    {
        return $this->expectPickupTimeStart;
    }

    public function setExpectPickupTimeStart(?string $expectPickupTimeStart): self
    {
        $this->expectPickupTimeStart = $expectPickupTimeStart;

        return $this;
    }

    public function getExpectPickupTimeEnd(): ?string
    {
        return $this->expectPickupTimeEnd;
    }

    public function setExpectPickupTimeEnd(?string $expectPickupTimeEnd): self
    {
        $this->expectPickupTimeEnd = $expectPickupTimeEnd;

        return $this;
    }

    public function getCainiaoOrderCode(): ?string
    {
        return $this->cainiaoOrderCode;
    }

    public function setCainiaoOrderCode(?string $cainiaoOrderCode): self
    {
        $this->cainiaoOrderCode = $cainiaoOrderCode;

        return $this;
    }

    public function getMailNo(): ?string
    {
        return $this->mailNo;
    }

    public function setMailNo(?string $mailNo): self
    {
        $this->mailNo = $mailNo;

        return $this;
    }

    public function getItemQuantity(): ?string
    {
        return $this->itemQuantity;
    }

    public function setItemQuantity(?string $itemQuantity): self
    {
        $this->itemQuantity = $itemQuantity;

        return $this;
    }

    public function getItemValue(): ?float
    {
        return $this->itemValue;
    }

    public function setItemValue(?float $itemValue): self
    {
        $this->itemValue = $itemValue;

        return $this;
    }

    public function getCourierName(): ?string
    {
        return $this->courierName;
    }

    public function setCourierName(?string $courierName): self
    {
        $this->courierName = $courierName;

        return $this;
    }

    public function getCourierPhone(): ?string
    {
        return $this->courierPhone;
    }

    public function setCourierPhone(?string $courierPhone): self
    {
        $this->courierPhone = $courierPhone;

        return $this;
    }

    public function getCpCode(): ?string
    {
        return $this->cpCode;
    }

    public function setCpCode(?string $cpCode): self
    {
        $this->cpCode = $cpCode;

        return $this;
    }

    public function getCpName(): ?string
    {
        return $this->cpName;
    }

    public function setCpName(?string $cpName): self
    {
        $this->cpName = $cpName;

        return $this;
    }

    public function getPickupTime(): ?\DateTimeImmutable
    {
        return $this->pickupTime;
    }

    public function setPickupTime(?\DateTimeImmutable $pickupTime): self
    {
        $this->pickupTime = $pickupTime;

        return $this;
    }

    public function getLastUpdateTime(): ?\DateTimeImmutable
    {
        return $this->lastUpdateTime;
    }

    public function setLastUpdateTime(?\DateTimeImmutable $lastUpdateTime): self
    {
        $this->lastUpdateTime = $lastUpdateTime;

        return $this;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): self
    {
        $this->cancelReason = $cancelReason;

        return $this;
    }

    public function getCancelTime(): ?\DateTimeImmutable
    {
        return $this->cancelTime;
    }

    public function setCancelTime(?\DateTimeImmutable $cancelTime): self
    {
        $this->cancelTime = $cancelTime;

        return $this;
    }

    public function getConfig(): CainiaoConfig
    {
        return $this->config;
    }

    public function setConfig(CainiaoConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * 转换为预查询API请求格式
     */
    public function toPreQueryApiFormat(): array
    {
        $data = [
            'queryCondition' => [
                'senderInfo' => $this->senderInfo->toApiFormat(),
                'receiverInfo' => $this->receiverInfo->toApiFormat(),
                'itemCodeList' => ['3000000040'], // 2小时上门取件
            ],
        ];

        if ($this->externalUserId !== null) {
            $data['queryCondition']['externalUserId'] = $this->externalUserId;
        }

        return $data;
    }

    /**
     * 转换为创建订单API请求格式
     */
    public function toCreateOrderApiFormat(): array
    {
        $data = [
            'senderInfo' => $this->senderInfo->toApiFormat(),
            'receiverInfo' => $this->receiverInfo->toApiFormat(),
            'itemType' => 2,
            'weight' => $this->weight,
            'itemId' => '3000000040',
            'itemVersion' => 2,
            'timeType' => 2,
            'externalUserId' => $this->getExternalUserId(),
            'externalUserMobile' => $this->getExternalUserMobile(),
            'outOrderInfoList' => [
                [
                    'itemType' => $this->getItemType()->value,
                    'outOrderId' => $this->getId(),
                ],
            ],
        ];

        // 添加可选字段
        if ($this->expectPickupTimeStart !== null && $this->expectPickupTimeEnd !== null) {
            $data['appointGotStartTime'] = $this->getExpectPickupTimeStart();
            $data['appointGotEndTime'] = $this->getExpectPickupTimeEnd();
        }

        if ($this->remark !== null) {
            $data['userRemark'] = $this->getRemark();
        }

        return $data;
    }

    /**
     * 转换为修改订单API请求格式
     */
    public function toModifyOrderApiFormat(): array
    {
        $data = [
            'orderId' => $this->cainiaoOrderCode,
            'cnAccountId' => $this->getConfig()->getAppKey(),
            'operatorType' => 1,
            'externalUserId' => $this->getExternalUserId(),
            'senderInfo' => $this->senderInfo->toApiFormat(),
            'receiverInfo' => $this->receiverInfo->toApiFormat(),
        ];

        // 添加可选字段
        if ($this->expectPickupTimeStart !== null && $this->expectPickupTimeEnd !== null) {
            $data['appointGotStartTime'] = $this->getExpectPickupTimeStart();
            $data['appointGotEndTime'] = $this->getExpectPickupTimeEnd();
        }

        if ($this->remark !== null) {
            $data['userRemark'] = $this->remark;
        }

        return $data;
    }

    public function toCancelOrderApiFormat(): array
    {
        return [
            'cnAccountId' => $this->getConfig()->getAppKey(),
            'orderId' => $this->getCainiaoOrderCode(),
            'reasonCode' => '-1',
            'reasonDesc' => $this->getCancelReason(),
            'operatorType' => 1,
        ];
    }

    /**
     * 从API响应更新订单信息
     */
    public function updateFromApiResponse(array $data): self
    {
        $this->setWeight($data['payInfo']['weight']);
        $this->setItemValue($data['payInfo']['totalPrice']);

        if (isset($data['courierInfo']['name'])) {
            $this->setCourierName($data['courierInfo']['name']);
        }
        if (isset($data['courierInfo']['mobile'])) {
            $this->setCourierPhone($data['courierInfo']['mobile']);
        }

        if (isset($data['packageInfo']['mailNo'])) {
            $this->setMailNo($data['mailNo']);
        }
        if (isset($data['packageInfo']['cpCode'])) {
            $this->setCpCode($data['cpCode']);
        }
        if (isset($data['packageInfo']['cpName'])) {
            $this->setCpName($data['cpName']);
        }

        if (!empty($data['latestLogisticsDetail']['updateTime'])) {
            $this->setLastUpdateTime(new \DateTimeImmutable($data['latestLogisticsDetail']['updateTime']));
        }
        if (isset($data['orderStatusCode'])) {
            $this->setStatus(OrderStatusEnum::from($data['orderStatusCode']));
        }

        return $this;
    }

    public function getExternalUserMobile(): ?string
    {
        return $this->externalUserMobile;
    }

    public function setExternalUserMobile(string $externalUserMobile): static
    {
        $this->externalUserMobile = $externalUserMobile;

        return $this;
    }
}
