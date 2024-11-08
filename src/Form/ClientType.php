<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomClient', TextType::class, [
                'attr' => ['placeholder' => 'nom complet du client']
                ])
            ->add('adresseClient', TextType::class, [
                'attr' => ['placeholder' => 'adresse du client']
                ])
            ->add('telephone', TextType::class, [
                'attr' => ['placeholder' => 'telephone']
                ])
            ->add('villeResidance', TextType::class, [
                'attr' => ['placeholder' => 'ville de residance']
                ])
            ->add('adresseEmail', EmailType::class, [
                'attr' => ['placeholder' => 'email']
                ] )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
        ]);
    }
}
