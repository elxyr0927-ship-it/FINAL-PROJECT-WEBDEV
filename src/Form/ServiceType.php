<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Service name'])
            ->add('description', TextareaType::class, ['attr' => ['rows' => 3]])
            ->add('capacity', IntegerType::class, ['attr' => ['min' => 0]])
            ->add('basePrice', NumberType::class, [
                'input' => 'string',
                'scale' => 2,
                'attr' => ['min' => 0, 'step' => '0.01'],
            ])
            ->add('pricePerKg', NumberType::class, [
                'input' => 'string',
                'scale' => 2,
                'label' => 'Price per kg',
                'attr' => ['min' => 0, 'step' => '0.01'],
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Service is active',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
