<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
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

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
        if (!$this->key) {
            $this->key = (new AsciiSlugger())->slug($filename);
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

}
