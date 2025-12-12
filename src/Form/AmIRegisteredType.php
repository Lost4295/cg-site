<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AmIRegisteredType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('going', CheckboxType::class, [
                'label'    => 'Je participe à cet événement',
                'required' => false,
                'mapped'   => false,
                'row_attr' => ['class' => 'form-check form-switch'],
                'attr'     => ['class' => 'form-check-input'],
            ])
        ;
    }
}
