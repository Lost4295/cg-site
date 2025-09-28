<?php

namespace App\Form;

use App\Entity\BlockedUser;
use App\Entity\Participation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompleteFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom',TextType::class, [
                "attr"=>["class"=>"form-control"]

            ])
            ->add('prenom',TextType::class, [
                "attr"=>["class"=>"form-control"]

            ])
            ->add('classe',TextType::class, [
                "attr"=>["class"=>"form-control"]

            ])
            ->add('visibility', CheckboxType::class, [
                "label"=> "Est-ce que votre compte est visible de tous ?",
                "required" => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
