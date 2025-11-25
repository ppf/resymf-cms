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
.page-form-container {
  max-width: 900px;
}

.page-form {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.card {
  border: 1px solid #dee2e6;
}

.card-header {
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  padding: 0.75rem 1rem;
}

.category-checkboxes {
  background: #f8f9fa;
  max-height: 200px;
  overflow-y: auto;
}

.form-check {
  margin-bottom: 0.5rem;
}

.form-check:last-child {
  margin-bottom: 0;
}

.toast-container {
  z-index: 1050;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}

.btn-close {
  filter: invert(1);
}
</style>
