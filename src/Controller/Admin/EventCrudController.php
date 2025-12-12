<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Event::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            DateTimeField::new('date')->setFormat('dd/MM/yyyy HH:mm'),
            TextEditorField::new('description')->hideOnIndex()->setEmptyData("Pas de description fournie."),
            ImageField::new('image')->setUploadDir('public/img/')->setBasePath('/img/')->setRequired(false),
            AssociationField::new('participants', 'Nombre de participants')->setFormTypeOptions(['by_reference' => false,]),
            ArrayField::new('participants')->hideOnForm(),
        ];
    }

}
