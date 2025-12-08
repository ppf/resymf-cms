# Form Types

This guide explains how to create Symfony form types in ReSymf-CMS.

## Form Type Pattern

All form types follow this pattern (from `src/Form/CategoryType.php`):

```php
<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\YourEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class YourEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => [
                    'placeholder' => 'Enter name',
                    'class' => 'form-control',
                    'maxlength' => 100,
                ],
                'help' => 'Required field (2-100 characters)',
            ])
            ->add('slug', TextType::class, [
                'label' => 'URL Slug',
                'attr' => [
                    'placeholder' => 'entity-slug',
                    'class' => 'form-control',
                    'maxlength' => 128,
                ],
                'help' => 'Lowercase letters, numbers, and hyphens only',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                    'placeholder' => 'Optional description',
                ],
            ])
            ->add('displayOrder', IntegerType::class, [
                'label' => 'Display Order',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                ],
                'help' => 'Lower numbers appear first',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => YourEntity::class,
        ]);
    }
}
```

---

## Common Field Types

### TextType
```php
->add('name', TextType::class, [
    'label' => 'Name',
    'attr' => ['class' => 'form-control'],
])
```

### TextareaType
```php
->add('description', TextareaType::class, [
    'label' => 'Description',
    'required' => false,
    'attr' => ['rows' => 5, 'class' => 'form-control'],
])
```

### EmailType
```php
use Symfony\Component\Form\Extension\Core\Type\EmailType;

->add('email', EmailType::class, [
    'label' => 'Email Address',
    'attr' => ['class' => 'form-control'],
])
```

### PasswordType
```php
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

->add('password', PasswordType::class, [
    'label' => 'Password',
    'attr' => ['class' => 'form-control'],
])
```

### CheckboxType
```php
->add('isActive', CheckboxType::class, [
    'label' => 'Active',
    'required' => false,
    'attr' => ['class' => 'form-check-input'],
])
```

### IntegerType
```php
->add('displayOrder', IntegerType::class, [
    'label' => 'Order',
    'attr' => ['class' => 'form-control', 'min' => 0],
])
```

### ChoiceType
```php
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

->add('status', ChoiceType::class, [
    'label' => 'Status',
    'choices' => [
        'Draft' => 'draft',
        'Published' => 'published',
        'Archived' => 'archived',
    ],
    'attr' => ['class' => 'form-select'],
])
```

### DateType
```php
use Symfony\Component\Form\Extension\Core\Type\DateType;

->add('publishedAt', DateType::class, [
    'label' => 'Publish Date',
    'widget' => 'single_text',
    'attr' => ['class' => 'form-control'],
])
```

---

## Entity Relations

### EntityType (Select)
```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Category;

->add('category', EntityType::class, [
    'class' => Category::class,
    'choice_label' => 'name',
    'placeholder' => 'Select category',
    'attr' => ['class' => 'form-select'],
])
```

### EntityType (Multiple)
```php
->add('categories', EntityType::class, [
    'class' => Category::class,
    'choice_label' => 'name',
    'multiple' => true,
    'expanded' => true, // Checkboxes
    'attr' => ['class' => 'form-check-input'],
])
```

---

## Validation

Use constraints on the entity, not the form. The form will automatically use them.

```php
// In entity
#[Assert\NotBlank]
#[Assert\Length(min: 2, max: 100)]
private string $name;
```

Additional form-level validation:
```php
use Symfony\Component\Validator\Constraints as Assert;

->add('email', EmailType::class, [
    'constraints' => [
        new Assert\NotBlank(),
        new Assert\Email(),
    ],
])
```

---

## Rendering in Twig

### Basic Form
```twig
{{ form_start(form) }}
    {{ form_widget(form) }}
    <button type="submit" class="btn btn-primary">Save</button>
{{ form_end(form) }}
```

### Custom Layout
```twig
{{ form_start(form) }}
    <div class="mb-3">
        {{ form_label(form.name) }}
        {{ form_widget(form.name) }}
        {{ form_errors(form.name) }}
        {{ form_help(form.name) }}
    </div>

    <div class="mb-3">
        {{ form_row(form.description) }}
    </div>

    <div class="form-check mb-3">
        {{ form_widget(form.isActive) }}
        {{ form_label(form.isActive) }}
    </div>

    <button type="submit" class="btn btn-primary">Save</button>
{{ form_end(form) }}
```

---

## Using in Controller

```php
public function new(Request $request): Response
{
    $entity = new YourEntity();
    $form = $this->createForm(YourEntityType::class, $entity);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $this->redirectToRoute('admin_entity_index');
    }

    return $this->render('admin/entity/new.html.twig', [
        'form' => $form,
    ]);
}
```

---

## Bootstrap Classes

Standard Bootstrap 5 classes:
- Inputs: `form-control`
- Select: `form-select`
- Checkbox/Radio: `form-check-input`
- Labels: `form-label`, `form-check-label`
- Validation: `is-invalid`, `invalid-feedback`
