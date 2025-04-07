<?php

namespace App\Controller\Admin;

use App\EasyAdmin\Field\TranslationsField;
use App\Entity\Message;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Message::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('textKey', 'key');
        yield TextField::new('text', 'text')->hideOnForm();

        yield TranslationsField::new('translations')
            ->addTranslatableField(
                TextField::new('text')->setRequired(true)->setColumns(6)
            );
    }


}
