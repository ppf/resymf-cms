<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Theme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Theme entity
 *
 * Handles theme creation and editing for UI customization
 */
class ThemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Theme Name',
                'attr' => [
                    'placeholder' => 'Enter theme name',
                    'class' => 'form-control',
                    'maxlength' => 50,
                ],
                'help' => 'Unique theme name (3-50 characters)',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'Describe this theme',
                    'maxlength' => 255,
                ],
                'help' => 'Optional description of the theme',
            ])
            ->add('primaryColor', ColorType::class, [
                'label' => 'Primary Color',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-color',
                ],
                'help' => 'Primary color for the theme (hex format)',
            ])
            ->add('secondaryColor', ColorType::class, [
                'label' => 'Secondary Color',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-color',
                ],
                'help' => 'Secondary color for the theme (hex format)',
            ])
            ->add('stylesheet', TextType::class, [
                'label' => 'Custom Stylesheet',
                'required' => false,
                'attr' => [
                    'placeholder' => 'themes/custom.css',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'help' => 'Path to custom CSS file (relative to public/)',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Whether this theme is currently active',
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'Set as Default',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Set this as the default theme for new users',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Theme::class,
        ]);
    }
}
