<template>
  <div class="category-form-container">
    <form @submit.prevent="handleSubmit" class="category-form">
      <!-- Name Field -->
      <div class="mb-3">
        <label for="category-name" class="form-label">
          Name <span class="text-danger">*</span>
        </label>
        <input
          id="category-name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': errors.name }"
          placeholder="Enter category name"
          maxlength="100"
          @input="handleNameInput"
          @blur="validateField('name')"
        />
        <div v-if="errors.name" class="invalid-feedback">
          {{ errors.name }}
        </div>
        <small class="form-text text-muted">
          Category name (2-100 characters)
        </small>
      </div>

      <!-- Slug Field -->
      <div class="mb-3">
        <label for="category-slug" class="form-label">
          Slug <span class="text-danger">*</span>
        </label>
        <div class="input-group">
          <input
            id="category-slug"
            v-model="form.slug"
            type="text"
            class="form-control"
            :class="{
              'is-invalid': errors.slug,
              'is-valid': slugValid && !errors.slug && form.slug
            }"
            placeholder="category-slug"
            maxlength="128"
            :disabled="validatingSlug"
            @input="handleSlugInput"
            @blur="validateSlug"
          />
          <button
            v-if="form.name && !slugManuallyEdited"
            type="button"
            class="btn btn-outline-secondary"
            @click="regenerateSlug"
            title="Regenerate from name"
          >
            <i class="bi bi-arrow-clockwise"></i>
          </button>
          <div v-if="errors.slug" class="invalid-feedback">
            {{ errors.slug }}
          </div>
          <div v-if="slugValid && !errors.slug && form.slug" class="valid-feedback">
            {{ slugValidMessage }}
          </div>
        </div>
        <small class="form-text text-muted">
          URL-friendly identifier (lowercase letters, numbers, hyphens only)
          {{ slugManuallyEdited ? '• Manually edited' : '• Auto-generated from name' }}
        </small>
      </div>

      <!-- Description Field -->
      <div class="mb-3">
        <label for="category-description" class="form-label">
          Description
        </label>
        <textarea
          id="category-description"
          v-model="form.description"
          class="form-control"
          rows="3"
          placeholder="Optional description for this category"
        ></textarea>
        <small class="form-text text-muted">
          Optional description visible in category listings
        </small>
      </div>

      <!-- Display Order Field -->
      <div class="mb-3">
        <label for="category-display-order" class="form-label">
          Display Order
        </label>
        <input
          id="category-display-order"
          v-model.number="form.displayOrder"
          type="number"
          class="form-control"
          :class="{ 'is-invalid': errors.displayOrder }"
          min="0"
          step="1"
          placeholder="0"
        />
        <div v-if="errors.displayOrder" class="invalid-feedback">
          {{ errors.displayOrder }}
        </div>
        <small class="form-text text-muted">
          Sort order for navigation (lower numbers appear first)
        </small>
      </div>

      <!-- Active Toggle -->
      <div class="mb-4">
        <div class="form-check form-switch">
          <input
            id="category-active"
            v-model="form.isActive"
            type="checkbox"
            class="form-check-input"
            role="switch"
          />
          <label for="category-active" class="form-check-label">
            <strong>Active</strong>
            <span class="text-muted ms-2">
              {{ form.isActive ? 'Category is visible' : 'Category is hidden' }}
            </span>
          </label>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="submitting || !isFormValid || validatingSlug"
        >
          <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="bi bi-check-lg me-1"></i>
          {{ isEditMode ? 'Update Category' : 'Create Category' }}
        </button>

        <a :href="cancelUrl" class="btn btn-secondary">
          <i class="bi bi-x-lg me-1"></i>
          Cancel
        </a>

        <button
          v-if="isEditMode && hasChanges"
          type="button"
          class="btn btn-outline-secondary"
          @click="resetToOriginal"
        >
          <i class="bi bi-arrow-counterclockwise me-1"></i>
          Reset
        </button>
      </div>

      <!-- Validation Summary -->
      <div v-if="Object.keys(errors).length > 0" class="alert alert-danger mt-3">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
          <li v-for="(error, field) in errors" :key="field">{{ error }}</li>
        </ul>
      </div>
    </form>

    <!-- Toast Notification -->
    <div
      v-if="toast.show"
      :class="['toast-notification', `toast-${toast.type}`]"
      role="alert"
    >
      <i :class="['bi', toastIcon, 'me-2']"></i>
      {{ toast.message }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useCategoryForm } from '../composables/useCategoryForm.js';

const props = defineProps({
  categoryId: {
    type: Number,
    default: null,
  },
  apiUrl: {
    type: String,
    required: true,
  },
  cancelUrl: {
    type: String,
    default: '/admin/categories',
  },
});

const {
  form,
  errors,
  submitting,
  fetchCategory,
  createCategory,
  updateCategory,
  validateSlugUniqueness,
  generateSlug,
  clearErrors,
} = useCategoryForm(props.apiUrl);

const isEditMode = computed(() => !!props.categoryId);
const slugValid = ref(false);
const slugValidMessage = ref('');
const slugManuallyEdited = ref(false);
const validatingSlug = ref(false);
const toast = ref({ show: false, message: '', type: 'success' });
const originalData = ref(null);
const hasChanges = computed(() => {
  if (!originalData.value) return false;
  return (
    form.name !== originalData.value.name ||
    form.slug !== originalData.value.slug ||
    form.description !== originalData.value.description ||
    form.displayOrder !== originalData.value.displayOrder ||
    form.isActive !== originalData.value.isActive
  );
});

// Auto-generate slug from name (if not manually edited)
watch(() => form.name, (newName) => {
  if (!slugManuallyEdited.value || !form.slug) {
    form.slug = generateSlug(newName);
    slugValid.value = false; // Reset validation
  }
});

// Detect manual slug editing
const handleSlugInput = () => {
  slugManuallyEdited.value = true;
  slugValid.value = false; // Reset validation on edit
  delete errors.slug;
};

// Handle name input
const handleNameInput = () => {
  delete errors.name;
};

// Validate individual field
const validateField = (field) => {
  if (field === 'name') {
    if (!form.name) {
      errors.name = 'Name is required';
    } else if (form.name.length < 2) {
      errors.name = 'Name must be at least 2 characters';
    } else if (form.name.length > 100) {
      errors.name = 'Name must not exceed 100 characters';
    } else {
      delete errors.name;
    }
  }
};

// Validate slug uniqueness with server
const validateSlug = async () => {
  if (!form.slug) {
    errors.slug = 'Slug is required';
    slugValid.value = false;
    return;
  }

  // Check format
  if (!/^[a-z0-9-]+$/.test(form.slug)) {
    errors.slug = 'Slug can only contain lowercase letters, numbers, and hyphens';
    slugValid.value = false;
    return;
  }

  validatingSlug.value = true;
  const result = await validateSlugUniqueness(form.slug, props.categoryId);
  validatingSlug.value = false;

  if (result.valid) {
    delete errors.slug;
    slugValid.value = true;
    slugValidMessage.value = result.message || 'Slug is available';
  } else {
    errors.slug = result.message;
    slugValid.value = false;
  }
};

// Regenerate slug from name
const regenerateSlug = () => {
  slugManuallyEdited.value = false;
  form.slug = generateSlug(form.name);
  slugValid.value = false;
  validateSlug();
};

// Check if form is valid
const isFormValid = computed(() => {
  return (
    form.name.length >= 2 &&
    form.slug.length > 0 &&
    Object.keys(errors).length === 0
  );
});

// Reset to original data
const resetToOriginal = () => {
  if (originalData.value) {
    Object.assign(form, originalData.value);
    clearErrors();
    slugValid.value = false;
  }
};

// Handle form submission
const handleSubmit = async () => {
  // Clear previous errors
  clearErrors();

  // Validate all fields
  validateField('name');

  if (!form.slug) {
    errors.slug = 'Slug is required';
  }

  // Final slug validation
  await validateSlug();

  if (!isFormValid.value) {
    showToast('Please fix all errors before submitting', 'error');
    return;
  }

  // Submit form
  let result;
  if (isEditMode.value) {
    result = await updateCategory(props.categoryId);
  } else {
    result = await createCategory();
  }

  if (result.success) {
    showToast(result.message, 'success');

    // Redirect after short delay
    setTimeout(() => {
      window.location.href = props.cancelUrl;
    }, 1000);
  } else {
    showToast(result.message || 'An error occurred', 'error');
  }
};

// Toast helpers
const toastIcon = computed(() => {
  switch (toast.value.type) {
    case 'success': return 'bi-check-circle-fill';
    case 'error': return 'bi-exclamation-circle-fill';
    case 'warning': return 'bi-exclamation-triangle-fill';
    default: return 'bi-info-circle-fill';
  }
});

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

// Load category data on mount (if editing)
onMounted(async () => {
  if (isEditMode.value) {
    const result = await fetchCategory(props.categoryId);
    if (result.success) {
      // Store original data for reset
      originalData.value = {
        name: form.name,
        slug: form.slug,
        description: form.description,
        displayOrder: form.displayOrder,
        isActive: form.isActive,
      };
      slugManuallyEdited.value = true; // Don't auto-generate for existing categories

      // Validate slug to show it's available
      await validateSlug();
    } else {
      showToast('Failed to load category', 'error');
    }
  }
});
</script>

<style scoped>
.category-form-container {
  max-width: 800px;
  margin: 0 auto;
}

.category-form {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.form-label {
  font-weight: 500;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.form-control, .form-select {
  border-radius: 4px;
  border: 1px solid #cbd5e0;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus, .form-select:focus {
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-control.is-invalid {
  border-color: #f56565;
}

.form-control.is-valid {
  border-color: #48bb78;
}

.form-check-input {
  width: 3rem;
  height: 1.5rem;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: #48bb78;
  border-color: #48bb78;
}

.form-check-label {
  cursor: pointer;
  user-select: none;
}

.form-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.form-actions .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.invalid-feedback, .valid-feedback {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.875rem;
}

.form-text {
  color: #718096;
  font-size: 0.875rem;
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  z-index: 9999;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  animation: slideIn 0.3s ease-out;
  display: flex;
  align-items: center;
  max-width: 400px;
}

.toast-success {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.toast-error {
  background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
}

.toast-warning {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Responsive */
@media (max-width: 768px) {
  .category-form {
    padding: 1.5rem;
  }

  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .form-actions .btn {
    width: 100%;
  }

  .toast-notification {
    left: 20px;
    right: 20px;
    max-width: none;
  }
}
</style>
