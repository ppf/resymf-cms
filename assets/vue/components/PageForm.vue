<template>
  <div class="page-form-container">
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2 text-muted">Loading page data...</p>
    </div>

    <form v-else @submit.prevent="handleSubmit" class="page-form">
      <!-- Title Field -->
      <div class="mb-3">
        <label for="title" class="form-label">
          Title <span class="text-danger">*</span>
        </label>
        <input
          id="title"
          v-model="form.title"
          type="text"
          class="form-control"
          :class="{
            'is-invalid': errors.title,
            'is-valid': !errors.title && form.title && form.title.length >= 3
          }"
          placeholder="Enter page title"
          required
          @input="handleTitleChange"
        />
        <div v-if="errors.title" class="invalid-feedback">
          {{ errors.title }}
        </div>
        <div v-else class="form-text">
          The main title of the page (3-255 characters)
        </div>
      </div>

      <!-- Slug Field -->
      <div class="mb-3">
        <label for="slug" class="form-label">
          URL Slug <span class="text-danger">*</span>
        </label>
        <div class="input-group">
          <input
            id="slug"
            v-model="form.slug"
            type="text"
            class="form-control"
            :class="{
              'is-invalid': errors.slug,
              'is-valid': slugValid && !errors.slug && form.slug
            }"
            placeholder="page-url-slug"
            required
            @blur="validateSlug"
          />
          <button
            type="button"
            class="btn btn-outline-secondary"
            @click="autoGenerateSlug"
            :disabled="!form.title"
            title="Generate slug from title"
          >
            <i class="bi bi-arrow-repeat"></i> Generate
          </button>
          <div v-if="errors.slug" class="invalid-feedback">
            {{ errors.slug }}
          </div>
        </div>
        <div v-if="validatingSlug" class="form-text text-primary">
          <span class="spinner-border spinner-border-sm me-1"></span>
          Validating slug...
        </div>
        <div v-else-if="slugValid && form.slug && !errors.slug" class="form-text text-success">
          <i class="bi bi-check-circle-fill me-1"></i>
          Slug is available
        </div>
        <div v-else-if="!errors.slug" class="form-text">
          URL-friendly identifier (lowercase letters, numbers, hyphens, and forward slashes only)
        </div>
      </div>

      <!-- Content Field (Rich Text Editor) -->
      <RichTextEditor
        v-model="form.content"
        label="Page Content"
        placeholder="Enter page content..."
        :rows="15"
        :error="errors.content"
        :required="true"
        help-text="Main content of the page (HTML allowed)"
        :show-char-count="true"
      />

      <!-- SEO Section -->
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0">
            <i class="bi bi-search me-2"></i>
            SEO Settings
          </h6>
        </div>
        <div class="card-body">
          <!-- Meta Description -->
          <div class="mb-3">
            <label for="metaDescription" class="form-label">Meta Description</label>
            <textarea
              id="metaDescription"
              v-model="form.metaDescription"
              class="form-control"
              :class="{ 'is-invalid': errors.metaDescription }"
              rows="2"
              maxlength="255"
              placeholder="Brief description for search engines"
            ></textarea>
            <div v-if="errors.metaDescription" class="invalid-feedback">
              {{ errors.metaDescription }}
            </div>
            <div v-else class="form-text">
              {{ form.metaDescription?.length || 0 }} / 255 characters - Appears in search engine results
            </div>
          </div>

          <!-- Meta Keywords -->
          <div class="mb-0">
            <label for="metaKeywords" class="form-label">Meta Keywords</label>
            <input
              id="metaKeywords"
              v-model="form.metaKeywords"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': errors.metaKeywords }"
              maxlength="255"
              placeholder="keyword1, keyword2, keyword3"
            />
            <div v-if="errors.metaKeywords" class="invalid-feedback">
              {{ errors.metaKeywords }}
            </div>
            <div v-else class="form-text">
              {{ form.metaKeywords?.length || 0 }} / 255 characters - Comma-separated keywords
            </div>
          </div>
        </div>
      </div>

      <!-- Publishing Options -->
      <div class="card mb-3">
        <div class="card-header">
          <h6 class="mb-0">
            <i class="bi bi-calendar-check me-2"></i>
            Publishing Options
          </h6>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <!-- Published Checkbox -->
              <div class="form-check mb-3">
                <input
                  id="isPublished"
                  v-model="form.isPublished"
                  type="checkbox"
                  class="form-check-input"
                />
                <label for="isPublished" class="form-check-label">
                  <strong>Published</strong>
                  <span class="text-muted d-block small">Make this page visible to the public</span>
                </label>
              </div>

              <!-- Homepage Checkbox -->
              <div class="form-check mb-3">
                <input
                  id="isHomepage"
                  v-model="form.isHomepage"
                  type="checkbox"
                  class="form-check-input"
                />
                <label for="isHomepage" class="form-check-label">
                  <strong>Set as Homepage</strong>
                  <span class="text-muted d-block small">Display this page as the site homepage</span>
                </label>
              </div>
            </div>

            <div class="col-md-6">
              <!-- Publish Date -->
              <div class="mb-3">
                <label for="publishedAt" class="form-label">Publish Date</label>
                <input
                  id="publishedAt"
                  v-model="form.publishedAt"
                  type="datetime-local"
                  class="form-control"
                  :class="{ 'is-invalid': errors.publishedAt }"
                />
                <div v-if="errors.publishedAt" class="invalid-feedback">
                  {{ errors.publishedAt }}
                </div>
                <div v-else class="form-text">
                  Schedule publication for a future date (leave blank for immediate)
                </div>
              </div>

              <!-- Display Order -->
              <div class="mb-0">
                <label for="displayOrder" class="form-label">Display Order</label>
                <input
                  id="displayOrder"
                  v-model.number="form.displayOrder"
                  type="number"
                  min="0"
                  class="form-control"
                  :class="{ 'is-invalid': errors.displayOrder }"
                  placeholder="0"
                />
                <div v-if="errors.displayOrder" class="invalid-feedback">
                  {{ errors.displayOrder }}
                </div>
                <div v-else class="form-text">
                  Sort order for navigation menus (lower numbers appear first)
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Categories -->
      <div class="mb-3">
        <label class="form-label">Categories</label>
        <div class="category-checkboxes p-3 border rounded">
          <div v-if="categories.length === 0" class="text-muted">
            No categories available
          </div>
          <div v-for="category in categories" :key="category.id" class="form-check">
            <input
              :id="`category-${category.id}`"
              v-model="form.categoryIds"
              type="checkbox"
              :value="category.id"
              class="form-check-input"
            />
            <label :for="`category-${category.id}`" class="form-check-label">
              {{ category.name }}
              <span v-if="category.description" class="text-muted small d-block">
                {{ category.description }}
              </span>
            </label>
          </div>
        </div>
        <div class="form-text">
          Select one or more categories for this page
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="d-flex justify-content-between align-items-center pt-3 border-top">
        <div>
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="submitting"
          >
            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
            <i v-else class="bi bi-save me-2"></i>
            {{ pageId ? 'Update Page' : 'Create Page' }}
          </button>

          <button
            v-if="pageId && hasChanges"
            type="button"
            class="btn btn-secondary ms-2"
            @click="resetToOriginal"
            :disabled="submitting"
          >
            <i class="bi bi-arrow-counterclockwise me-2"></i>
            Reset
          </button>
        </div>

        <a :href="cancelUrl" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle me-2"></i>
          Cancel
        </a>
      </div>
    </form>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <div
        ref="toastEl"
        class="toast"
        :class="toastClass"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <div class="toast-header">
          <i class="bi me-2" :class="toastIcon"></i>
          <strong class="me-auto">{{ toastTitle }}</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
          {{ toastMessage }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { usePageForm } from '../composables/usePageForm';
import RichTextEditor from './RichTextEditor.vue';

const props = defineProps({
  pageId: {
    type: Number,
    default: null,
  },
  apiUrl: {
    type: String,
    required: true,
  },
  cancelUrl: {
    type: String,
    default: '/admin/pages',
  },
  categoriesData: {
    type: String,
    default: '[]',
  },
});

const { form, errors, submitting, fetchPage, createPage, updatePage, validateSlugUniqueness, generateSlug, clearErrors } = usePageForm(props.apiUrl);

const loading = ref(false);
const slugValid = ref(false);
const validatingSlug = ref(false);
const originalForm = reactive({});
const categories = ref([]);

// Toast
const toastEl = ref(null);
const toastMessage = ref('');
const toastTitle = ref('');
const toastClass = ref('');
const toastIcon = ref('');

const hasChanges = computed(() => {
  if (!props.pageId) return false;

  return JSON.stringify(form) !== JSON.stringify(originalForm);
});

const handleTitleChange = () => {
  // Auto-generate slug when typing title in create mode
  if (!props.pageId && form.title && !form.slug) {
    autoGenerateSlug();
  }
};

const autoGenerateSlug = async () => {
  if (!form.title) {
    return;
  }

  const result = await generateSlug(form.title);
  if (result.success) {
    form.slug = result.slug;
    await validateSlug();
  }
};

const validateSlug = async () => {
  if (!form.slug) {
    slugValid.value = false;
    return;
  }

  validatingSlug.value = true;
  const result = await validateSlugUniqueness(form.slug, props.pageId);
  validatingSlug.value = false;

  if (!result.valid) {
    errors.slug = result.message;
    slugValid.value = false;
  } else {
    delete errors.slug;
    slugValid.value = true;
  }
};

const handleSubmit = async () => {
  clearErrors();

  // Client-side validation
  if (!form.title || form.title.length < 3) {
    errors.title = 'Title must be at least 3 characters';
    return;
  }

  if (!form.slug) {
    errors.slug = 'Slug is required';
    return;
  }

  if (!form.content) {
    errors.content = 'Content is required';
    return;
  }

  // Submit
  const result = props.pageId
    ? await updatePage(props.pageId)
    : await createPage();

  if (result.success) {
    showToast('Success', result.message, 'success');

    // Redirect after short delay
    setTimeout(() => {
      window.location.href = props.cancelUrl;
    }, 1500);
  } else {
    showToast('Error', result.message, 'danger');
  }
};

const resetToOriginal = () => {
  Object.assign(form, JSON.parse(JSON.stringify(originalForm)));
  clearErrors();
  showToast('Reset', 'Form has been reset to original values', 'info');
};

const showToast = (title, message, type = 'success') => {
  toastTitle.value = title;
  toastMessage.value = message;

  if (type === 'success') {
    toastClass.value = 'bg-success text-white';
    toastIcon.value = 'bi-check-circle-fill text-white';
  } else if (type === 'danger') {
    toastClass.value = 'bg-danger text-white';
    toastIcon.value = 'bi-exclamation-triangle-fill text-white';
  } else if (type === 'info') {
    toastClass.value = 'bg-info text-white';
    toastIcon.value = 'bi-info-circle-fill text-white';
  }

  if (toastEl.value && window.bootstrap) {
    const toast = new window.bootstrap.Toast(toastEl.value);
    toast.show();
  }
};

onMounted(async () => {
  // Parse categories
  try {
    categories.value = JSON.parse(props.categoriesData);
  } catch (e) {
    console.error('Failed to parse categories data:', e);
    categories.value = [];
  }

  // Load page data if editing
  if (props.pageId) {
    loading.value = true;
    const result = await fetchPage(props.pageId);
    loading.value = false;

    if (!result.success) {
      showToast('Error', result.message, 'danger');
      return;
    }

    // Store original form state for reset functionality
    Object.assign(originalForm, JSON.parse(JSON.stringify(form)));
  }
});
</script>

<style scoped>
/* ============================================
   Design System: Refined Editorial
   Aligned with admin sidebar aesthetic
   ============================================ */

/* Color Palette */
.page-form-container {
  --primary: #3b82f6;
  --primary-hover: #2563eb;
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --slate-50: #f8fafc;
  --slate-100: #f1f5f9;
  --slate-200: #e2e8f0;
  --slate-300: #cbd5e1;
  --slate-500: #64748b;
  --slate-700: #334155;
  --slate-900: #0f172a;

  max-width: 900px;
}

/* Main Form Container */
.page-form {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid var(--slate-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Form Labels */
.page-form :deep(.form-label) {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--slate-700);
  margin-bottom: 0.375rem;
}

/* Form Inputs */
.page-form :deep(.form-control) {
  border: 1px solid var(--slate-200);
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--slate-900);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.page-form :deep(.form-control:focus) {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  outline: none;
}

.page-form :deep(.form-control::placeholder) {
  color: var(--slate-300);
}

.page-form :deep(.form-control.is-valid) {
  border-color: var(--success);
  background-image: none;
  padding-right: 0.875rem;
}

.page-form :deep(.form-control.is-invalid) {
  border-color: var(--danger);
  background-image: none;
  padding-right: 0.875rem;
}

/* Input Group (Slug field with button) */
.page-form :deep(.input-group) {
  display: flex;
}

.page-form :deep(.input-group .form-control) {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.page-form :deep(.input-group .btn) {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  border: 1px solid var(--slate-200);
  border-left: none;
  background: var(--slate-50);
  color: var(--slate-700);
  font-size: 0.8125rem;
  padding: 0.625rem 1rem;
  transition: all 0.15s ease;
}

.page-form :deep(.input-group .btn:hover:not(:disabled)) {
  background: var(--slate-100);
  color: var(--slate-900);
}

.page-form :deep(.input-group .btn:disabled) {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Card Sections (SEO, Publishing) */
.page-form .card {
  border: 1px solid var(--slate-200);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: none;
}

.page-form .card-header {
  background: linear-gradient(to bottom, var(--slate-50), #ffffff);
  border-bottom: 1px solid var(--slate-100);
  padding: 1rem 1.25rem;
}

.page-form .card-header h6 {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--slate-900);
  display: flex;
  align-items: center;
}

.page-form .card-header h6 i {
  color: var(--primary);
  font-size: 1rem;
}

.page-form .card-body {
  padding: 1.25rem;
  background: white;
}

/* Categories Section */
.category-checkboxes {
  background: var(--slate-50);
  border: 1px solid var(--slate-200) !important;
  border-radius: 8px !important;
  max-height: 200px;
  overflow-y: auto;
  padding: 1rem !important;
}

.category-checkboxes::-webkit-scrollbar {
  width: 6px;
}

.category-checkboxes::-webkit-scrollbar-track {
  background: var(--slate-100);
  border-radius: 3px;
}

.category-checkboxes::-webkit-scrollbar-thumb {
  background: var(--slate-300);
  border-radius: 3px;
}

/* Checkboxes */
.page-form :deep(.form-check) {
  margin-bottom: 0.75rem;
  padding-left: 1.75rem;
}

.page-form :deep(.form-check:last-child) {
  margin-bottom: 0;
}

.page-form :deep(.form-check-input) {
  width: 1.125rem;
  height: 1.125rem;
  border: 1.5px solid var(--slate-300);
  border-radius: 4px;
  margin-top: 0.125rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.page-form :deep(.form-check-input:checked) {
  background-color: var(--primary);
  border-color: var(--primary);
}

.page-form :deep(.form-check-input:focus) {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  border-color: var(--primary);
}

.page-form :deep(.form-check-label) {
  font-size: 0.875rem;
  color: var(--slate-700);
  cursor: pointer;
}

.page-form :deep(.form-check-label strong) {
  color: var(--slate-900);
  font-weight: 500;
}

/* Help Text */
.page-form :deep(.form-text) {
  font-size: 0.75rem;
  color: var(--slate-500);
  margin-top: 0.375rem;
}

.page-form :deep(.form-text.text-success) {
  color: var(--success);
}

.page-form :deep(.form-text.text-primary) {
  color: var(--primary);
}

/* Validation Feedback */
.page-form :deep(.invalid-feedback) {
  font-size: 0.75rem;
  color: var(--danger);
  margin-top: 0.375rem;
}

.page-form :deep(.valid-feedback) {
  font-size: 0.75rem;
  color: var(--success);
  margin-top: 0.375rem;
}

/* Buttons */
.page-form :deep(.btn-primary) {
  background: var(--primary);
  border: none;
  border-radius: 8px;
  padding: 0.625rem 1.25rem;
  font-weight: 500;
  font-size: 0.875rem;
  transition: all 0.15s ease;
}

.page-form :deep(.btn-primary:hover:not(:disabled)) {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
}

.page-form :deep(.btn-primary:active) {
  transform: translateY(0);
}

.page-form :deep(.btn-primary:disabled) {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.page-form :deep(.btn-secondary) {
  background: var(--slate-100);
  border: 1px solid var(--slate-200);
  border-radius: 8px;
  padding: 0.625rem 1.25rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: var(--slate-700);
  transition: all 0.15s ease;
}

.page-form :deep(.btn-secondary:hover:not(:disabled)) {
  background: var(--slate-200);
  color: var(--slate-900);
}

.page-form :deep(.btn-outline-secondary) {
  background: transparent;
  border: 1px solid var(--slate-200);
  border-radius: 8px;
  padding: 0.625rem 1.25rem;
  font-weight: 500;
  font-size: 0.875rem;
  color: var(--slate-600);
  transition: all 0.15s ease;
}

.page-form :deep(.btn-outline-secondary:hover) {
  background: var(--slate-50);
  border-color: var(--slate-300);
  color: var(--slate-900);
}

/* Action Bar */
.page-form .border-top {
  border-color: var(--slate-200) !important;
}

/* Toast Notifications */
.toast-container {
  z-index: 1050;
}

.toast {
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.toast.bg-success {
  background: linear-gradient(135deg, var(--success) 0%, #059669 100%) !important;
}

.toast.bg-danger {
  background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%) !important;
}

.toast.bg-info {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%) !important;
}

.toast .toast-header {
  background: transparent;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem 1rem;
}

.toast .toast-body {
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
}

/* Loading Spinner */
.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}

.page-form .spinner-border.text-primary {
  color: var(--primary) !important;
}

/* Toast Close Button */
.btn-close {
  filter: invert(1);
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .page-form {
    padding: 1.25rem;
  }

  .page-form .card-body {
    padding: 1rem;
  }
}
</style>
