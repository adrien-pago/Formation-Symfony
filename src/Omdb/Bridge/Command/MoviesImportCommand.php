<?php

namespace App\Omdb\Bridge\Command;

use App\Entity\Movie as MovieEntity;
use App\Omdb\Bridge\DatabaseImporterInterface;
use App\Omdb\Client\ApiConsumerInterface;
use App\Omdb\Client\Model\SearchResult;
use App\Omdb\Client\NoResult;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function array_reduce;
use function count;
use function sprintf;

#[AsCommand(
    name: 'app:movies:import',
    description: 'Import movies from OMDB API onto the database.',
    aliases: [
        'omdb:movies:import',
        'movies:import',
    ],
)]
class MoviesImportCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface    $entityManager,
        private readonly ApiConsumerInterface      $omdbApi,
        private readonly DatabaseImporterInterface $databaseImporter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id-or-title', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'IMDB ID or title to search.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Will not import into database. Only displays what would happen.')
            ->setHelp(<<<'EOT'
                The <info>%command.name%</info> import movies data from OMDB API to database :
                
                Using only titles
                    <info>php %command.full_name% "title 1" "title 2"</info>
                
                Using only ids
                    <info>php %command.full_name% "ttid1" "ttid2"</info>
                
                Or mixing both
                    <info>php %command.full_name% "ttid1" "title 2"</info>
                EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import movies from OMDB');

        /** @var list<string> $idOrTitles */
        $idOrTitles = $input->getArgument('id-or-title');
        $io->writeln(sprintf('Trying to import %d movies into the database.', count($idOrTitles)));

        /** @var list<array{string, MovieEntity}> $success
         */
        $success = [];

        /** @var list<array{string, Throwable}> $error */
        $error = [];

        foreach ($idOrTitles as $idOrTitle) {
            try {
                $movieEntity = $this->tryImport($io, $idOrTitle);
                $success[] = [$idOrTitle, $movieEntity];
            } catch (Throwable $throwable) {
                $error[] = [$idOrTitle, $throwable];
            }
        }

        // save to database if not dry-run
        $isDryRun = $input->getOption('dry-run');

        if (false === $isDryRun) {
            $io->info(' >>>> Saving to database <<<<');
            $this->entityManager->flush();
        } else {
            $io->warning('`--dry-run` prevents the save to database.');
        }

        if ([] !== $success) {
            $io->success('The following movies were imported.');
            $io->table(
                ['ID', 'Title', 'Query'],
                array_reduce($success, static function (array $rows, array $success) {
                    /** @var array{string, MovieEntity} $success */
                    [$query, $movieEntity] = $success;

                    $rows[] = [
                        $movieEntity->getId(),
                        "{$movieEntity->getTitle()} ({$movieEntity->getReleasedAt()->format('Y')})",
                        $query,
                    ];

                    return $rows;
                }, [])
            );
        }

        if ([] !== $error) {
            $io->warning('The following terms were not conclusive.');
            $io->table([
                'Query',
                'Reason',
            ], array_reduce($error, static function (array $rows, array $error): array {
                /** @var array{string, Throwable} $error */
                [$query, $throwable] = $error;

                $rows[] = [
                    $query,
                    $throwable->getMessage()
                ];

                return $rows;
            }, []));
        }

        return Command::SUCCESS;
    }

    private function tryImport(SymfonyStyle $io, string $idOrTitle): MovieEntity
    {
        $io->section("Trying >>> {$idOrTitle}");

        try {
            $movieEntity = $this->tryImportAsImdbId($io, $idOrTitle);
        } catch (NoResult) {
            $movieEntity = $this->searchAndImportFromTitle($io, $idOrTitle);
        }

        return $movieEntity;
    }

    private function tryImportAsImdbId(SymfonyStyle $io, string $imdbId): MovieEntity
    {
        $movieOmdb = $this->omdbApi->getByImdbId($imdbId);

        return $this->databaseImporter->import($movieOmdb, false);
    }

    private function searchAndImportFromTitle(SymfonyStyle $io, string $title): MovieEntity
    {
        $searchResults = $this->omdbApi->searchByTitle($title);

        $choices = array_reduce($searchResults, static function (array $choices, SearchResult $searchResult): array {
            $choices[$searchResult->imdbId] = "{$searchResult->Title} ({$searchResult->Year})";

            return $choices;
        }, []);
        $choices['none'] = 'None of the above.';

        $selectedChoice = $io->choice('Which movie would you like to import ?', $choices);

        if ('none' === $selectedChoice) {
            throw new Exception('None of the candidates were selected.');
        }

        return $this->tryImportAsImdbId($io, $selectedChoice);
    }
}
