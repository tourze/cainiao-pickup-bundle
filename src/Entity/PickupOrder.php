<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Entity;

use CainiaoPickupBundle\Enum\ItemTypeEnum;
use CainiaoPickupBundle\Enum\OrderStatusEnum;
use CainiaoPickupBundle\Repository\PickupOrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: PickupOrderRepository::class)]
#[ORM\Table(name: 'cainiao_pickup_order', options: ['comment' => '菜鸟上门取件订单'])]
class PickupOrder implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '取件单号'])]
    #[Assert\NotBlank(message: '取件单号不能为空')]
    #[Assert\Length(max: 64, maxMessage: '取件单号长度不能超过 {{ limit }} 个字符')]
    private string $orderCode;

    #[ORM\OneToOne(targetEntity: AddressInfo::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: '发件人信息不能为空')]
    #[Assert\Valid]
    private AddressInfo $senderInfo;

    #[ORM\OneToOne(targetEntity: AddressInfo::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: '收件人信息不能为空')]
    #[Assert\Valid]
    private AddressInfo $receiverInfo;

    #[ORM\Column(type: Types::STRING, enumType: ItemTypeEnum::class, options: ['comment' => '物品类型'])]
    #[Assert\NotNull(message: '物品类型不能为空')]
    #[Assert\Choice(choices: ['document', 'clothing', 'electronics', 'food', 'fragile', 'other'], message: '物品类型选择无效')]
    private ItemTypeEnum $itemType;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '物品重量(kg)'])]
    #[Assert\NotBlank(message: '物品重量不能为空')]
    #[Assert\Positive(message: '物品重量必须大于0')]
    private float $weight;

    #[ORM\Column(type: Types::STRING, enumType: OrderStatusEnum::class, options: ['comment' => '订单状态'])]
    #[Assert\NotNull(message: '订单状态不能为空')]
    #[Assert\Choice(choices: ['cancelled', '0', '100', '150', '200', '300', '400', '430', '470', '471', '472', '473', '474', '475', '500', '600', '700', '800', '900', '901', '950', '1000', '1100', '1200'], message: '订单状态选择无效')]
    private OrderStatusEnum $status = OrderStatusEnum::CREATE;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '外部用户ID'])]
    #[Assert\NotBlank(message: '外部用户ID不能为空')]
    #[Assert\Length(max: 64, maxMessage: '外部用户ID长度不能超过 {{ limit }} 个字符')]
    private ?string $externalUserId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 255, maxMessage: '备注长度不能超过 {{ limit }} 个字符')]
    private ?string $remark = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '预约取件时间段开始时间'])]
    #[Assert\Length(max: 32, maxMessage: '预约取件开始时间长度不能超过 {{ limit }} 个字符')]
    private ?string $expectPickupTimeStart = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '预约取件时间段结束时间'])]
    #[Assert\Length(max: 32, maxMessage: '预约取件结束时间长度不能超过 {{ limit }} 个字符')]
    private ?string $expectPickupTimeEnd = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '菜鸟订单号'])]
    #[Assert\Length(max: 64, maxMessage: '菜鸟订单号长度不能超过 {{ limit }} 个字符')]
    private ?string $cainiaoOrderCode = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '运单号'])]
    #[Assert\Length(max: 64, maxMessage: '运单号长度不能超过 {{ limit }} 个字符')]
    private ?string $mailNo = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '物品数量'])]
    #[Assert\Length(max: 32, maxMessage: '物品数量长度不能超过 {{ limit }} 个字符')]
    private ?string $itemQuantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true, options: ['comment' => '物品价值(元)'])]
    #[Assert\PositiveOrZero(message: '物品价值不能为负数')]
    private ?float $itemValue = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '快递员姓名'])]
    #[Assert\Length(max: 32, maxMessage: '快递员姓名长度不能超过 {{ limit }} 个字符')]
    private ?string $courierName = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => '快递员电话'])]
    #[Assert\Length(max: 20, maxMessage: '快递员电话长度不能超过 {{ limit }} 个字符')]
    #[Assert\Regex(pattern: '/^1[3-9]\d{9}$/', message: '请输入有效的手机号码')]
    private ?string $courierPhone = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '快递公司编码'])]
    #[Assert\Length(max: 32, maxMessage: '快递公司编码长度不能超过 {{ limit }} 个字符')]
    private ?string $cpCode = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '快递公司名称'])]
    #[Assert\Length(max: 64, maxMessage: '快递公司名称长度不能超过 {{ limit }} 个字符')]
    private ?string $cpName = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '取件时间'])]
    #[Assert\Type(type: '\DateTimeImmutable', message: '取件时间格式不正确')]
    private ?\DateTimeImmutable $pickupTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后更新时间'])]
    #[Assert\Type(type: '\DateTimeImmutable', message: '最后更新时间格式不正确')]
    private ?\DateTimeImmutable $lastUpdateTime = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '取消原因'])]
    #[Assert\Length(max: 255, maxMessage: '取消原因长度不能超过 {{ limit }} 个字符')]
    private ?string $cancelReason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '取消时间'])]
    #[Assert\Type(type: '\DateTimeImmutable', message: '取消时间格式不正确')]
    private ?\DateTimeImmutable $cancelTime = null;

    #[ORM\ManyToOne(targetEntity: CainiaoConfig::class)]
    #[ORM\JoinColumn(nullable: false, options: ['comment' => '菜鸟开放平台配置'])]
    #[Assert\NotNull(message: '菜鸟配置不能为空')]
    #[Assert\Valid]
    private CainiaoConfig $config;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '外部用户对应的手机号'])]
    #[Assert\Length(max: 20, maxMessage: '外部用户手机号长度不能超过 {{ limit }} 个字符')]
    #[Assert\Regex(pattern: '/^1[3-9]\d{9}$/', message: '请输入有效的手机号码')]
    private ?string $externalUserMobile = null;

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->orderCode, $this->status->value);
    }

    // Getters and Setters
    public function getOrderCode(): string
    {
        return $this->orderCode;
    }

    public function setOrderCode(string $orderCode): void
    {
        $this->orderCode = $orderCode;
    }

    public function getSenderInfo(): AddressInfo
    {
        return $this->senderInfo;
    }

    public function setSenderInfo(AddressInfo $senderInfo): void
    {
        $this->senderInfo = $senderInfo;
    }

    public function getReceiverInfo(): AddressInfo
    {
        return $this->receiverInfo;
    }

    public function setReceiverInfo(AddressInfo $receiverInfo): void
    {
        $this->receiverInfo = $receiverInfo;
    }

    public function getItemType(): ItemTypeEnum
    {
        return $this->itemType;
    }

    public function setItemType(ItemTypeEnum $itemType): void
    {
        $this->itemType = $itemType;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    public function getStatus(): OrderStatusEnum
    {
        return $this->status;
    }

    public function setStatus(OrderStatusEnum $status): void
    {
        $this->status = $status;
    }

    public function getExternalUserId(): ?string
    {
        return $this->externalUserId;
    }

    public function setExternalUserId(?string $externalUserId): void
    {
        $this->externalUserId = $externalUserId;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getExpectPickupTimeStart(): ?string
    {
        return $this->expectPickupTimeStart;
    }

    public function setExpectPickupTimeStart(?string $expectPickupTimeStart): void
    {
        $this->expectPickupTimeStart = $expectPickupTimeStart;
    }

    public function getExpectPickupTimeEnd(): ?string
    {
        return $this->expectPickupTimeEnd;
    }

    public function setExpectPickupTimeEnd(?string $expectPickupTimeEnd): void
    {
        $this->expectPickupTimeEnd = $expectPickupTimeEnd;
    }

    public function getCainiaoOrderCode(): ?string
    {
        return $this->cainiaoOrderCode;
    }

    public function setCainiaoOrderCode(?string $cainiaoOrderCode): void
    {
        $this->cainiaoOrderCode = $cainiaoOrderCode;
    }

    public function getMailNo(): ?string
    {
        return $this->mailNo;
    }

    public function setMailNo(?string $mailNo): void
    {
        $this->mailNo = $mailNo;
    }

    public function getItemQuantity(): ?string
    {
        return $this->itemQuantity;
    }

    public function setItemQuantity(?string $itemQuantity): void
    {
        $this->itemQuantity = $itemQuantity;
    }

    public function getItemValue(): ?float
    {
        return $this->itemValue;
    }

    public function setItemValue(?float $itemValue): void
    {
        $this->itemValue = $itemValue;
    }

    public function getCourierName(): ?string
    {
        return $this->courierName;
    }

    public function setCourierName(?string $courierName): void
    {
        $this->courierName = $courierName;
    }

    public function getCourierPhone(): ?string
    {
        return $this->courierPhone;
    }

    public function setCourierPhone(?string $courierPhone): void
    {
        $this->courierPhone = $courierPhone;
    }

    public function getCpCode(): ?string
    {
        return $this->cpCode;
    }

    public function setCpCode(?string $cpCode): void
    {
        $this->cpCode = $cpCode;
    }

    public function getCpName(): ?string
    {
        return $this->cpName;
    }

    public function setCpName(?string $cpName): void
    {
        $this->cpName = $cpName;
    }

    public function getPickupTime(): ?\DateTimeImmutable
    {
        return $this->pickupTime;
    }

    public function setPickupTime(?\DateTimeImmutable $pickupTime): void
    {
        $this->pickupTime = $pickupTime;
    }

    public function getLastUpdateTime(): ?\DateTimeImmutable
    {
        return $this->lastUpdateTime;
    }

    public function setLastUpdateTime(?\DateTimeImmutable $lastUpdateTime): void
    {
        $this->lastUpdateTime = $lastUpdateTime;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): void
    {
        $this->cancelReason = $cancelReason;
    }

    public function getCancelTime(): ?\DateTimeImmutable
    {
        return $this->cancelTime;
    }

    public function setCancelTime(?\DateTimeImmutable $cancelTime): void
    {
        $this->cancelTime = $cancelTime;
    }

    public function getConfig(): CainiaoConfig
    {
        return $this->config;
    }

    public function setConfig(CainiaoConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * 转换为预查询API请求格式
     *
     * @return array<string, mixed>
     */
    public function toPreQueryApiFormat(): array
    {
        return $this->buildPreQueryData();
    }

    /**
     * 构建预查询数据结构
     *
     * @return array<string, mixed>
     */
    private function buildPreQueryData(): array
    {
        $data = [
            'queryCondition' => [
                'senderInfo' => $this->senderInfo->toApiFormat(),
                'receiverInfo' => $this->receiverInfo->toApiFormat(),
                'itemCodeList' => ['3000000040'], // 2小时上门取件
            ],
        ];

        $this->addExternalUserIdToQuery($data);

        return $data;
    }

    /**
     * 添加外部用户ID到查询条件
     *
     * @param array<string, mixed> $data
     */
    private function addExternalUserIdToQuery(array &$data): void
    {
        if (null !== $this->externalUserId) {
            $data['queryCondition']['externalUserId'] = $this->externalUserId;
        }
    }

    /**
     * 转换为创建订单API请求格式
     *
     * @return array<string, mixed>
     */
    public function toCreateOrderApiFormat(): array
    {
        return $this->buildCreateOrderData();
    }

    /**
     * 构建创建订单数据结构
     *
     * @return array<string, mixed>
     */
    private function buildCreateOrderData(): array
    {
        $data = $this->getCreateOrderBaseData();
        $this->addOptionalFieldsToCreateOrder($data);

        return $data;
    }

    /**
     * 获取创建订单的基础数据
     *
     * @return array<string, mixed>
     */
    private function getCreateOrderBaseData(): array
    {
        return [
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
    }

    /**
     * 添加可选字段到创建订单数据
     *
     * @param array<string, mixed> $data
     */
    private function addOptionalFieldsToCreateOrder(array &$data): void
    {
        $this->addPickupTimeToData($data);
        $this->addRemarkToData($data);
    }

    /**
     * 添加预约时间到数据
     *
     * @param array<string, mixed> $data
     */
    private function addPickupTimeToData(array &$data): void
    {
        if (null !== $this->expectPickupTimeStart && null !== $this->expectPickupTimeEnd) {
            $data['appointGotStartTime'] = $this->getExpectPickupTimeStart();
            $data['appointGotEndTime'] = $this->getExpectPickupTimeEnd();
        }
    }

    /**
     * 添加备注到数据
     *
     * @param array<string, mixed> $data
     */
    private function addRemarkToData(array &$data): void
    {
        if (null !== $this->remark) {
            $data['userRemark'] = $this->getRemark();
        }
    }

    /**
     * 转换为修改订单API请求格式
     *
     * @return array<string, mixed>
     */
    public function toModifyOrderApiFormat(): array
    {
        return $this->buildModifyOrderData();
    }

    /**
     * 构建修改订单数据结构
     *
     * @return array<string, mixed>
     */
    private function buildModifyOrderData(): array
    {
        $data = $this->getModifyOrderBaseData();
        $this->addOptionalFieldsToModifyOrder($data);

        return $data;
    }

    /**
     * 获取修改订单的基础数据
     *
     * @return array<string, mixed>
     */
    private function getModifyOrderBaseData(): array
    {
        return [
            'orderId' => $this->cainiaoOrderCode,
            'cnAccountId' => $this->getConfig()->getAppKey(),
            'operatorType' => 1,
            'externalUserId' => $this->getExternalUserId(),
            'senderInfo' => $this->senderInfo->toApiFormat(),
            'receiverInfo' => $this->receiverInfo->toApiFormat(),
        ];
    }

    /**
     * 添加可选字段到修改订单数据
     *
     * @param array<string, mixed> $data
     */
    private function addOptionalFieldsToModifyOrder(array &$data): void
    {
        $this->addPickupTimeToData($data);
        $this->addRemarkToData($data);
    }

    /**
     * @return array<string, mixed>
     */
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
     *
     * @param array<string, mixed> $data
     */
    public function updateFromApiResponse(array $data): void
    {
        $this->processPayInfo($data);
        $this->processCourierInfo($data);
        $this->processPackageInfo($data);
        $this->processLatestLogisticsDetail($data);
        $this->updateOrderStatus($data);
    }

    /**
     * 处理支付信息
     *
     * @param array<string, mixed> $data
     */
    private function processPayInfo(array $data): void
    {
        if (isset($data['payInfo']) && is_array($data['payInfo'])) {
            /** @var array<string, mixed> $payInfo */
            $payInfo = $data['payInfo'];
            $this->updatePayInfo($payInfo);
        }
    }

    /**
     * 处理快递员信息
     *
     * @param array<string, mixed> $data
     */
    private function processCourierInfo(array $data): void
    {
        if (isset($data['courierInfo']) && is_array($data['courierInfo'])) {
            /** @var array<string, mixed> $courierInfo */
            $courierInfo = $data['courierInfo'];
            $this->updateCourierInfo($courierInfo);
        }
    }

    /**
     * 处理包裹信息
     *
     * @param array<string, mixed> $data
     */
    private function processPackageInfo(array $data): void
    {
        if (isset($data['packageInfo']) && is_array($data['packageInfo'])) {
            /** @var array<string, mixed> $packageInfo */
            $packageInfo = $data['packageInfo'];
            $this->updatePackageInfo($packageInfo);
        }
    }

    /**
     * 处理最新物流详情
     *
     * @param array<string, mixed> $data
     */
    private function processLatestLogisticsDetail(array $data): void
    {
        if (isset($data['latestLogisticsDetail']) && is_array($data['latestLogisticsDetail'])) {
            /** @var array<string, mixed> $logisticsDetail */
            $logisticsDetail = $data['latestLogisticsDetail'];
            $this->updateLatestLogisticsDetail($logisticsDetail);
        }
    }

    /**
     * 更新订单状态
     *
     * @param array<string, mixed> $data
     */
    private function updateOrderStatus(array $data): void
    {
        if (isset($data['orderStatusCode'])) {
            $statusCode = $this->castToNullableString($data['orderStatusCode']);
            if (null !== $statusCode) {
                $this->setStatus(OrderStatusEnum::from($statusCode));
            }
        }
    }

    /**
     * 更新支付信息
     *
     * @param array<string, mixed> $payInfo
     */
    private function updatePayInfo(array $payInfo): void
    {
        if (isset($payInfo['weight'])) {
            try {
                $this->setWeight($this->castToFloat($payInfo['weight']));
            } catch (\InvalidArgumentException) {
                // 忽略无效的weight数据，保持原有值
            }
        }
        if (isset($payInfo['totalPrice'])) {
            try {
                $this->setItemValue($this->castToNullableFloat($payInfo['totalPrice']));
            } catch (\InvalidArgumentException) {
                // 忽略无效的totalPrice数据，保持原有值
            }
        }
    }

    /**
     * 更新快递员信息
     *
     * @param array<string, mixed> $courierInfo
     */
    private function updateCourierInfo(array $courierInfo): void
    {
        if (isset($courierInfo['name'])) {
            $this->setCourierName($this->castToNullableString($courierInfo['name']));
        }
        if (isset($courierInfo['mobile'])) {
            $this->setCourierPhone($this->castToNullableString($courierInfo['mobile']));
        }
    }

    /**
     * 更新包裹信息
     *
     * @param array<string, mixed> $packageInfo
     */
    private function updatePackageInfo(array $packageInfo): void
    {
        if (isset($packageInfo['mailNo'])) {
            $this->setMailNo($this->castToNullableString($packageInfo['mailNo']));
        }
        if (isset($packageInfo['cpCode'])) {
            $this->setCpCode($this->castToNullableString($packageInfo['cpCode']));
        }
        if (isset($packageInfo['cpName'])) {
            $this->setCpName($this->castToNullableString($packageInfo['cpName']));
        }
    }

    /**
     * 更新最新物流详情
     *
     * @param array<string, mixed> $logisticsDetail
     */
    private function updateLatestLogisticsDetail(array $logisticsDetail): void
    {
        if (isset($logisticsDetail['updateTime'])) {
            $updateTime = $this->castToNullableString($logisticsDetail['updateTime']);
            if (null !== $updateTime) {
                try {
                    $this->setLastUpdateTime(new \DateTimeImmutable($updateTime));
                } catch (\Exception) {
                    // 忽略无效的日期格式，保持原有值
                }
            }
        }
    }

    /**
     * 安全地将mixed类型转换为float
     */
    private function castToFloat(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }
        if (is_int($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw new \InvalidArgumentException(sprintf('Cannot convert %s to float', gettype($value)));
    }

    /**
     * 安全地将mixed类型转换为?float
     */
    private function castToNullableFloat(mixed $value): ?float
    {
        if (null === $value) {
            return null;
        }

        return $this->castToFloat($value);
    }

    /**
     * 安全地将mixed类型转换为?string
     */
    private function castToNullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        throw new \InvalidArgumentException(sprintf('Cannot convert %s to string', gettype($value)));
    }

    public function getExternalUserMobile(): ?string
    {
        return $this->externalUserMobile;
    }

    public function setExternalUserMobile(?string $externalUserMobile): void
    {
        $this->externalUserMobile = $externalUserMobile;
    }
}
