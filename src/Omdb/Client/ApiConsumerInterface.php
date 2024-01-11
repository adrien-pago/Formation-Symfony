<?php

declare(strict_types=1);

namespace App\Omdb\Client;

interface ApiConsumerInterface
{
    public function getByImdbId(string $imdbId): Movie;
}
