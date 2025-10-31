<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity]
#[ORM\Table(name: 'cainiao_logistics_detail', options: ['comment' => '物流详情'])]
class LogisticsDetail implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\ManyToOne(targetEntity: PickupOrder::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', options: ['comment' => '取件订单'])]
    #[Assert\NotNull(message: '取件订单不能为空')]
    private PickupOrder $order;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '运单号'])]
    #[Assert\NotBlank(message: '运单号不能为空')]
    #[Assert\Length(max: 64, maxMessage: '运单号长度不能超过 {{ limit }} 个字符')]
    private string $mailNo;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '物流状态'])]
    #[Assert\NotBlank(message: '物流状态不能为空')]
    #[Assert\Length(max: 32, maxMessage: '物流状态长度不能超过 {{ limit }} 个字符')]
    private string $logisticsStatus;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '物流描述'])]
    #[Assert\NotBlank(message: '物流描述不能为空')]
    #[Assert\Length(max: 255, maxMessage: '物流描述长度不能超过 {{ limit }} 个字符')]
    private string $logisticsDescription;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '物流时间'])]
    #[Assert\NotNull(message: '物流时间不能为空')]
    private \DateTimeImmutable $logisticsTime;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '城市'])]
    #[Assert\Length(max: 64, maxMessage: '城市长度不能超过 {{ limit }} 个字符')]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true, options: ['comment' => '地区'])]
    #[Assert\Length(max: 64, maxMessage: '地区长度不能超过 {{ limit }} 个字符')]
    private ?string $area = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '详细地址'])]
    #[Assert\Length(max: 255, maxMessage: '详细地址长度不能超过 {{ limit }} 个字符')]
    private ?string $address = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true, options: ['comment' => '快递员姓名'])]
    #[Assert\Length(max: 32, maxMessage: '快递员姓名长度不能超过 {{ limit }} 个字符')]
    private ?string $courierName = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => '快递员电话'])]
    #[Assert\Length(max: 20, maxMessage: '快递员电话长度不能超过 {{ limit }} 个字符')]
    #[Assert\Regex(pattern: '/^1[3-9]\d{9}$/', message: '请输入有效的手机号码')]
    private ?string $courierPhone = null;

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->logisticsStatus, $this->logisticsDescription);
    }

    public function getOrder(): PickupOrder
    {
        return $this->order;
    }

    public function setOrder(PickupOrder $order): void
    {
        $this->order = $order;
    }

    public function getMailNo(): string
    {
        return $this->mailNo;
    }

    public function setMailNo(string $mailNo): void
    {
        $this->mailNo = $mailNo;
    }

    public function getLogisticsStatus(): string
    {
        return $this->logisticsStatus;
    }

    public function setLogisticsStatus(string $logisticsStatus): void
    {
        $this->logisticsStatus = $logisticsStatus;
    }

    public function getLogisticsDescription(): string
    {
        return $this->logisticsDescription;
    }

    public function setLogisticsDescription(string $logisticsDescription): void
    {
        $this->logisticsDescription = $logisticsDescription;
    }

    public function getLogisticsTime(): \DateTimeImmutable
    {
        return $this->logisticsTime;
    }

    public function setLogisticsTime(\DateTimeImmutable $logisticsTime): void
    {
        $this->logisticsTime = $logisticsTime;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(?string $area): void
    {
        $this->area = $area;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
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
}
