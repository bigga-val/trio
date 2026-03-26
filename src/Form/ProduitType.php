<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\User;
use App\Entity\CategorieVehicule;
use App\Entity\Devise;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Constraints\All as AllConstraint;


class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomProduit', TextType::class, [
                 'attr' => ['placeholder' => 'Entrez le nom du vehicule']
            ])
            ->add('prixProduit', TextType::class, [
                'attr' => ['placeholder' => 'Entrez le prix du vehicule']
                ]
                )
            ->add('descrition', TextType::class, [
                'attr' => ['placeholder' => 'Entrez la description du vehicule']
                ])
            ->add('ImageProduit',FileType::class, [
                'label'=> 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new FileConstraint([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WebP).',
                    ])
                ]
            ])
            ->add('images', FileType::class, [
                'label' => 'Images supplémentaires',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'constraints' => [
                    new AllConstraint([
                        'constraints' => [
                            new FileConstraint([
                                'maxSize' => '5M',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/png',
                                    'image/webp',
                                ],
                                'mimeTypesMessage' => 'Chaque fichier doit être une image valide (JPEG, PNG, WebP).',
                            ])
                        ]
                    ])
                ]
            ])
            ->add('localisation',TextType::class, [
                'attr' => ['placeholder' => 'Entrez la localisation du vehicule']
                ])
            ->add('categorie', EntityType::class, [
                'class'=>CategorieVehicule::class,
                'choice_label'=>'nomCategorie',
            ])
            ->add('devise', EntityType::class, [
                'class'=>Devise::class,
                'choice_label'=>'designation',
            ])
            ->add('ceatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('createdBy', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
