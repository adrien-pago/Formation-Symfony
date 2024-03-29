<?php

namespace App\Entity;

use App\Model\Movie as MovieModel;
use App\Model\Rating;
use App\Repository\MovieRepository;
use App\Validator\Constraints\PosterValid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\UniqueConstraint(name: 'movie_unique_slug', fields: ['slug'])]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull()]
    #[Assert\Regex('#'.MovieModel::SLUG_FORMAT.'#')]
    #[Assert\Length(min: 7)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull()]
    #[Assert\Length(min: 2)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotNull()]
    #[Assert\Length(min: 20, max: 2000)]
    private ?string $plot = null;

    #[ORM\Column]
    #[Assert\NotNull()]
    #[Assert\GreaterThanOrEqual('1 Jan 1900')]
    #[Assert\LessThan('+100 years')]
    private ?\DateTimeImmutable $releasedAt = null;

    #[ORM\Column(length: 255)]
    #[PosterValid()]
    private ?string $poster = null;

    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'movies')]
    #[Assert\Count(min: 1)]
    private Collection $genres;

    #[ORM\Column(length: 6, enumType: Rating::class, options: ['default' => Rating::GeneralAudiences->value])]
    private Rating $rated = Rating::GeneralAudiences;

    public function __construct()
    {
        $this->genres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
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

    public function getPlot(): ?string
    {
        return $this->plot;
    }

    public function setPlot(string $plot): static
    {
        $this->plot = $plot;

        return $this;
    }

    public function getReleasedAt(): ?\DateTimeImmutable
    {
        return $this->releasedAt;
    }

    public function setReleasedAt(\DateTimeImmutable $releasedAt): static
    {
        $this->releasedAt = $releasedAt;

        return $this;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(string $poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        $this->genres->removeElement($genre);

        return $this;
    }

    public function getRated(): Rating
    {
        return $this->rated;
    }

    public function setRated(Rating $rated): static
    {
        $this->rated = $rated;

        return $this;
    }
}
