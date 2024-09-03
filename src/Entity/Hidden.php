<?php

namespace App\Entity;

use App\Repository\HiddenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HiddenRepository::class)]
class Hidden
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'hidden', cascade: ['persist', 'remove'])]
    private ?Comic $comic = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(length: 255)]
    private ?string $source = null;

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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;

        return $this;
    }
}
