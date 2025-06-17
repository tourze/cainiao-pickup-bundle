<?php

namespace CainiaoPickupBundle\Entity;

use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Attribute\CreatedByColumn;
use Tourze\DoctrineUserBundle\Attribute\UpdatedByColumn;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;

#[Creatable]
#[Editable]
#[ORM\Entity(repositoryClass: CainiaoConfigRepository::class)]
#[ORM\Table(name: 'cainiao_config', options: ['comment' => '菜鸟开放平台配置'])]
class CainiaoConfig
{
    use TimestampableAware;

    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[CreatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '创建人'])]
    private ?string $createdBy = null;

    #[UpdatedByColumn]
    #[ORM\Column(nullable: true, options: ['comment' => '更新人'])]
    private ?string $updatedBy = null;

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private ?bool $valid = false;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: 'string', length: 64, options: ['comment' => '配置名称'])]
    private string $name;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: 'string', length: 64, options: ['comment' => 'AppKey'])]
    private string $appKey;

    #[FormField]
    #[ORM\Column(type: 'string', length: 64, options: ['comment' => 'AppSecret'])]
    private string $appSecret;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: 'string', length: 64, options: ['comment' => 'AccessCode'])]
    private string $accessCode;

    #[ListColumn]
    #[FormField]
    #[ORM\Column(length: 100, options: ['comment' => 'logistic_provider_id'])]
    private ?string $providerId = null;

    #[FormField]
    #[ORM\Column(type: 'string', length: 255, options: ['comment' => 'API网关地址'])]
    private string $apiGateway = 'https://global.link.cainiao.com';

    #[FormField]
    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setCreatedBy(?string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setUpdatedBy(?string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
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

    public function getAppKey(): string
    {
        return $this->appKey;
    }

    public function setAppKey(string $appKey): self
    {
        $this->appKey = $appKey;

        return $this;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function setAppSecret(string $appSecret): self
    {
        $this->appSecret = $appSecret;

        return $this;
    }

    public function getAccessCode(): string
    {
        return $this->accessCode;
    }

    public function setAccessCode(string $accessCode): self
    {
        $this->accessCode = $accessCode;

        return $this;
    }

    public function getApiGateway(): string
    {
        return $this->apiGateway;
    }

    public function setApiGateway(string $apiGateway): self
    {
        $this->apiGateway = $apiGateway;

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

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(string $providerId): static
    {
        $this->providerId = $providerId;

        return $this;
    }
}
