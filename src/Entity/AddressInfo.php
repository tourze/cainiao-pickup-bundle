<?php

namespace CainiaoPickupBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity]
#[ORM\Table(name: 'cainiao_address_info', options: ['comment' => '地址信息'])]
class AddressInfo implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '联系人姓名'])]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '联系人电话'])]
    private string $mobile;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '完整地址'])]
    private string $fullAddressDetail;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '省份名称'])]
    private ?string $provinceName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '城市名称'])]
    private ?string $cityName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '区域名称'])]
    private ?string $areaName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '详细地址'])]
    private ?string $address = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->name, $this->fullAddressDetail);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getFullAddressDetail(): string
    {
        return $this->fullAddressDetail;
    }

    public function setFullAddressDetail(string $fullAddressDetail): self
    {
        $this->fullAddressDetail = $fullAddressDetail;

        return $this;
    }

    /**
     * 转换为API请求格式
     */
    public function toApiFormat(): array
    {
        return [
            'name' => $this->name,
            'mobile' => $this->mobile,
            'fullAddressDetail' => $this->fullAddressDetail,
            'provinceName' => $this->provinceName,
            'cityName' => $this->cityName,
            'areaName' => $this->areaName,
            'address' => $this->address,
        ];
    }

    public function getProvinceName(): ?string
    {
        return $this->provinceName;
    }

    public function setProvinceName(?string $provinceName): static
    {
        $this->provinceName = $provinceName;

        return $this;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function setCityName(?string $cityName): static
    {
        $this->cityName = $cityName;

        return $this;
    }

    public function getAreaName(): ?string
    {
        return $this->areaName;
    }

    public function setAreaName(?string $areaName): static
    {
        $this->areaName = $areaName;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }
}
