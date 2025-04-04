<?php

namespace App\Repository;

use App\Entity\DocTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocTranslation>
 *
 * @method DocTranslation|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocTranslation|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocTranslation[]    findAll()
 * @method DocTranslation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocTranslation::class);
    }

}
