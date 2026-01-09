<?php

namespace App\Form;

use App\Entity\BlockedUser;
use App\Entity\Participation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompleteFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                "attr" => ["class" => "form-control"]

            ])
            ->add('prenom', TextType::class, [
                "attr" => ["class" => "form-control"]

            ])
            ->add('classe', ChoiceType::class, [
                "attr" => ["class" => "form-control"],
                "expanded" => false,
                "multiple" => false,
                "placeholder" => "Sélectionnez votre classe",
                "choices" => [
                    '1️⃣ Première année Alternance Groupe 1' => '1A1',
                    '1️⃣ Première année Alternance Groupe 2' => '1A2',
                    '1️⃣ Première année Alternance Groupe 3' => '1A3',
                    '1️⃣ Première année Alternance Groupe 4' => '1A4',
                    '1️⃣ Première année Alternance Groupe 5' => '1A5',
                    '1️⃣ Première année Alternance Groupe 6' => '1A6',
                    '1️⃣ Première année Alternance Groupe 7' => '1A7',
                    '1️⃣ Première année Initial Groupe 1' => '1I1',
                    '1️⃣ Première année Initial Groupe 2' => '1I2',
                    '1️⃣ Première année Initial Janvier' => '1IJ',
                    '1️⃣ Première année Alternance Janvier Groupe 1' => '1AJ1',
                    '1️⃣ Première année Alternance Janvier Groupe 2' => '1AJ2',
                    '2️⃣ Deuxième année Alternance Groupe 1' => '2A1',
                    '2️⃣ Deuxième année Alternance Groupe 2' => '2A2',
                    '2️⃣ Deuxième année Alternance Groupe 3' => '2A3',
                    '2️⃣ Deuxième année Alternance Groupe 4' => '2A4',
                    '2️⃣ Deuxième année Alternance Groupe 5' => '2A5',
                    '2️⃣ Deuxième année Initial Groupe 1' => '2I1',
                    '2️⃣ Deuxième année Initial Groupe 2' => '2I2',
                    '2️⃣ Deuxième année MCSI' => '2MCSI',
                    '2️⃣ Deuxième année Alternance Janvier Groupe 1' => '2AJ1',
                    '2️⃣ Deuxième année Alternance Janvier Groupe 2' => '2AJ2',
                    '3️⃣ Troisième année AL Groupe 1' => '3AL1',
                    '3️⃣ Troisième année AL Groupe 2' => '3AL2',
                    '3️⃣ Troisième année IABD Groupe 1' => '3IABD1',
                    '3️⃣ Troisième année IABD Groupe 2' => '3IABD2',
                    '3️⃣ Troisième année MOC' => '3MOC',
                    '3️⃣ Troisième année IBC' => '3IBC',
                    '3️⃣ Troisième année IW Groupe 1' => '3IW1',
                    '3️⃣ Troisième année IW Groupe 2' => '3IW2',
                    '3️⃣ Troisième année MCSI Groupe 1' => '3MCSI1',
                    '3️⃣ Troisième année MCSI Groupe 2' => '3MCSI2',
                    '3️⃣ Troisième année RVJV' => '3RVJV',
                    '3️⃣ Troisième année SI Groupe 1' => '3SI1',
                    '3️⃣ Troisième année SI Groupe 2' => '3SI2',
                    '3️⃣ Troisième année SI Groupe 3' => '3SI3',
                    '3️⃣ Troisième année SI Groupe 4' => '3SI4',
                    '3️⃣ Troisième année SI Groupe 5' => '3SI5',
                    '3️⃣ Troisième année SRC Groupe 1' => '3SRC1',
                    '3️⃣ Troisième année SRC Groupe 2' => '3SRC2',
                    '3️⃣ Troisième année SRC Groupe 3' => '3SRC3',
                    '3️⃣ Troisième année SRC Groupe 4' => '3SRC4',
                    '3️⃣ Troisième année SRC Groupe 5' => '3SRC5',
                    '3️⃣ Troisième année IABD Janvier' => '3IABDJ',
                    '3️⃣ Troisième année SI Janvier' => '3SIJ',
                    '3️⃣ Troisième année SRC Janvier' => '3SRCJ',
                    '3️⃣ Troisième année IW Janvier' => '3IWJ',
                    '3️⃣ Troisième année MCSI Janvier' => '3MCSIJ',
                    '4️⃣ Quatrième année AL Groupe 1' => '4AL1',
                    '4️⃣ Quatrième année AL Groupe 2' => '4AL2',
                    '4️⃣ Quatrième année IABD Groupe 1' => '4IABD1',
                    '4️⃣ Quatrième année IABD Groupe 2' => '4IABD2',
                    '4️⃣ Quatrième année MOC' => '4MOC',
                    '4️⃣ Quatrième année IBC' => '4IBC',
                    '4️⃣ Quatrième année IW Groupe 1' => '4IW1',
                    '4️⃣ Quatrième année IW Groupe 2' => '4IW2',
                    '4️⃣ Quatrième année IW Groupe 3' => '4IW3',
                    '4️⃣ Quatrième année MCSI Groupe 1' => '4MCSI1',
                    '4️⃣ Quatrième année MCSI Groupe 2' => '4MCSI2',
                    '4️⃣ Quatrième année MCSI Groupe 3' => '4MCSI3',
                    '4️⃣ Quatrième année MCSI Groupe 4' => '4MCSI4',
                    '4️⃣ Quatrième année RVJV' => '4RVJV',
                    '4️⃣ Quatrième année SI Groupe 1' => '4SI1',
                    '4️⃣ Quatrième année SI Groupe 2' => '4SI2',
                    '4️⃣ Quatrième année SI Groupe 3' => '4SI3',
                    '4️⃣ Quatrième année SI Groupe 4' => '4SI4',
                    '4️⃣ Quatrième année SI Groupe 5' => '4SI5',
                    '4️⃣ Quatrième année SRC Groupe 1' => '4SRC1',
                    '4️⃣ Quatrième année SRC Groupe 2' => '4SRC2',
                    '4️⃣ Quatrième année SRC Groupe 3' => '4SRC3',
                    '4️⃣ Quatrième année SRC Groupe 4' => '4SRC4',
                    '4️⃣ Quatrième année SRC Groupe 5' => '4SRC5',
                    '4️⃣ Quatrième année IABD Janvier' => '4IABDJ',
                    '4️⃣ Quatrième année SI Janvier' => '4SIJ',
                    '4️⃣ Quatrième année SRC Janvier' => '4SRCJ',
                    '4️⃣ Quatrième année IW Janvier' => '4IWJ',
                    '4️⃣ Quatrième année MCSI Janvier' => '4MCSIJ',
                    '5️⃣ Cinquième année MOC' => '5MOC',
                    '5️⃣ Cinquième année IBC' => '5IBC',
                    '5️⃣ Cinquième année IW Groupe 1' => '5IW1',
                    '5️⃣ Cinquième année IW Groupe 2' => '5IW2',
                    '5️⃣ Cinquième année AL Groupe 1' => '5AL1',
                    '5️⃣ Cinquième année AL Groupe 2' => '5AL2',
                    '5️⃣ Cinquième année MCSI Groupe 1' => '5MCSI1',
                    '5️⃣ Cinquième année MCSI Groupe 2' => '5MCSI2',
                    '5️⃣ Cinquième année MCSI Groupe 3' => '5MCSI3',
                    '5️⃣ Cinquième année IABD Groupe 1' => '5IABD1',
                    '5️⃣ Cinquième année IABD Groupe 2' => '5IABD2',
                    '5️⃣ Cinquième année RVJV' => '5RVJV',
                    '5️⃣ Cinquième année SI Groupe 1' => '5SI1',
                    '5️⃣ Cinquième année SI Groupe 2' => '5SI2',
                    '5️⃣ Cinquième année SI Groupe 3' => '5SI3',
                    '5️⃣ Cinquième année SRC Groupe 1' => '5SRC1',
                    '5️⃣ Cinquième année SRC Groupe 2' => '5SRC2',
                    '5️⃣ Cinquième année SRC Groupe 3' => '5SRC3',
                    '5️⃣ Cinquième année SRC Groupe 4' => '5SRC4',
                    '5️⃣ Cinquième année IABD Janvier' => '5IABDJ',
                    '5️⃣ Cinquième année SI Janvier' => '5SIJ',
                    '5️⃣ Cinquième année SRC Janvier' => '5SRCJ',
                    '5️⃣ Cinquième année IW Janvier' => '5IWJ',
                    '5️⃣ Cinquième année MCSI Janvier' => '5MCSIJ',
                ]
            ])
            ->
            add('visibility', CheckboxType::class, [
                "label" => "Est-ce que votre compte est visible de tous ?",
                "required" => false,
                "row_attr" => ["class" => "form-check form-switch my-3"],
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
