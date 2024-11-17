<?php

namespace App\Entity;

use App\Repository\PanelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: PanelRepository::class)]
class Panel
{
    const UPLOAD_DIRECTORY = 'media/';

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
        return '/' . self::UPLOAD_DIRECTORY . $this->source;
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function upload(): void
    {
        if (null === $this->file) {
            return;
        }

        $this->file->move(
            self::UPLOAD_DIRECTORY,
            $this->file->getClientOriginalName()
        );

        $this->source = $this->file->getClientOriginalName();
    }

    public function getTitle(): string
    {
        return implode(' > ', [($this->getComic() ?? new Comic())->getTitle(), 'Panel #' . $this->id]);
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

}
