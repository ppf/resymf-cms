<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Category entity
 *
 * Handles category creation and editing for content organization
 */
class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Category Name',
                'attr' => [
                    'placeholder' => 'Enter category name',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
                'help' => 'Unique category name (2-100 characters)',
            ])
            ->add('slug', TextType::class, [
                'label' => 'URL Slug',
                'attr' => [
                    'placeholder' => 'category-slug',
                    'class' => 'form-control',
                    'maxlength' => 128,
                ],
                'help' => 'URL-friendly identifier (lowercase letters, numbers, and hyphens only)',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Describe this category',
                ],
                'help' => 'Optional description of the category',
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Display Order',
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'help' => 'Sort order for category lists (lower numbers appear first)',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Whether this category is currently active',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
