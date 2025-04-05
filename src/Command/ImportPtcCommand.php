<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Doc;
use App\Repository\ArticleRepository;
use App\Repository\DocRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Survos\CoreBundle\Service\SurvosUtils;
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
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand('app:ptc-import', 'Import the Parallel Translation Corpus Files into Doc entities')]
final class ImportPtcCommand extends InvokableServiceCommand
{
    use RunsCommands;
    use RunsProcesses;

    public function __construct(
        private EntityManagerInterface      $entityManager,
        private DocRepository               $docRepository,
        private HttpClientInterface         $httpClient,
        private readonly ValidatorInterface $validator,
        private string                      $speaker = '~',
        private array                       $seen = [], // to avoid duplicated within a batch
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
        #[Option(description: 'limit the number of records')]
        ?bool $validate=null
    ): int
    {
        $validate ??= false;
        $dir = 'data/ptc';
        if (!file_exists($dir)) {
            $this->io()->error("Run app:ptc-split first");
            return self::FAILURE;
        }
        // where the translations go as files.
        $fileDir = 'data/ptc/files';
        if (!file_exists($dir)) {
            $this->io()->error("Run app:ptc-split first");
            return self::FAILURE;
        }

        $finder = (new Finder())->directories()->in($fileDir);
        $progressBar = SurvosUtils::createProgressBar($io, $limit ?: $finder->count());
        $progressBar->start();
        // the DOCUMENT finder (per directory)
        foreach ($finder as $idx => $dir) {
            $progressBar->advance();
            $name = $dir->getBasename();

            $localeFinder = (new Finder())->files()->in($dir);
            if ($localeFinder->count() === 0) {
                $this->io()->warning("No files in $name " . $dir->getRealPath());
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
            if (($progressBar->getProgress() % $batch) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
            if ($limit && ($progressBar->getProgress() >= $limit)) {
                break;
            }
        }
        $progressBar->finish();
        $this->entityManager->flush();
        $this->io()->success("done: " . $this->docRepository->count());
        return self::SUCCESS;
    }
}
