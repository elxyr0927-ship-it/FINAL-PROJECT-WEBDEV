<?php

namespace App\Form;

use App\Entity\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [new NotBlank(), new Length(max: 100)],
                'attr' => ['maxlength' => 100, 'autocomplete' => 'name'],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [new NotBlank(), new Email(), new Length(max: 180)],
                'attr' => ['maxlength' => 180, 'autocomplete' => 'email'],
            ])
            ->add('subject', TextType::class, [
                'constraints' => [new NotBlank(), new Length(max: 255)],
                'attr' => ['maxlength' => 255],
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [new NotBlank(), new Length(max: 10000)],
                'attr' => ['rows' => 6, 'maxlength' => 10000],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContactMessage::class]);
    }
}
