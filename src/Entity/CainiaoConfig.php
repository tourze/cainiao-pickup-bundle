<?php

namespace CainiaoPickupBundle\Entity;

use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: CainiaoConfigRepository::class)]
#[ORM\Table(name: 'cainiao_config', options: ['comment' => '菜鸟开放平台配置'])]
class CainiaoConfig implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;


    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '配置名称'])]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AppKey'])]
    private string $appKey;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AppSecret'])]
    private string $appSecret;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AccessCode'])]
    private string $accessCode;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => 'logistic_provider_id'])]
    private ?string $providerId = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'API网关地址'])]
    private string $apiGateway = 'https://global.link.cainiao.com';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->name;
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
