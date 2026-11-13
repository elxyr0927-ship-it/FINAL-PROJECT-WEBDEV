<?php

namespace App\Form;

use App\Entity\StatusHistory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CheckpointType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('checkpoint', TextType::class, ['label' => 'Hub or location'])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 3, 'maxlength' => 500],
                'label' => 'Rider or status note',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => StatusHistory::class]);
    }
}
