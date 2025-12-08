# Vue Islands

Vue islands are self-contained Vue 3 components that mount into specific DOM elements. This architecture allows mixing server-rendered Twig templates with interactive Vue components.

## Architecture

```
Twig Template
    └── Mount Point (data-vue-island="...")
            └── Vue Entry Point (*-app.js)
                    └── Vue Component (.vue)
                            └── Composable (use*.js)
```

---

## Entry Point Pattern

Create entry points in `assets/vue/` (from `category-form-app.js`):

```javascript
import { createApp } from 'vue';
import CategoryForm from './components/CategoryForm.vue';

const mount = () => {
  const target = document.querySelector('[data-vue-island="category-form"]');
  if (!target) {
    return;
  }

  // Parse props from data attributes
  const categoryId = target.dataset.categoryId
    ? parseInt(target.dataset.categoryId, 10)
    : null;
  const apiUrl = target.dataset.apiUrl;
  const cancelUrl = target.dataset.cancelUrl || '/admin/categories';

  // Create and mount app
  createApp(CategoryForm, {
    categoryId,
    apiUrl,
    cancelUrl
  }).mount(target);
};

document.addEventListener('DOMContentLoaded', mount);
```

---

## Twig Mount Point

In your Twig template:

```twig
{# Mount point with data attributes #}
<div data-vue-island="category-form"
     data-api-url="{{ path('api_categories') }}"
     data-category-id="{{ category.id }}"
     data-cancel-url="{{ path('admin_category_index') }}">
</div>

{# Load the Vue entry point #}
{{ vite_entry_script_tags('category-form-app') }}
```

---

## Vue Component

Create components in `assets/vue/components/`:

```vue
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCategoryForm } from '../composables/useCategoryForm.js';

// Props from entry point
const props = defineProps({
  categoryId: { type: Number, default: null },
  apiUrl: { type: String, required: true },
  cancelUrl: { type: String, default: '/admin/categories' },
});

// Use composable for business logic
const {
  form,
  errors,
  submitting,
  fetchCategory,
  saveCategory
} = useCategoryForm(props.apiUrl);

// Computed
const isEditMode = computed(() => !!props.categoryId);
const isFormValid = computed(() =>
  form.name.trim().length >= 2 &&
  form.slug.trim().length >= 2
);

// Lifecycle
onMounted(async () => {
  if (isEditMode.value) {
    await fetchCategory(props.categoryId);
  }
});

// Methods
const handleSubmit = async () => {
  if (!isFormValid.value) return;

  const success = await saveCategory(props.categoryId);
  if (success) {
    window.location.href = props.cancelUrl;
  }
};
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <div>
      <label class="form-label">Name</label>
      <input
        v-model="form.name"
        type="text"
        class="form-control"
        :class="{ 'is-invalid': errors.name }"
      >
      <div v-if="errors.name" class="invalid-feedback">
        {{ errors.name }}
      </div>
    </div>

    <div>
      <label class="form-label">Slug</label>
      <input
        v-model="form.slug"
        type="text"
        class="form-control"
      >
    </div>

    <div class="form-check">
      <input
        v-model="form.isActive"
        type="checkbox"
        class="form-check-input"
        id="isActive"
      >
      <label class="form-check-label" for="isActive">Active</label>
    </div>

    <div class="flex gap-2">
      <button
        type="submit"
        class="btn btn-primary"
        :disabled="!isFormValid || submitting"
      >
        {{ submitting ? 'Saving...' : (isEditMode ? 'Update' : 'Create') }}
      </button>
      <a :href="cancelUrl" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</template>
```

---

## Composable Pattern

Create composables in `assets/vue/composables/`:

```javascript
import { reactive, ref } from 'vue';

export function useCategoryForm(apiBaseUrl) {
  // Reactive state
  const form = reactive({
    name: '',
    slug: '',
    description: '',
    displayOrder: 0,
    isActive: true,
  });

  const errors = reactive({});
  const submitting = ref(false);

  // Fetch existing entity
  const fetchCategory = async (id) => {
    try {
      const response = await fetch(`${apiBaseUrl}/${id}`);
      if (!response.ok) throw new Error('Failed to load');

      const data = await response.json();
      Object.assign(form, data);
    } catch (error) {
      console.error('Fetch error:', error);
    }
  };

  // Save entity (create or update)
  const saveCategory = async (id = null) => {
    submitting.value = true;
    Object.keys(errors).forEach(key => delete errors[key]);

    try {
      const method = id ? 'PUT' : 'POST';
      const url = id ? `${apiBaseUrl}/${id}` : apiBaseUrl;

      const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      if (!response.ok) {
        const data = await response.json();
        if (data.errors) {
          Object.assign(errors, data.errors);
        }
        return false;
      }

      return true;
    } catch (error) {
      console.error('Save error:', error);
      return false;
    } finally {
      submitting.value = false;
    }
  };

  return { form, errors, submitting, fetchCategory, saveCategory };
}
```

---

## Vite Configuration

Register entry points in `vite.config.mjs`:

```javascript
export default defineConfig({
  build: {
    rollupOptions: {
      input: {
        app: './assets/app.js',
        admin: './assets/admin.js',
        // Add Vue islands
        'category-form-app': './assets/vue/category-form-app.js',
        'category-grid-app': './assets/vue/category-grid-app.js',
        'user-form-app': './assets/vue/user-form-app.js',
        // ... add more as needed
      },
    },
  },
});
```

After adding an entry, rebuild:
```bash
npm run build
```

---

## API Endpoints

Vue islands typically need JSON API endpoints:

```php
#[Route('/api/categories')]
class CategoryApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(CategoryRepository $repo): JsonResponse
    {
        return $this->json($repo->findAll());
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($category);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Validate and create
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Request $request, Category $category): JsonResponse
    {
        // Validate and update
    }
}
```

---

## Checklist

When creating a new Vue island:

1. [ ] Create composable in `assets/vue/composables/use{Feature}.js`
2. [ ] Create component in `assets/vue/components/{Feature}.vue`
3. [ ] Create entry point in `assets/vue/{feature}-app.js`
4. [ ] Add entry to `vite.config.mjs` rollupOptions.input
5. [ ] Add API endpoint if needed
6. [ ] Add mount point in Twig template
7. [ ] Include `vite_entry_script_tags` in template
8. [ ] Rebuild frontend: `npm run build`
