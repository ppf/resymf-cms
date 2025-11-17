<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Page entity.
 *
 * Handles CMS page creation and editing with categories, SEO fields, and publishing options
 */
class PageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Page Title',
                'attr' => [
                    'placeholder' => 'Enter page title',
                    'class' => 'form-control',
                ],
                'help' => 'The main title of the page (3-255 characters)',
            ])
            ->add('slug', TextType::class, [
                'label' => 'URL Slug',
                'attr' => [
                    'placeholder' => 'page-url-slug',
                    'class' => 'form-control',
                ],
                'help' => 'URL-friendly identifier (lowercase letters, numbers, hyphens, and forward slashes only)',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Page Content',
                'attr' => [
                    'rows' => 15,
                    'class' => 'form-control',
                    'placeholder' => 'Enter page content',
                ],
                'help' => 'Main content of the page (HTML allowed)',
            ])
            ->add('metaDescription', TextType::class, [
                'label' => 'Meta Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Brief description for search engines',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'help' => 'SEO meta description (max 255 characters)',
            ])
            ->add('metaKeywords', TextType::class, [
                'label' => 'Meta Keywords',
                'required' => false,
                'attr' => [
                    'placeholder' => 'keyword1, keyword2, keyword3',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'help' => 'SEO keywords, comma-separated (max 255 characters)',
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Published',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Make this page visible to the public',
            ])
            ->add('isHomepage', CheckboxType::class, [
                'label' => 'Set as Homepage',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Display this page as the site homepage',
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Display Order',
                'attr' => [
                    'placeholder' => '0',
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'help' => 'Sort order for navigation menus (lower numbers appear first)',
            ])
            ->add('publishedAt', DateTimeType::class, [
                'label' => 'Publish Date',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Schedule page publication for a future date (leave blank for immediate)',
            ])
            ->add('author', EntityType::class, [
                'label' => 'Author',
                'class' => User::class,
                'choice_label' => 'username',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'The author of this page',
            ])
            ->add('categories', EntityType::class, [
                'label' => 'Categories',
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'size' => 5,
                ],
                'help' => 'Assign this page to one or more categories',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
