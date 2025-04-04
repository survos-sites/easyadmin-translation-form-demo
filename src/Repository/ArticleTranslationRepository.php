<?php

namespace App\Repository;

use App\Entity\ArticleTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleTranslation>
 *
 * @method ArticleTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleTranslation[]    findAll()
 * @method ArticleTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleTranslation::class);
    }

}
