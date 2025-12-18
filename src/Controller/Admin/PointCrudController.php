<?php

namespace App\Controller\Admin;

use App\Entity\Point;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PointCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Point::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            AssociationField::new('user', 'Utilisateur'),
            TextField::new('reason', 'Raison'),
            DateTimeField::new('date', 'Date')->formatValue(function ($value) {
                return $value ? $value->format('d/m/Y H:i') : '';
            }),
            NumberField::new('points', 'Points'),
        ];
    }
}
