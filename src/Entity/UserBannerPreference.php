<?php

namespace App\Entity;

use App\Repository\UserBannerPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBannerPreferenceRepository::class)]
#[ORM\Table(name: 'user_banner_preference')]
#[ORM\UniqueConstraint(name: 'unique_user_banner', columns: ['user_id', 'banner_id'])]
class UserBannerPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'bannerPreferences')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Banner::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Banner $banner = null;

    #[ORM\Column]
    private bool $hidden = false;

    #[ORM\Column]
    private ?\DateTime $hiddenAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBanner(): ?Banner
    {
        return $this->banner;
    }

    public function setBanner(?Banner $banner): static
    {
        $this->banner = $banner;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;
        if ($hidden) {
            $this->hiddenAt = new \DateTime();
        } else {
            $this->hiddenAt = null;
        }

        return $this;
    }

    public function getHiddenAt(): ?\DateTime
    {
        return $this->hiddenAt;
    }

    public function setHiddenAt(?\DateTime $hiddenAt): static
    {
        $this->hiddenAt = $hiddenAt;

        return $this;
    }
}
