<?php

namespace App\Form;

use App\Entity\Genre;
use App\Entity\Movie;
use App\Model\Rating;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MovieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug')
            ->add('title')
            ->add('releasedAt', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('plot')
            ->add('rated', EnumType::class, [
                'class' => Rating::class,
                'choice_label' => 'value',
                'multiple' => false,
                'expanded' => true,
            ])
            ->add('poster')
            ->add('genres', EntityType::class, [
                'class' => Genre::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Movie::class,
        ]);
    }
}
