<?php

namespace App\Form;

use App\Entity\Movie;
use App\Entity\Actor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;


class MovieFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class,[
                'attr' => array(
                    'class' => 'my-2 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                    'placeholder' => 'Enter title'
                ),
                'label' => false,
                'required' => false
            ])
            ->add('releaseYear', IntegerType::class,[
                'attr' => array(
                    'class' => 'my-2 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                    'placeholder' => 'Enter Release Year'
                ),
                'label' => false,
                'required' => false
            ])
            ->add('description', TextAreaType::class,[
                'attr' => array(
                    'class' => 'my-2 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                    'placeholder' => 'Enter description'
                ),
                'label' => false,
                'required' => false
            ])
            ->add('imagePath', FileType::class,array(
                'required' => false,
                'mapped' => false
            ))
            ->add('actors', EntityType::class, [
                'class' => Actor::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false, // true pour des checkboxes
                'attr' => [
                    'class' => 'my-2 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                ]
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Movie::class,
        ]);
    }
}
