<template>
  <div class="category-grid">
    <!-- Header with Search -->
    <div class="grid-header mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h2 class="mb-0">Categories</h2>
        </div>
        <div class="col-md-6">
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-search"></i>
            </span>
            <input
              v-model="searchQuery"
              type="text"
              class="form-control"
              placeholder="Search categories..."
              @input="handleSearch"
            />
            <button v-if="searchQuery" class="btn btn-outline-secondary" @click="clearSearch">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && categories.length === 0" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-3">Loading categories...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchCategories">
        Try Again
      </button>
    </div>

    <!-- Categories Table -->
    <div v-else-if="categories.length" class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 40px"></th>
              <th @click="sort('name')" class="sortable">
                Name
                <i :class="getSortIcon('name')"></i>
              </th>
              <th @click="sort('slug')" class="sortable">
                Slug
                <i :class="getSortIcon('slug')"></i>
              </th>
              <th @click="sort('isActive')" class="sortable text-center" style="width: 100px">
                Active
                <i :class="getSortIcon('isActive')"></i>
              </th>
              <th class="text-center" style="width: 80px">Pages</th>
              <th @click="sort('displayOrder')" class="sortable text-center" style="width: 80px">
                Order
                <i :class="getSortIcon('displayOrder')"></i>
              </th>
              <th class="text-end" style="width: 100px">Actions</th>
            </tr>
          </thead>
          <draggable
            v-model="categories"
            tag="tbody"
            item-key="id"
            handle=".drag-handle"
            @end="handleDragEnd"
          >
            <template #item="{ element: category }">
              <tr>
                <td class="text-center">
                  <span class="drag-handle" title="Drag to reorder">
                    <i class="bi bi-grip-vertical"></i>
                  </span>
                </td>
                <td class="fw-semibold">{{ category.name }}</td>
                <td><code class="text-muted">{{ category.slug }}</code></td>
                <td class="text-center">
                  <button
                    @click="handleToggleActive(category)"
                    :class="['btn', 'btn-sm', category.isActive ? 'btn-success' : 'btn-secondary']"
                  >
                    {{ category.isActive ? 'Yes' : 'No' }}
                  </button>
                </td>
                <td class="text-center text-muted">{{ category.pageCount }}</td>
                <td class="text-center text-muted">{{ category.displayOrder }}</td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a
                      :href="`/admin/categories/${category.id}`"
                      class="btn btn-outline-info"
                      title="View category"
                    >
                      <i class="bi bi-eye"></i>
                    </a>
                    <a
                      :href="`/admin/categories/${category.id}/edit`"
                      class="btn btn-outline-primary"
                      title="Edit category"
                    >
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button
                      @click="handleDelete(category)"
                      class="btn btn-outline-danger"
                      title="Delete category"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </template>
          </draggable>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Showing {{ ((currentPage - 1) * perPage) + 1 }} to
          {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} categories
        </div>
        <nav v-if="totalPages > 1" aria-label="Category pagination">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="goToPage(currentPage - 1)" :disabled="currentPage === 1">
                <i class="bi bi-chevron-left"></i>
              </button>
            </li>
            <li
              v-for="pageNum in visiblePages"
              :key="pageNum"
              class="page-item"
              :class="{ active: currentPage === pageNum }"
            >
              <button class="page-link" @click="goToPage(pageNum)">
                {{ pageNum }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link" @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages">
                <i class="bi bi-chevron-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5">
      <i class="bi bi-tags" style="font-size: 4rem; color: var(--neutral-300);"></i>
      <p class="text-muted mt-3">
        {{ searchQuery ? 'No categories found matching your search.' : 'No categories yet.' }}
      </p>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="categoryToDelete"
      class="modal fade show d-block"
      tabindex="-1"
      style="background: rgba(0, 0, 0, 0.5);"
      @click.self="cancelDelete"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirm Delete</h5>
            <button type="button" class="btn-close" @click="cancelDelete"></button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete category <strong>{{ categoryToDelete.name }}</strong>?</p>
            <p class="text-danger mb-0">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              This action cannot be undone.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cancelDelete">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDeleteCategory" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              Delete Category
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div
      v-if="toast.show"
      :class="['toast-notification', `toast-${toast.type}`]"
      role="alert"
    >
      {{ toast.message }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import draggable from 'vuedraggable';
import { useCategoryGrid } from '../composables/useCategoryGrid.js';

const props = defineProps({
  apiUrl: {
    type: String,
    required: true,
  },
  csrfToken: {
    type: String,
    required: true,
  },
});

const {
  categories,
  loading,
  error,
  searchQuery,
  sortField,
  sortOrder,
  currentPage,
  totalPages,
  fetchCategories,
  search,
  sort: doSort,
  goToPage,
  toggleActive,
  reorder,
  deleteCategory,
} = useCategoryGrid(props.apiUrl, props.csrfToken);

const perPage = ref(10);
const totalItems = computed(() => categories.value.length > 0 ? totalPages.value * perPage.value : 0);
const categoryToDelete = ref(null);
const deleting = ref(false);
const toast = ref({ show: false, message: '', type: 'success' });
let searchTimeout = null;

// Search with debounce
const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    search(searchQuery.value);
  }, 300);
};

const clearSearch = () => {
  searchQuery.value = '';
  search('');
};

// Sorting
const sort = (field) => {
  doSort(field);
};

const getSortIcon = (field) => {
  if (sortField.value !== field) {
    return 'bi bi-chevron-expand text-muted';
  }
  return sortOrder.value === 'asc' ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
};

// Actions
const handleToggleActive = async (category) => {
  const result = await toggleActive(category);
  showToast(result.message, result.success ? 'success' : 'error');
};

const handleDelete = (category) => {
  categoryToDelete.value = category;
};

const cancelDelete = () => {
  categoryToDelete.value = null;
};

const confirmDeleteCategory = async () => {
  if (!categoryToDelete.value) return;

  deleting.value = true;
  try {
    const result = await deleteCategory(categoryToDelete.value.id, props.csrfToken);
    showToast(result.message, result.success ? 'success' : 'error');
    categoryToDelete.value = null;
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    deleting.value = false;
  }
};

const handleDragEnd = async () => {
  const items = categories.value.map((cat, index) => ({
    id: cat.id,
    displayOrder: index,
  }));

  // Update local display order
  categories.value.forEach((cat, index) => {
    cat.displayOrder = index;
  });

  const result = await reorder(items);
  if (!result.success) {
    showToast(result.message, 'error');
    await fetchCategories(); // Revert on failure
  } else {
    showToast(result.message, 'success');
  }
};

// Pagination
const visiblePages = computed(() => {
  const pagesArr = [];
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pagesArr.push(i);
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pagesArr.push(i);
      pagesArr.push(total);
    } else if (current >= total - 3) {
      pagesArr.push(1);
      for (let i = total - 4; i <= total; i++) pagesArr.push(i);
    } else {
      pagesArr.push(1);
      for (let i = current - 1; i <= current + 1; i++) pagesArr.push(i);
      pagesArr.push(total);
    }
  }

  return pagesArr;
});

// Toast
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

onMounted(() => {
  fetchCategories();
});
</script>

<style scoped>
.category-grid {
  width: 100%;
}

.grid-header h2 {
  font-size: 1.75rem;
  font-weight: 600;
  color: var(--neutral-900, #0f172a);
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.sortable:hover {
  background-color: var(--neutral-100, #f1f5f9);
}

.sortable i {
  font-size: 0.75rem;
  margin-left: 0.25rem;
}

.table > :not(caption) > * > * {
  padding: 1rem 0.75rem;
}

.drag-handle {
  cursor: move;
  color: var(--neutral-400, #94a3b8);
  transition: color 0.2s;
}

.drag-handle:hover {
  color: var(--neutral-600, #475569);
}

.badge {
  font-weight: 500;
  font-size: 0.75rem;
  padding: 0.35em 0.65em;
}

.btn-group-sm > .btn {
  padding: 0.25rem 0.5rem;
}

.modal.show {
  display: block;
}

.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 12px 24px;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  z-index: 9999;
  animation: slideIn 0.3s ease-out;
}

.toast-success {
  background-color: #10b981;
}

.toast-error {
  background-color: #ef4444;
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
</style>
