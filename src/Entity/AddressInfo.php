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
#[ORM\Table(name: 'cainiao_address_info', options: ['comment' => '地址信息'])]
class AddressInfo implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => '联系人姓名'])]
    #[Assert\NotBlank(message: '联系人姓名不能为空')]
    #[Assert\Length(max: 32, maxMessage: '联系人姓名长度不能超过 {{ limit }} 个字符')]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '联系人电话'])]
    #[Assert\NotBlank(message: '联系人电话不能为空')]
    #[Assert\Length(max: 20, maxMessage: '联系人电话长度不能超过 {{ limit }} 个字符')]
    #[Assert\Regex(pattern: '/^1[3-9]\d{9}$/', message: '请输入有效的手机号码')]
    private string $mobile;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '完整地址'])]
    #[Assert\NotBlank(message: '完整地址不能为空')]
    #[Assert\Length(max: 255, maxMessage: '完整地址长度不能超过 {{ limit }} 个字符')]
    private string $fullAddressDetail;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '省份名称'])]
    #[Assert\Length(max: 255, maxMessage: '省份名称长度不能超过 {{ limit }} 个字符')]
    private ?string $provinceName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '城市名称'])]
    #[Assert\Length(max: 255, maxMessage: '城市名称长度不能超过 {{ limit }} 个字符')]
    private ?string $cityName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '区域名称'])]
    #[Assert\Length(max: 255, maxMessage: '区域名称长度不能超过 {{ limit }} 个字符')]
    private ?string $areaName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '详细地址'])]
    #[Assert\Length(max: 255, maxMessage: '详细地址长度不能超过 {{ limit }} 个字符')]
    private ?string $address = null;

    public function __toString(): string
    {
        return sprintf('%s - %s', $this->name, $this->fullAddressDetail);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    public function getFullAddressDetail(): string
    {
        return $this->fullAddressDetail;
    }

    public function setFullAddressDetail(string $fullAddressDetail): void
    {
        $this->fullAddressDetail = $fullAddressDetail;
    }

    /**
     * 转换为API请求格式
     *
     * @return array<string, string|null>
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

    public function setProvinceName(?string $provinceName): void
    {
        $this->provinceName = $provinceName;
    }

    public function getCityName(): ?string
    {
        return $this->cityName;
    }

    public function setCityName(?string $cityName): void
    {
        $this->cityName = $cityName;
    }

    public function getAreaName(): ?string
    {
        return $this->areaName;
    }

    public function setAreaName(?string $areaName): void
    {
        $this->areaName = $areaName;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }
}
