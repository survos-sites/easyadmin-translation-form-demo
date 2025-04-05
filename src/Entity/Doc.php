<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use App\Repository\DocRepository;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocRepository::class)]
#[ORM\Index(name: 'line_count_index', columns: ['line_count'])]
class Doc implements TranslatableInterface
{
    use TranslatableTrait;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private ?string $key = null
    )
    {
    }

    public function setKey(?string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column]
    #[Assert\NotNull()]
    #[Assert\GreaterThan(0)]
    private int $lineCount = -2;

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
        if (!$this->key) {
            $this->key = new AsciiSlugger()->slug($filename);
        }
    }


    public function getTitle(?string $locale=null): ?string
    {
        $locale ??= $this->getCurrentLocale();
        return PropertyAccess::createPropertyAccessor()->getValue($this->translate($locale), 'title');
//        return $this->translate($locale)->getTitle();
    }


    public function __get($name): mixed
    {
        return PropertyAccess::createPropertyAccessor()->getValue($this->translate(), $name);
    }

    public function getLineCount(): ?int
    {
        return $this->lineCount;
    }

    public function setLineCount(int $lineCount): static
    {
        $this->lineCount = $lineCount;

        return $this;
    }

}
