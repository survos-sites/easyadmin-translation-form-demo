<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message implements TranslatableInterface
{
    use TranslatableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $domain = null;

    #[ORM\Column(length: 255)]
    private ?string $textKey = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function getText(?string $locale=null): ?string
    {
        $locale ??= $this->getCurrentLocale();
        return PropertyAccess::createPropertyAccessor()->getValue($this->translate($locale), 'text');
    }


    public function setText(string $text, ?string $locale=null): static
    {
        $this->translate($locale)->setText($text);
        return $this;
    }

    public function getTextKey(): ?string
    {
        return $this->textKey;
    }

    public function setTextKey(string $textKey): static
    {
        $this->textKey = $textKey;

        return $this;
    }
}
