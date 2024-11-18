<?php

namespace App\Entity;

use App\Repository\CharacterRepository;
use App\Repository\SearchRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
class Character
{
    const UPLOAD_DIRECTORY = 'media/character';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $age = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $biography = null;

    /**
     * @var Collection<int, Comic>
     */
    #[ORM\ManyToMany(targetEntity: Comic::class, inversedBy: 'characters')]
    private Collection $comics;

    #[ORM\Column(length: 255)]
    private ?string $source = null;

    private ?UploadedFile $file = null;

    #[ORM\Column(nullable: true)]
    private ?bool $raw = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $nickname = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $updated = null;

    #[ORM\Column(length: 128)]
    private ?string $description = null;

    public function __construct()
    {
        $this->comics = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(string $biography): static
    {
        $this->biography = $biography;

        return $this;
    }

    /**
     * @return Collection<int, Comic>
     */
    public function getComics(): Collection
    {
        return $this->comics;
    }

    public function addComic(Comic $comic): static
    {
        if (!$this->comics->contains($comic)) {
            $this->comics->add($comic);
        }

        return $this;
    }

    public function removeComic(Comic $comic): static
    {
        $this->comics->removeElement($comic);

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

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): static
    {
        $this->file = $file;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->getId()
            ? DIRECTORY_SEPARATOR . $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getSource()
            : null;
    }

    public function getDirectory(): string
    {
        return self::UPLOAD_DIRECTORY;
    }

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function upload(): void
    {
        if (null === $this->file) {
            return;
        }

        $this->file->move(
            $this->getDirectory(),
            $this->file->getClientOriginalName()
        );

        $this->setSource($this->file->getClientOriginalName());
    }

    public function isRaw(): ?bool
    {
        return $this->raw;
    }

    public function setRaw(?bool $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): static
    {
        $this->nickname = $nickname;

        return $this;
    }

    public function getFeaturedIn(): Collection
    {
        return $this
            ->getComics()
            ->matching(Criteria::create()->orderBy(['id' => Order::Descending])->setMaxResults(16));
    }

    public function __toString(): string
    {
        return $this->getName();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
