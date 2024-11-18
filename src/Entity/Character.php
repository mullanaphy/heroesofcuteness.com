<?php

namespace App\Entity;

use App\Config;
use App\Repository\CharacterRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CharacterRepository::class)]
#[ORM\Table(name: '`character`')]
class Character
{
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

    private ?UploadedFile $sourceFile = null;
    private ?UploadedFile $thumbnailFile = null;

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

    #[ORM\Column(length: 255)]
    private ?string $thumbnail = null;

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

    public function getSourceFile(): ?UploadedFile
    {
        return $this->sourceFile;
    }

    public function setSourceFile(?UploadedFile $file): static
    {
        $this->sourceFile = $file;

        return $this;
    }

    public function getSourcePath(): ?string
    {
        return $this->id && $this->source
            ? DIRECTORY_SEPARATOR . Config::MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $this->source
            : null;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getThumbnailFile(): ?UploadedFile
    {
        return $this->thumbnailFile;
    }

    public function setThumbnailFile(?UploadedFile $file): static
    {
        $this->thumbnailFile = $file;

        return $this;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->id && $this->thumbnail
            ? DIRECTORY_SEPARATOR . Config::MEDIA_DIRECTORY . DIRECTORY_SEPARATOR . $this->thumbnail
            : null;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function upload(): void
    {
        $hash = Uuid::v7()->toBase32();
        $sourceFileName = $this->move($this->sourceFile, $hash);
        if ($sourceFileName) {
            $this->setSource($sourceFileName);
        }

        $thumbnailFileName = $this->move($this->thumbnailFile, 't-' . $hash);
        if ($thumbnailFileName) {
            $this->setThumbnail($thumbnailFileName);
        }
    }

    private function move(?UploadedFile $file, $hash): ?string
    {
        if (null === $file) {
            return null;
        }

        $fileName = $hash . '.' . $file->guessExtension();
        $file->move(
            Config::MEDIA_DIRECTORY,
            $fileName
        );

        return $fileName;
    }
}
