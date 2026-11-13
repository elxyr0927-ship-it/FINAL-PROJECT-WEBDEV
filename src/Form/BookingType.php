<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Service;
use App\Repository\ServiceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Booking $booking */
        $booking = $options['data'];
        $currentServiceId = $booking->getService()?->getId();

        $builder
            ->add('customerName', TextType::class, ['label' => 'Customer name'])
            ->add('customerEmail', EmailType::class, ['label' => 'Customer email'])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'name',
                'choice_attr' => static fn (Service $service): array => [
                    'data-base-price' => $service->getBasePrice(),
                    'data-price-per-kg' => $service->getPricePerKg(),
                ],
                'query_builder' => static function (ServiceRepository $repository) use ($currentServiceId) {
                    $query = $repository->createQueryBuilder('s')
                        ->andWhere('s.isActive = true');

                    if ($currentServiceId !== null) {
                        $query->orWhere('s.id = :currentService')
                            ->setParameter('currentService', $currentServiceId);
                    }

                    return $query->orderBy('s.name', 'ASC');
                },
            ])
            ->add('pickupRegion', ChoiceType::class, [
                'choices' => ['Luzon' => 'Luzon', 'Visayas' => 'Visayas', 'Mindanao' => 'Mindanao'],
            ])
            ->add('deliveryRegion', ChoiceType::class, [
                'choices' => ['Luzon' => 'Luzon', 'Visayas' => 'Visayas', 'Mindanao' => 'Mindanao'],
            ])
            ->add('pickupAddress', TextareaType::class, ['attr' => ['rows' => 2]])
            ->add('deliveryAddress', TextareaType::class, ['attr' => ['rows' => 2]])
            ->add('weight', NumberType::class, [
                'input' => 'string',
                'scale' => 2,
                'attr' => ['min' => '0.01', 'step' => '0.01'],
            ])
            ->add('totalCost', NumberType::class, [
                'input' => 'string',
                'scale' => 2,
                'mapped' => false,
                'required' => false,
                'label' => 'Calculated total cost (PHP)',
                'help' => 'The base price covers the first kilogram; additional weight uses the selected service rate.',
                'attr' => ['readonly' => true, 'step' => '0.01'],
            ]);

        if ($options['include_admin_fields']) {
            $builder
                ->add('status', ChoiceType::class, [
                    'choices' => Booking::statusChoices(
                        (string) $booking->getStatus(),
                        (string) $booking->getPaymentStatus(),
                    ),
                    'help' => 'Pending → Dispatched → Delivered. Dispatch is only available once payment is marked paid.',
                ])
                ->add('paymentStatus', ChoiceType::class, [
                    'label' => 'Payment status',
                    'choices' => Booking::paymentChoices(
                        (string) $booking->getPaymentStatus(),
                        (string) $booking->getStatus(),
                    ),
                    'help' => 'Paid is final; it cannot be reverted to unpaid.',
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'include_admin_fields' => false,
        ]);
        $resolver->setAllowedTypes('include_admin_fields', 'bool');
    }
}
