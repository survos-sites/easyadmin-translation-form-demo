<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Doc;
use App\Repository\ArticleRepository;
use App\Repository\DocRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\CoreBundle\Service\SurvosUtils;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Command\Command;

#[AsCommand('app:ptc:import', 'Import the Parallel Translation Corpus Files into Doc entities', help: <<<END
After running app:ptc:split, the files...
END
)]
final class PtcImportCommand
{

    public function __construct(
        private EntityManagerInterface      $entityManager,
        private DocRepository               $docRepository,
        private readonly ValidatorInterface $validator,
        private string                      $speaker = '~',
        private array                       $seen = [], // to avoid duplicated within a batch
    )
    {
    }

    public function __invoke(
        SymfonyStyle     $io,
        #[Option(description: 'limit the number of records')]
        int    $limit = 50,
        #[Option(description: 'batch size for flush')]
        int    $batch = 5,
        #[Option(description: 'validate the entity before persisting')]
        ?bool $validate=null
    ): int
    {
        $validate ??= false;
        $dir = 'data/ptc';
        if (!file_exists($dir)) {
            $io->error("Run app:ptc-split first");
            return Command::FAILURE;
        }
        // where the translations go as files.
        $fileDir = 'data/ptc/files';
        if (!file_exists($dir)) {
            $io->error("Run app:ptc-split first");
            return Command::FAILURE;
        }

        $finder = (new Finder())->directories()->in($fileDir);
//        $progressBar = SurvosUtils::createProgressBar($io, $limit ?: $finder->count());
//        $progressBar->start();
        // the DOCUMENT finder (per directory)
        foreach ($io->progressIterate($finder, $limit ?: $finder->count()) as $idx=>$dir)
        {
            $progressBar = $io->createProgressBar($batch);
//            foreach ($finder as $idx => $dir) {
//            $progressBar->advance();
            $name = $dir->getBasename();

            $localeFinder = (new Finder())->files()->in($dir);
            if ($localeFinder->count() === 0) {
                $io->warning("No files in $name " . $dir->getRealPath());
                continue;
            }

            if (!$document = $this->docRepository->find($name)) {
                $document = new Doc($name);
                $document->setFilename($name);
                $document->setLineCount(-1);
                $this->entityManager->persist($document);
            }
            // now the translations
            foreach ($localeFinder as $file) {
                $content = $file->getContents();
                // get the title from the first lines
                $lines = explode("\n", $content);
//                $title = array_shift($lines);
                // @todo: smarter word break.
                $title = substr($lines[0], 0, 60);
                assert($title);
                // could make only when locale=='en', should be the same for all.
                $document->setLineCount(count($lines));
                assert($document->getLineCount());
                $locale = $file->getBasename('.txt');
                $document->translate($locale)->setTitle($title);
                $document->translate($locale)->setBody($content);
            }
            $document->mergeNewTranslations();
            if ($validate) {
                $errors = $this->validator->validate($document);
                if (count($errors) > 0) {
                    foreach ($errors as $error) {
                        dd($error);
                    }
                }
            }

            if (($idx % $batch) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            if ($limit && ($idx >= $limit)) {
                break;
            }
        }
        $this->entityManager->flush();
        $io->success("done: " . $this->docRepository->count());
        return Command::SUCCESS;
    }
}
