<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Doc;
use App\Repository\ArticleRepository;
use App\Repository\DocRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

#[AsCommand('app:ptc-import', 'Import the Parallel Translation Corpus Files into Doc entities')]
final class ImportPtcCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocRepository          $docRepository,
        private HttpClientInterface    $httpClient,
        private string                 $speaker = '~',
        private array                  $seen = [], // to avoid duplicated within a batch
    )
    {
        parent::__construct('app:euro');

    }

    public function __invoke(
        IO     $io,
        #[Option(description: 'limit the number of records')]
        int    $limit = 50,
        #[Option(description: 'batch size for flush')]
        int    $batch = 5,
    ): int
    {
        $dir = 'data/ptc';
        if (!file_exists($dir)) {
            $this->io()->error("Run app:ptc-split first");
            return self::FAILURE;
        }
        // where the translations go as files.
        $fileDir = 'data/ptc-files';
        if (!file_exists($dir)) {
            $this->io()->error("Run app:ptc-split first");
            return self::FAILURE;
        }

        $finder = (new Finder())->directories()->in($fileDir);
        $locales = [];
        $progressBar = new ProgressBar($io, $limit ?: $finder->count());
        $progressBar->start();
        foreach ($finder as $idx => $dir) {
            $progressBar->advance();
            $name = $dir->getBasename();
            if (!$document = $this->docRepository->find($name)) {
                $document = new Doc($name);
                $document->setFilename($name);
                $this->entityManager->persist($document);
            }
            // now the translations
            foreach ((new Finder())->files()->in($dir) as $file) {
                $content = $file->getContents();
                // get the title from the first lines
                $lines = explode("\n", $content);
//                $title = array_shift($lines);
                $title = substr($lines[0], 0, 40);
                assert($title);
                $locale = $file->getBasename('.txt');
                $document->translate($locale)->setTitle($title);
                $document->translate($locale)->setBody($content);
            }
            $document->mergeNewTranslations();
            if (($progressBar->getProgress() % $batch) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            if ($limit && ($progressBar->getProgress() + 1 >= $limit)) {
                break;
            }
        }
        $progressBar->finish();
        $this->entityManager->flush();
        $this->io()->success("done: " . $this->docRepository->count());
        return self::SUCCESS;
    }
}
