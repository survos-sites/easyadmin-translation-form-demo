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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\Console\Attribute\Argument;
use Zenstruck\Console\Attribute\Option;
use Zenstruck\Console\InvokableServiceCommand;
use Zenstruck\Console\IO;
use Zenstruck\Console\RunsCommands;
use Zenstruck\Console\RunsProcesses;

#[AsCommand('app:ptc', 'Import the Parallel Translation Corpus into Doc entities')]
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
        #[Argument(description: 'url to tar')]
        string $url = 'https://www.statmt.org/europarl/v7/europarl.tgz',

        #[Option(description: 'limit the number of records')]
        int    $limit = 50,
        #[Option(description: 'batch size for flush')]
        int    $batch = 5,
    ): int
    {
        $dir = 'data/ptc';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
//        https://medium.com/@a.marakhin2077/creating-a-file-download-in-symfony-5-from-remote-url-d2a51b1cf547
        $filename = "data/ptc/archive.zip";
        assert(file_exists($filename), "Missing $filename");
        if (!file_exists($filename)) {
            $this->download($url, $filename);
        }
        if (0) // unzip
        if (!file_exists($dir . '/txt')) {
            // now untar it, either in php or in exec
            if (!file_exists($tarFile = str_replace('.tgz', '.tar', $filename))) {
                $io->warning("decompressing");
                $p = new \PharData($filename);
                $p->decompress(); // creates /path/to/my.tar
            }

            $io->warning("extracting");
            $phar = new \PharData($tarFile);
            $phar->extractTo($dir);
        }

        $finder = (new Finder())->directories()->in($dir);
        $locales = [];
        foreach ($finder as $localeDir) {
            $pair = $localeDir->getBasename();
            $locale = str_replace('EN', '', $pair);
            if (str_contains($locale, ' ')) {
                continue; // ignore NL because of space
            }
            if (!str_contains($locale, 'Sample')) {
                $locales[] = $locale; // -NL problem
            }
        }
        $prevTitle = null;
        $doc=[];
        foreach ($locales as $locale) {
            $locale = trim(trim($locale, '-'));
            $path = $dir . "/EN-$locale/EN-$locale.txt";
            assert(file_exists($path), "missing $path");
            $csv = Reader::createFromPath($path, 'r')->setDelimiter("\t");
//            $csv->mapHeader(['title','source','target','tmx','path','fn','id','status']);
//            $csv->setHeaderOffset(0);
            foreach ($csv->getRecords() as $idx => $record) {
                array_walk($record, 'trim');
                [$source, $target, $title, $tmx, $path, $fn, $id, $status] = $record;
                $title = trim($title);
                $title = str_replace('COMMON ATTRIBUTES -- ', '', $title);
                if ($prevTitle !== $title) {
                    if ($prevTitle) {
                        if (!$document = $this->docRepository->find($prevTitle)) {
                            $document=new Doc($prevTitle);
                            $document->setFilename($prevTitle);
                            $this->entityManager->persist($document);
                        }
                        foreach($doc[$prevTitle] as $l=>$lines) {
                            $title = array_shift($lines);
                            $document->translate($l)->setTitle($title);
                            $document->translate($l)->setBody(join("\n", $lines));
                        }
                        $document->mergeNewTranslations();
                        $this->entityManager->flush();

                    } else {
                    }
                    $prevTitle = $title;
                }
                $doc[$prevTitle]['en'][] = $source;
                $doc[$prevTitle][strtolower($locale)][] = $target;
                if ($idx>14) {
//                    dump(title: $title, source: $source, target: $target, id: $id);
                }
//                dd($title, $source, $target, $tmx, $path, $fn, $id, status: $status, record: $record);
                if ($idx >= $limit-1) {
                    dd($record, $doc);
                }
            }
            $this->entityManager->flush();
            dd();

            if ($localeDir<>'en') {
                $locales[] = $localeDir->getRelativePathname();
            }
        }
//        dd(join(',', $locales));
        $txtFinder = (new Finder())->in($dir . '/txt/en')->files()->name('*.txt');
        $progressBar = new ProgressBar($io, $txtFinder->count());
        $progressBar->start();
        foreach ($txtFinder as $idx => $file) {
            $progressBar->advance();
            $lines = []; // some translation files missing
            $lines['en'] = file($file->getRealPath());
            foreach ($locales as $locale) {
                $localeFilename = str_replace('/en/', "/$locale/", $file->getRealPath());
                if (file_exists($localeFilename)) {
                    $lines[$locale] = file($localeFilename);
                }
//                dd($lines[$locale], $locale);
            }
            $this->process($lines, $locales, $file->getBasename());
            if (($progressBar->getProgress() % $batch) === 0) {
                $this->entityManager->flush();
                $this->seen = [];
            }
            if (($progressBar->getProgress() >= $limit-1)) {
                break;
            }
        }
        $io->success($this->getName() . ' success.');

        return self::SUCCESS;
    }

    private function process(array $lines, array $locales, string $filename): ?Article
    {
        $article = null; // if no lines
        foreach ($lines['en'] as $idx=>$line) {
            // <SPEAKER ID="053" NAME="Jo Leinen  " AFFILIATION="PSE">
            if (preg_match('/SPEAKER ID="(\d*)" NAME="(.*?)"/', $line, $match)) {
                $this->speaker = trim($match[2]);
                // we _could_ do a related table, etc.
            }
            if ($this->isValid($line)) {
                $key = hash('xxh3', $line);
                if (in_array($key, $this->seen)) {
                    continue;
                }
                $title = substr($line, 0, 60);
                if (!$article = $this->docRepository->find($key)) {
                    $article=new Article($key);
                    $this->entityManager->persist($article);
                }
                $this->seen[] = $key;
                $article->setAuthor($this->speaker); // @todo: look for speaker?
                // now set each translation
                foreach ($locales as $locale) {
                    if (array_key_exists($locale, $lines)) {
                        $article->translate($locale)->setTitle($title . "- " .$locale);
                        $article->translate($locale)->setBody($lines[$locale][$idx]??null);
                    }
                }
                $article->mergeNewTranslations();
            }
        }
        return $article;
    }

    private function isValid(string $line): bool
    {
        if (str_starts_with($line, '<')) {
            return false;
        }
        return true;
    }

    private function download(string $url, string $filename)
    {
        // https://github.com/zizoujab/FileDownloadCommand
        $progressBar = new ProgressBar($this->io()->output(), 100);
        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use ($progressBar) {
                if ($dlSize && $dlNow > 0 ){
                    $progressBar->setProgress(intval($dlNow*100 / $dlSize));
                    if ($dlNow == $dlSize){
                        $progressBar->finish();
                    }
                }
            }
        ]);
        $filHandler = fopen($filename , 'w');
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($filHandler, $chunk->getContent());
        }

    }
}
