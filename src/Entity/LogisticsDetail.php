<?php

namespace CainiaoPickupBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;

#[ORM\Entity]
#[ORM\Table(name: 'cainiao_logistics_detail', options: ['comment' => '物流详情'])]
class LogisticsDetail
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: PickupOrder::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', options: ['comment' => '取件订单'])]
    private PickupOrder $order;

    #[ORM\Column(type: 'string', length: 64, options: ['comment' => '运单号'])]
    private string $mailNo;

    #[ORM\Column(type: 'string', length: 32, options: ['comment' => '物流状态'])]
    private string $logisticsStatus;

    #[ORM\Column(type: 'string', length: 255, options: ['comment' => '物流描述'])]
    private string $logisticsDescription;

    #[ORM\Column(type: 'datetime_immutable', options: ['comment' => '物流时间'])]
    private \DateTimeImmutable $logisticsTime;

    #[ORM\Column(type: 'string', length: 64, nullable: true, options: ['comment' => '城市'])]
    private ?string $city = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true, options: ['comment' => '地区'])]
    private ?string $area = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => '详细地址'])]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true, options: ['comment' => '快递员姓名'])]
    private ?string $courierName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, options: ['comment' => '快递员电话'])]
    private ?string $courierPhone = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOrder(): PickupOrder
    {
        return $this->order;
    }

    public function setOrder(PickupOrder $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getMailNo(): string
    {
        return $this->mailNo;
    }

    public function setMailNo(string $mailNo): self
    {
        $this->mailNo = $mailNo;

        return $this;
    }

    public function getLogisticsStatus(): string
    {
        return $this->logisticsStatus;
    }

    public function setLogisticsStatus(string $logisticsStatus): self
    {
        $this->logisticsStatus = $logisticsStatus;

        return $this;
    }

    public function getLogisticsDescription(): string
    {
        return $this->logisticsDescription;
    }

    public function setLogisticsDescription(string $logisticsDescription): self
    {
        $this->logisticsDescription = $logisticsDescription;

        return $this;
    }

    public function getLogisticsTime(): \DateTimeImmutable
    {
        return $this->logisticsTime;
    }

    public function setLogisticsTime(\DateTimeImmutable $logisticsTime): self
    {
        $this->logisticsTime = $logisticsTime;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(?string $area): self
    {
        $this->area = $area;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

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
}
