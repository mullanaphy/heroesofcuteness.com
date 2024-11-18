<?php

namespace App\Entity;

use App\Repository\PanelRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: PanelRepository::class)]
class Panel
{
    const UPLOAD_DIRECTORY = 'media/comic/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'panels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Comic $comic = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $source = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    private ?UploadedFile $file = null;

    #[ORM\Column]
    private ?int $sort = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dialogue = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $updated = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getComic(): ?Comic
    {
        return $this->comic;
    }

    public function setComic(?Comic $comic): static
    {
        $this->comic = $comic;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getPath(): ?string
    {
        return $this->getId()
            ? DIRECTORY_SEPARATOR . $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getSource()
            : null;
    }

    public function getDirectory(): ?string
    {
        return self::UPLOAD_DIRECTORY . $this->getComic()->getId();
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function upload(): void
    {
        if (null === $this->getFile()) {
            return;
        }

        $this->file->move(
            $this->getDirectory(),
            $this->file->getClientOriginalName()
        );

        $this->setSource($this->file->getClientOriginalName());
        $this->setFile(null);
    }

    public function getTitle(): string
    {
        return implode(' > ', [($this->getComic() ?? new Comic())->getTitle(), 'Panel #' . $this->getSort()]);
    }

    public function toArray(): array
    {
        return [
            'alt' => $this->alt,
            'source' => $this->source,
            'path' => $this->getPath(),
        ];
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getDialogue(): ?string
    {
        return $this->dialogue;
    }

    public function setDialogue(?string $dialogue): static
    {
        $this->dialogue = $dialogue;

        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    #[ORM\PrePersist]
    public function refreshCreated(): void
    {
        $this->setCreated(new DateTime());
        $this->refreshUpdated();
    }

    #[ORM\PreUpdate]
    public function refreshUpdated(): void
    {
        $this->setUpdated(new DateTime());
    }
}
