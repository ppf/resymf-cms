<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CronJob;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for CronJob entity.
 *
 * Handles cron job creation and editing for scheduled tasks
 */
class CronJobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Job Name',
                'attr' => [
                    'placeholder' => 'e.g., Daily Cleanup',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
                'help' => 'Unique name for this cron job (3-100 characters)',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'Describe what this job does',
                ],
                'help' => 'Optional description of the job\'s purpose',
            ])
            ->add('command', TextType::class, [
                'label' => 'Console Command',
                'attr' => [
                    'placeholder' => 'e.g., app:cleanup --days=30',
                    'class' => 'form-control',
                    'maxlength' => 255,
                ],
                'help' => 'Symfony console command with optional arguments',
            ])
            ->add('cronExpression', TextType::class, [
                'label' => 'Cron Expression',
                'attr' => [
                    'placeholder' => 'e.g., 0 * * * * (hourly)',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
                'help' => 'Format: minute hour day month weekday. Examples: "0 * * * *" (hourly), "0 0 * * *" (daily midnight), "0 0 * * 0" (weekly Sunday)',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Only active jobs will be executed',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CronJob::class,
        ]);
    }
}
