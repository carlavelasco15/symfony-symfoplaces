<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Repository\PeliculaRepository;
use App\Entity\Pelicula;

class ActorAddPeliculaFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pelicula', EntityType::class, [
                    'class' => Pelicula::class,
                    'choice_label' => 'titulo',
                    'query_builder' => function(PeliculaRepository $er) {
                        return $er->createQueryBuilder('a')->orderBy('a.titulo', 'ASC');
                    }
                ])
            ->add('Add', SubmitType::class, [
                'label' => 'Añadir',
                'attr' => ['class' => 'btn btn-success my-3']
            ])
            ->setAction($options['action']);
        }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NULL,
        ]);
    }
}
