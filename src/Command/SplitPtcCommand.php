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

#[AsCommand('app:ptc-split', 'Download and split Parallel Translation Corpus Archive into directories and files')]
final class SplitPtcCommand extends InvokableServiceCommand
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
        $dir = 'data/ptc/pairs';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        // where the translations go as files.
        $fileDir = 'data/ptc/files';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = "data/ptc/archive.zip";
        if (0) {
            assert(file_exists($filename), "Missing $filename");
            if (!file_exists($filename)) {
                $this->download($url, $filename);
            }

        }
//            if (!file_exists($dir . '/txt')) {
                // now unzip it, either in php or in exec
//            }

        // @todo: rename the bad 'EN -NL' dir and file, delete Sample

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
        $prevCommon = null;
        $doc = [];
        foreach ($locales as $locale) {
            $locale = trim(trim($locale, '-'));
            $path = $dir . "/EN-$locale/EN-$locale.txt";
            $this->io()->writeln("Adding $path...");
            assert(file_exists($path), "missing $path");
            try {
                $csv = Reader::createFromPath($path, 'r')->setDelimiter("\t");
                $records = $csv->getRecords();
            } catch (\Exception $e) {
                $this->io()->error($e->getMessage());
                return self::FAILURE;
            }
            // @todo: custom headers in csv
//            $csv->mapHeader(['common','source','target','tmx','path','fn','id','status']);
//            $csv->setHeaderOffset(0);
            foreach ($records as $idx => $record) {
                array_walk($record, 'trim');
                [$source, $target, $common, $tmx, $path, $fn, $id, $status] = $record;

                // this is really the internal filename
                $common = str_replace('COMMON ATTRIBUTES -- ', '', $common);
                $common = str_replace('Txt::Doc. No.:', '', $common);
                $common = trim($common);

                // if we're at a new doc
                if ($prevCommon !== $common) {
                    // and it's not the first record.
                    if ($prevCommon) {
//                        $prevKey = (new AsciiSlugger())->slug($prevCommon)->toString();
                        // just dump the files
//                        dd($prevKey, $doc[$prevKey]);
                        if (!file_exists($x = $fileDir . "/" . $prevCommon)) {
                            mkdir($x, 0777, true);
                        }
                        foreach ($doc as $l => $lines) {
//                            if (!file_exists($l)) {
                            file_put_contents($x . "/$l.txt", join("\n", $lines));
//                            }
                        }
                        $doc = [];
                    }
                    $prevCommon = $common;
                }
                // now process the individual lines by appending the source and translation.
                $doc['en'][] = $source;
                $doc[strtolower($locale)][] = $target;
                if ($limit && ($idx >= $limit - 1)) {
                    break;
                }
            }
            // for end.
            foreach ($doc as $l => $lines) {
                file_put_contents($x . "/$l.txt", join("\n", $lines));
            }

        }
        $this->io()->success("done: ");
        return self::SUCCESS;
    }

    private function download(string $url, string $filename)
    {
//        https://medium.com/@a.marakhin2077/creating-a-file-download-in-symfony-5-from-remote-url-d2a51b1cf547
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
