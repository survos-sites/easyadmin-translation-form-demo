<?php

namespace App\Entity;

use App\Repository\DocTranslationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Length;

#[ORM\Entity(repositoryClass: DocTranslationRepository::class)]
//#[UniqueConstraint(name: "locale_doc_uidx", columns: ['locale', 'id'])]
//#[UniqueEntity(['locale', 'id'])]
class DocTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Length(min: 3)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(nullable: true)]
    private ?int $characterCount = null;


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;
        $this->setCharacterCount(strlen($body));
        return $this;
    }

    public function getCharacterCount(): ?int
    {
        return $this->characterCount;
    }

    public function setCharacterCount(?int $characterCount): static
    {
        $this->characterCount = $characterCount;

        return $this;
    }
}
