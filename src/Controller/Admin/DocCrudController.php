<?php

namespace App\Controller\Admin;

use App\EasyAdmin\Field\TranslationsField;
use App\EasyAdmin\Filter\TranslatableTextFilter;
use App\Entity\Doc;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class DocCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Doc::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('key'))
            // this could be really slow!
            ->add(TranslatableTextFilter::new('title'))
            ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('key')->hideOnForm();
        yield IdField::new('filename')->setMaxLength(60);
        yield IdField::new('title')->hideOnForm()->setColumns(5)->setMaxLength(60);

        yield TranslationsField::new('translations')
            ->addTranslatableField(
                TextField::new('title')->setRequired(true)->setColumns(6)
            )
            ->addTranslatableField(
                TextEditorField::new('body')->setRequired(true)->setNumOfRows(6)->setColumns(12)
            )
        ;
    }
}
