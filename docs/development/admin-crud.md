# Admin CRUD Controllers

This guide explains how to create admin CRUD features in ReSymf-CMS.

## Controller Pattern

All admin controllers follow this pattern (from `src/Controller/Admin/CategoryController.php`):

```php
<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\YourEntity;
use App\Form\YourEntityType;
use App\Repository\YourEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/your-entities')]
#[IsGranted('ROLE_ADMIN')]
class YourEntityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly YourEntityRepository $repository,
    ) {
    }

    #[Route('', name: 'admin_your_entity_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/your_entity/index.html.twig', [
            'entities' => $this->repository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_your_entity_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $entity = new YourEntity();
        $form = $this->createForm(YourEntityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            $this->addFlash('success', 'Created successfully.');
            return $this->redirectToRoute('admin_your_entity_index');
        }

        return $this->render('admin/your_entity/new.html.twig', [
            'entity' => $entity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_your_entity_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(YourEntity $entity): Response
    {
        return $this->render('admin/your_entity/show.html.twig', [
            'entity' => $entity,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_your_entity_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, YourEntity $entity): Response
    {
        $form = $this->createForm(YourEntityType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Updated successfully.');
            return $this->redirectToRoute('admin_your_entity_index');
        }

        return $this->render('admin/your_entity/edit.html.twig', [
            'entity' => $entity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_your_entity_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, YourEntity $entity): Response
    {
        if ($this->isCsrfTokenValid('delete' . $entity->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            $this->addFlash('success', 'Deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_your_entity_index');
    }

    #[Route('/{id}/toggle-active', name: 'admin_your_entity_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(Request $request, YourEntity $entity): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $entity->getId(), $request->request->get('_token'))) {
            $entity->setIsActive(!$entity->getIsActive());
            $this->entityManager->flush();

            $status = $entity->getIsActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', "Entity {$status}.");
        }

        return $this->redirectToRoute('admin_your_entity_index');
    }
}
```

---

## Route Conventions

| Route | Name | Method | Purpose |
|-------|------|--------|---------|
| `/admin/entities` | `admin_entity_index` | GET | List all |
| `/admin/entities/new` | `admin_entity_new` | GET, POST | Create form |
| `/admin/entities/{id}` | `admin_entity_show` | GET | View details |
| `/admin/entities/{id}/edit` | `admin_entity_edit` | GET, POST | Edit form |
| `/admin/entities/{id}/delete` | `admin_entity_delete` | POST | Delete action |
| `/admin/entities/{id}/toggle-active` | `admin_entity_toggle_active` | POST | Toggle status |

---

## CSRF Protection

Always validate CSRF tokens on destructive actions:

```php
// In Twig template
<form method="post" action="{{ path('admin_entity_delete', {id: entity.id}) }}">
    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ entity.id) }}">
    <button type="submit">Delete</button>
</form>
```

```php
// In controller
if ($this->isCsrfTokenValid('delete' . $entity->getId(), $request->request->get('_token'))) {
    // Process delete
}
```

---

## Templates

Create templates in `templates/admin/your_entity/`:

### index.html.twig
```twig
{% extends 'admin/base.html.twig' %}

{% block title %}Entities{% endblock %}

{% block body %}
<div class="flex justify-between mb-4">
    <h1 class="text-2xl font-bold">Entities</h1>
    <a href="{{ path('admin_your_entity_new') }}" class="btn btn-primary">
        Create New
    </a>
</div>

{# Vue island for grid #}
<div data-vue-island="entity-grid"
     data-api-url="{{ path('api_your_entities') }}"
     data-csrf-token="{{ csrf_token('toggle') }}">
</div>

{{ vite_entry_script_tags('your-entity-grid-app') }}
{% endblock %}
```

### new.html.twig / edit.html.twig
```twig
{% extends 'admin/base.html.twig' %}

{% block title %}{{ entity.id ? 'Edit' : 'Create' }} Entity{% endblock %}

{% block body %}
<div class="bg-white rounded-xl border p-6 shadow-sm mb-6">
    <h1 class="text-xl font-semibold">
        {{ entity.id ? 'Edit' : 'Create' }} Entity
    </h1>
</div>

{# Vue island for form #}
<div data-vue-island="entity-form"
     data-api-url="{{ path('api_your_entities') }}"
     {% if entity.id %}data-entity-id="{{ entity.id }}"{% endif %}
     data-cancel-url="{{ path('admin_your_entity_index') }}">
</div>

{{ vite_entry_script_tags('your-entity-form-app') }}
{% endblock %}
```

### show.html.twig
```twig
{% extends 'admin/base.html.twig' %}

{% block title %}{{ entity.name }}{% endblock %}

{% block body %}
<div class="bg-white rounded-xl border p-6">
    <h1 class="text-xl font-semibold mb-4">{{ entity.name }}</h1>

    <dl class="grid grid-cols-2 gap-4">
        <dt class="text-slate-500">Status</dt>
        <dd>{{ entity.isActive ? 'Active' : 'Inactive' }}</dd>

        <dt class="text-slate-500">Created</dt>
        <dd>{{ entity.createdAt|date('Y-m-d H:i') }}</dd>
    </dl>

    <div class="mt-6 flex gap-2">
        <a href="{{ path('admin_your_entity_edit', {id: entity.id}) }}"
           class="btn btn-primary">Edit</a>
        <a href="{{ path('admin_your_entity_index') }}"
           class="btn btn-secondary">Back</a>
    </div>
</div>
{% endblock %}
```

---

## Flash Messages

Use flash messages for user feedback:

```php
$this->addFlash('success', 'Operation completed.');
$this->addFlash('error', 'Something went wrong.');
$this->addFlash('warning', 'Please review.');
```

Display in base template:
```twig
{% for type, messages in app.flashes %}
    {% for message in messages %}
        <div class="alert alert-{{ type }}">{{ message }}</div>
    {% endfor %}
{% endfor %}
```

---

## Checklist

When creating a new admin feature:

1. [ ] Create entity (`src/Entity/`)
2. [ ] Create form type (`src/Form/`)
3. [ ] Create repository (`src/Repository/`)
4. [ ] Create controller (`src/Controller/Admin/`)
5. [ ] Create templates (`templates/admin/entity/`)
6. [ ] Create Vue components if needed
7. [ ] Add API endpoints for Vue islands
8. [ ] Write tests
