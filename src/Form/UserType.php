<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fileConstraint = new File([
            'maxSize' => '2048k',
            'mimeTypes' => [
                'image/jpeg',
                'image/png',
            ],
            'mimeTypesMessage' => 'Please upload a valid image. Allowed extensions are: .jpeg, .jpg, .png',
            'groups' => ['new']
        ]);

        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('password')
            ->add('avatar', FileType::class, [
                'mapped' => false,
                'constraints' => [$fileConstraint],
            ])
            ->add('photos', CollectionType::class, [
                'entry_type' => FileType::class,
                'allow_add' => true,
                'mapped' => false,
                'constraints' => [
                    new Count([
                        'min' => 4,
                        'minMessage' => 'Photos collection should contain at least 4 photos.',
                        'groups' => ['new']
                    ]),
                ],
                'entry_options' => [
                    'constraints' => [$fileConstraint],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'validation_groups' => ['new']
        ]);
    }
}
