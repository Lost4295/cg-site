<?php

namespace App\Form;

use App\Entity\GuildInformation;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAPIUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullname',
                'choice_value' => 'id',
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('u')
                        ->where('u.email IS NOT NULL')
                        ->andWhere('u.email != :empty')
                        ->setParameter('empty', '')
                        ->orderBy('u.prenom', 'ASC');
                },
                'label' => 'Utilisateur',
                'placeholder' => 'Sélectionnez un utilisateur',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('password', TextType::class, [
                'label' => 'Mot de passe',
                'attr' => ['class' => 'form-control mt-3'],
                'required' => true,
            ])
            ->add('guilds', EntityType::class, [
                'label' => 'Associations',
                'attr' => ['class' => 'form-select mt-3'],
                'expanded' => false,
                'multiple' => true,
                'class' => GuildInformation::class,
                'choice_label' => 'name',
                'choice_value' => 'guild_id',
            ])
        ->add('submit', SubmitType::class, [
                'label' => 'Soumettre',
                'attr' => ['class' => 'btn btn-primary mt-3'],
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
