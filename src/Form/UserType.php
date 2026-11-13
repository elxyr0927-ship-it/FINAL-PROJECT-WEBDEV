<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['data'];
        $roles = array_intersect($user->getRoles(), ['ROLE_ADMIN', 'ROLE_STAFF']);

        $builder
            ->add('username', TextType::class)
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('role', ChoiceType::class, [
                'mapped' => false,
                'choices' => [
                    'Administrator' => 'ROLE_ADMIN',
                    'Staff' => 'ROLE_STAFF',
                ],
                'data' => $roles[0] ?? 'ROLE_STAFF',
            ])
            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'label' => 'Account is active',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => $options['is_new'],
                'first_options' => [
                    'label' => $options['is_new'] ? 'Password' : 'New password (leave blank to keep current)',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirm password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'The password fields must match.',
                'constraints' => $options['is_new']
                    ? [new NotBlank(), new Length(min: 12, max: 4096)]
                    : [new Length(max: 4096)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_new' => false,
        ]);
        $resolver->setAllowedTypes('is_new', 'bool');
    }
}
