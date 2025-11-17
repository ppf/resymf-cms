<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Settings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for Settings entity.
 *
 * Handles site-wide configuration settings
 */
class SettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // General Settings
            ->add('siteName', TextType::class, [
                'label' => 'Site Name',
                'attr' => [
                    'placeholder' => 'My Website',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'help' => 'The name of your website',
            ])
            ->add('siteTagline', TextType::class, [
                'label' => 'Site Tagline',
                'required' => false,
                'attr' => [
                    'placeholder' => 'A brief description',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
                'help' => 'A short tagline or slogan',
            ])
            ->add('adminEmail', EmailType::class, [
                'label' => 'Admin Email',
                'required' => false,
                'attr' => [
                    'placeholder' => 'admin@example.com',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'help' => 'Email address for admin notifications',
            ])

            // SEO Settings
            ->add('seoDescription', TextareaType::class, [
                'label' => 'SEO Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'Description for search engines',
                    'maxlength' => 500,
                ],
                'help' => 'Meta description for SEO (max 500 characters)',
            ])
            ->add('seoKeywords', TextType::class, [
                'label' => 'SEO Keywords',
                'required' => false,
                'attr' => [
                    'placeholder' => 'keyword1, keyword2, keyword3',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'help' => 'Comma-separated keywords for SEO',
            ])

            // Analytics
            ->add('googleAnalyticsId', TextType::class, [
                'label' => 'Google Analytics ID',
                'required' => false,
                'attr' => [
                    'placeholder' => 'G-XXXXXXXXXX',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
                'help' => 'Google Analytics tracking ID',
            ])
            ->add('googleTagManagerKey', TextType::class, [
                'label' => 'Google Tag Manager ID',
                'required' => false,
                'attr' => [
                    'placeholder' => 'GTM-XXXXXXX',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
                'help' => 'Google Tag Manager container ID',
            ])

            // Maintenance
            ->add('maintenanceMode', CheckboxType::class, [
                'label' => 'Maintenance Mode',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Enable to show maintenance page to visitors',
            ])
            ->add('maintenanceMessage', TextareaType::class, [
                'label' => 'Maintenance Message',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'We will be back soon!',
                ],
                'help' => 'Message displayed during maintenance',
            ])

            // Localization
            ->add('defaultLocale', ChoiceType::class, [
                'label' => 'Default Language',
                'choices' => [
                    'English' => 'en',
                    'French' => 'fr',
                    'German' => 'de',
                    'Spanish' => 'es',
                    'Italian' => 'it',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Default language for the site',
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'Timezone',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Default timezone for date/time display',
            ])

            // User Settings
            ->add('registrationEnabled', CheckboxType::class, [
                'label' => 'Enable User Registration',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Allow new users to register',
            ])
            ->add('emailVerificationRequired', CheckboxType::class, [
                'label' => 'Require Email Verification',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Require users to verify their email address',
            ])
            ->add('itemsPerPage', IntegerType::class, [
                'label' => 'Items Per Page',
                'attr' => [
                    'placeholder' => '10',
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 100,
                ],
                'help' => 'Number of items to display per page (1-100)',
            ])

            // Social Media
            ->add('facebookUrl', TextType::class, [
                'label' => 'Facebook URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://facebook.com/yourpage',
                    'class' => 'form-control',
                    'maxlength' => 50,
                ],
                'help' => 'Facebook page URL',
            ])
            ->add('twitterUrl', TextType::class, [
                'label' => 'Twitter/X URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://twitter.com/yourhandle',
                    'class' => 'form-control',
                    'maxlength' => 50,
                ],
                'help' => 'Twitter/X profile URL',
            ])
            ->add('linkedinUrl', TextType::class, [
                'label' => 'LinkedIn URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://linkedin.com/company/yourcompany',
                    'class' => 'form-control',
                    'maxlength' => 50,
                ],
                'help' => 'LinkedIn profile or company page URL',
            ])
            ->add('githubUrl', TextType::class, [
                'label' => 'GitHub URL',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://github.com/yourusername',
                    'class' => 'form-control',
                    'maxlength' => 50,
                ],
                'help' => 'GitHub profile URL',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Settings::class,
        ]);
    }
}
