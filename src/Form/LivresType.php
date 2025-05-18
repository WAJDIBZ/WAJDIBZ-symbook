<?php

namespace App\Form;

use App\Entity\Livres;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;

class LivresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 15,
                        'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('isbn', TextType::class, [
                'label' => 'ISBN',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'ISBN est obligatoire.'])
                ]
            ])
            ->add('editeur', TextType::class, [
                'label' => 'Éditeur',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'éditeur est obligatoire.'])
                ]
            ])
            ->add('dateEdition', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date d\'édition',
                'constraints' => [
                    new Assert\NotNull(['message' => 'La date d\'édition est obligatoire.']),

                ]
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (DT)',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Le prix est obligatoire.']),
                    new Assert\Positive(['message' => 'Le prix doit être positif.']),
                    new Assert\LessThanOrEqual([
                        'value' => 1000,
                        'message' => 'Le prix ne peut pas dépasser {{ compared_value }} DT.'
                    ])
                ]
            ])
            ->add('image', TextType::class, [
                'label' => 'Image URL',
                'required' => false,

            ])
            ->add('resume', TextType::class, [
                'label' => 'Résumé',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Le résumé ne peut pas dépasser {{ limit }} caractères.'
                    ])
                ]
            ])
            ->add('Categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'libelle',
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie',
                'constraints' => [
                    new Assert\NotNull(['message' => 'Veuillez sélectionner une catégorie.'])
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livres::class,
        ]);
    }
}
