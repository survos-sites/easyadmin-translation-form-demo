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

class ArticleCrudController extends AbstractCrudController
{
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
            ->add(TranslatableTextFilter::new('title'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield IdField::new('title')->hideOnForm();


        yield TranslationsField::new('translations')
            ->addTranslatableField(
                TextField::new('title')->setRequired(true)->setHelp('Help message for title')->setColumns(6)
            )
            ->addTranslatableField(
                SlugField::new('slug')->setTargetFieldName('title')->setRequired(true)->setHelp('Help message for slug')->setColumns(6)
            )
            ->addTranslatableField(
                TextEditorField::new('body')->setRequired(true)->setHelp('Help message for body')->setNumOfRows(6)->setColumns(12)
            )
        ;
    }
}
