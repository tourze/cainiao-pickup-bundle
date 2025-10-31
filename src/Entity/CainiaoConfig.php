<?php

declare(strict_types=1);

namespace CainiaoPickupBundle\Entity;

use CainiaoPickupBundle\Repository\CainiaoConfigRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

#[ORM\Entity(repositoryClass: CainiaoConfigRepository::class)]
#[ORM\Table(name: 'cainiao_config', options: ['comment' => '菜鸟开放平台配置'])]
class CainiaoConfig implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[TrackColumn]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[Assert\Type(type: 'bool', message: '有效性必须是布尔值')]
    private ?bool $valid = false;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => '配置名称'])]
    #[Assert\NotBlank(message: '配置名称不能为空')]
    #[Assert\Length(max: 64, maxMessage: '配置名称长度不能超过 {{ limit }} 个字符')]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AppKey'])]
    #[Assert\NotBlank(message: 'AppKey不能为空')]
    #[Assert\Length(max: 64, maxMessage: 'AppKey长度不能超过 {{ limit }} 个字符')]
    private string $appKey;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AppSecret'])]
    #[Assert\NotBlank(message: 'AppSecret不能为空')]
    #[Assert\Length(max: 64, maxMessage: 'AppSecret长度不能超过 {{ limit }} 个字符')]
    private string $appSecret;

    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'AccessCode'])]
    #[Assert\NotBlank(message: 'AccessCode不能为空')]
    #[Assert\Length(max: 64, maxMessage: 'AccessCode长度不能超过 {{ limit }} 个字符')]
    private string $accessCode;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => 'logistic_provider_id'])]
    #[Assert\Length(max: 100, maxMessage: 'ProviderId长度不能超过 {{ limit }} 个字符')]
    private ?string $providerId = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'API网关地址'])]
    #[Assert\NotBlank(message: 'API网关地址不能为空')]
    #[Assert\Length(max: 255, maxMessage: 'API网关地址长度不能超过 {{ limit }} 个字符')]
    #[Assert\Url(message: '请输入有效的URL地址')]
    private string $apiGateway = 'https://global.link.cainiao.com';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 255, maxMessage: '备注长度不能超过 {{ limit }} 个字符')]
    private ?string $remark = null;

    public function __toString(): string
    {
        return $this->name;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAppKey(): string
    {
        return $this->appKey;
    }

    public function setAppKey(string $appKey): void
    {
        $this->appKey = $appKey;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function setAppSecret(string $appSecret): void
    {
        $this->appSecret = $appSecret;
    }

    public function getAccessCode(): string
    {
        return $this->accessCode;
    }

    public function setAccessCode(string $accessCode): void
    {
        $this->accessCode = $accessCode;
    }

    public function getApiGateway(): string
    {
        return $this->apiGateway;
    }

    public function setApiGateway(string $apiGateway): void
    {
        $this->apiGateway = $apiGateway;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(?string $providerId): void
    {
        $this->providerId = $providerId;
    }
}
