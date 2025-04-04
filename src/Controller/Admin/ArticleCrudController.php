<?php

namespace App\Controller\Admin;

use App\EasyAdmin\Field\TranslationsField;
use App\EasyAdmin\Filter\TranslatableTextFilter;
use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;


use Symfony\Contracts\Translation\TranslatorInterface;

class ArticleCrudController extends AbstractCrudController
{
    private $translator;
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
//            ->setPageTitle(Crud::PAGE_INDEX, '%entity_label_plural% listing')
            ->setPageTitle(Crud::PAGE_INDEX, 'articles')
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            // @todo: key is exact only!
            ->add(TextFilter::new('key'))
            // this could be really slow!
            ->add(TranslatableTextFilter::new('title'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('key')->hideOnForm();
        yield IdField::new('author')->setMaxLength(60);
        yield IdField::new('title')->hideOnForm()->setColumns(5)->setMaxLength(60);

        yield TranslationsField::new('translations')
            ->addTranslatableField(
                TextField::new('title')->setRequired(true)
            )
            ->addTranslatableField(
                SlugField::new('slug')->setTargetFieldName('title')->setRequired(true)->setColumns(6)
            )
            ->addTranslatableField(
                TextEditorField::new('body')->setRequired(true)->setNumOfRows(6)->setColumns(12)
            )
        ;
    }

    public function trans($text,$locale = "en") {
        return $this->translator->trans($text,[],null,$locale);
    }
}
