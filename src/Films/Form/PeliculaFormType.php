<?php

namespace App\Form;

use App\Entity\Pelicula;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeliculaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titulo', TextType::class)
            ->add('director', TextType::class)
            ->add('duracion', NumberType::class,[
                'empty_data' => 0,
                'html5' => true
            ])
            ->add('genero', TextType::class)
            ->add('sinopsis', TextareaType::class, [
                'required' => false
            ])
            ->add('estreno', NumberType::class, [
                'empty_data' => NULL,
                'html5' => true,
                'required' => false
            ])
            ->add('valoracion', NumberType::class, [
                'empty_data' => NULL,
                'html5' => true,
                'required' => false
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'data_class' => NULL,
                'constraints' => [
                    new File([
                        'maxSize' => '10240k',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Debes subir una imagen png, jpg o gif'
                    ])
                ]
            ])
            ->add('Guardar', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary my-3']
            ])->getForm();

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pelicula::class,
        ]);
    }
}
