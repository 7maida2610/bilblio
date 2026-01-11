<?php

namespace App\Entity;

use App\Repository\ReadingProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReadingProgressRepository::class)]
#[ORM\Table(name: 'reading_progress')]
class ReadingProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Livre::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Livre $livre = null;

    #[ORM\Column(type: 'integer')]
    private int $progressPercentage = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $currentPage = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $lastReadAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isCompleted = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $bookmarks = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->lastReadAt = new \DateTime();
        $this->bookmarks = [];
    }

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

    public function getLivre(): ?Livre
    {
        return $this->livre;
    }

    public function setLivre(?Livre $livre): static
    {
        $this->livre = $livre;

        return $this;
    }

    public function getProgressPercentage(): int
    {
        return $this->progressPercentage;
    }

    public function setProgressPercentage(int $progressPercentage): static
    {
        $this->progressPercentage = $progressPercentage;

        if ($progressPercentage >= 100) {
            $this->isCompleted = true;
        }

        return $this;
    }

    public function getCurrentPage(): ?int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(?int $currentPage): static
    {
        $this->currentPage = $currentPage;

        // Auto-calculate progress percentage if we have current page and total pages
        if ($currentPage !== null && $this->livre && $this->livre->getNbPages()) {
            $totalPages = $this->livre->getNbPages();
            $percentage = min(100, ($currentPage / $totalPages) * 100);
            $this->setProgressPercentage((int) $percentage);
        }

        return $this;
    }

    public function getLastReadAt(): \DateTimeInterface
    {
        return $this->lastReadAt;
    }

    public function setLastReadAt(\DateTimeInterface $lastReadAt): static
    {
        $this->lastReadAt = $lastReadAt;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    public function getBookmarks(): ?array
    {
        return $this->bookmarks ?? [];
    }

    public function setBookmarks(?array $bookmarks): static
    {
        $this->bookmarks = $bookmarks ?? [];

        return $this;
    }

    public function addBookmark(int $page, string $title, ?string $note = null): static
    {
        if (!is_array($this->bookmarks)) {
            $this->bookmarks = [];
        }

        $this->bookmarks[] = [
            'page' => $page,
            'title' => $title,
            'note' => $note,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        return $this;
    }

    public function removeBookmark(int $index): static
    {
        if (is_array($this->bookmarks) && isset($this->bookmarks[$index])) {
            unset($this->bookmarks[$index]);
            $this->bookmarks = array_values($this->bookmarks); // Reindex array
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}