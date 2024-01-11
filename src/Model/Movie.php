<?php

namespace App\Model;

use App\Entity\Movie as MovieEntity;
use App\Entity\Genre as GenreEntity;
use App\Omdb\Client\Movie as MovieOmdb;
use DateTimeImmutable;
use Symfony\Component\Routing\Requirement\Requirement;
use function array_map;

final class Movie
{
    public const SLUG_FORMAT = '\d{4}-'.Requirement::ASCII_SLUG;

    /**
     * @param list<string> $genres
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $plot,
        public readonly string $poster,
        public readonly DateTimeImmutable $releasedAt,
        public readonly array $genres,
    ) {
    }

    public static function fromOmdb(MovieOmdb $movieOmdb): self
    {
        return new self(
            slug: '',
            title: $movieOmdb->Title,
            plot: $movieOmdb->Plot,
            poster: $movieOmdb->Poster,
            releasedAt: new DateTimeImmutable($movieOmdb->Released),
            genres: explode(', ', $movieOmdb->Genre),
        );
    }

    public static function fromEntity(MovieEntity $movieEntity): self
    {
        return new self(
            slug: $movieEntity->getSlug(),
            title: $movieEntity->getTitle(),
            plot: $movieEntity->getPlot(),
            poster: $movieEntity->getPoster(),
            releasedAt: $movieEntity->getReleasedAt(),
            genres: $movieEntity->getGenres()->map(
                static fn (GenreEntity $genreEntity): string => $genreEntity->getName()
            )->toArray(),
        );
    }

    /**
     * @param list<MovieEntity> $movieEntities
     *
     * @return list<self>
     */
    public static function fromEntities(array $movieEntities): array
    {
        return array_map(self::fromEntity(...), $movieEntities);
    }

    public function year(): string
    {
        return $this->releasedAt->format('Y');
    }

    public function isRemotePoster(): bool
    {
        return str_starts_with($this->poster, 'http');
    }
}
