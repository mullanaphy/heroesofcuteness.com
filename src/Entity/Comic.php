<?php

namespace App\Entity;

use App\Repository\ComicRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Unique;

#[ORM\Entity(repositoryClass: ComicRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Comic
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column]
    private ?DateTimeImmutable $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTimeInterface $updated = null;

    #[ORM\ManyToOne(inversedBy: 'comics')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    /**
     * @var Collection<int, Panel>
     */
    #[ORM\OneToMany(targetEntity: Panel::class, mappedBy: 'comic', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $panels;

    #[ORM\OneToOne(mappedBy: 'comic', cascade: ['persist', 'remove'])]
    private ?Hidden $hidden = null;

    #[ORM\OneToOne(mappedBy: 'comic', cascade: ['persist', 'remove'])]
    private ?Search $search = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(nullable: true)]
    private ?bool $raw = null;

    public function __construct()
    {
        $this->panels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCreated(): ?DateTimeImmutable
    {
        return $this->created;
    }

    #[ORM\PrePersist]
    public function setCreated(): void
    {
        $this->created = new DateTimeImmutable();
        $this->updated = new DateTime();
    }

    public function getUpdated(): ?DateTimeInterface
    {
        return $this->updated;
    }

    #[ORM\PreUpdate]
    public function setUpdated(): void
    {
        $this->updated = new DateTime();
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection<int, Panel>
     */
    public function getPanels(): Collection
    {
        return $this->panels;
    }

    public function addPanel(Panel $panel): static
    {
        if (!$this->panels->contains($panel)) {
            $this->panels->add($panel);
            $panel->setComic($this);
        }

        return $this;
    }

    public function removePanel(Panel $panel): static
    {
        if ($this->panels->removeElement($panel)) {
            // set the owning side to null (unless already changed)
            if ($panel->getComic() === $this) {
                $panel->setComic(null);
            }
        }

        return $this;
    }

    public function getHidden(): ?Hidden
    {
        return $this->hidden;
    }

    public function setHidden(?Hidden $hidden): static
    {
        // unset the owning side of the relation if necessary
        if ($hidden === null && $this->hidden !== null) {
            $this->hidden->setComic(null);
        }

        // set the owning side of the relation if necessary
        if ($hidden !== null && $hidden->getComic() !== $this) {
            $hidden->setComic($this);
        }

        $this->hidden = $hidden;

        return $this;
    }

    public function getSearch(): ?Search
    {
        return $this->search;
    }

    public function setSearch(Search $search): static
    {
        // set the owning side of the relation if necessary
        if ($search->getComic() !== $this) {
            $search->setComic($this);
        }

        $this->search = $search;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
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

    public function __toString(): string
    {
        return $this->title ?? 'Untitled Comic';
    }
}
