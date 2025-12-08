<template>
  <div class="page-grid">
    <!-- Header with Search -->
    <div class="grid-header mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h2 class="mb-0">Pages</h2>
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
              placeholder="Search pages by title, slug, or content..."
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
    <div v-if="loading && pages.length === 0" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-3">Loading pages...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchPages">
        Try Again
      </button>
    </div>

    <!-- Pages Table -->
    <div v-else-if="pages.length" class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th @click="sort('id')" class="sortable" style="width: 60px">
                ID
                <i :class="getSortIcon('id')"></i>
              </th>
              <th @click="sort('title')" class="sortable">
                Title
                <i :class="getSortIcon('title')"></i>
              </th>
              <th @click="sort('slug')" class="sortable">
                Slug
                <i :class="getSortIcon('slug')"></i>
              </th>
              <th>Categories</th>
              <th @click="sort('isPublished')" class="sortable text-center" style="width: 100px">
                Published
                <i :class="getSortIcon('isPublished')"></i>
              </th>
              <th @click="sort('isHomepage')" class="sortable text-center" style="width: 100px">
                Homepage
                <i :class="getSortIcon('isHomepage')"></i>
              </th>
              <th @click="sort('viewCount')" class="sortable text-center" style="width: 80px">
                Views
                <i :class="getSortIcon('viewCount')"></i>
              </th>
              <th @click="sort('createdAt')" class="sortable" style="width: 120px">
                Created
                <i :class="getSortIcon('createdAt')"></i>
              </th>
              <th class="text-end" style="width: 120px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="page in pages" :key="page.id">
              <td class="text-muted">{{ page.id }}</td>
              <td>
                <span class="fw-semibold">{{ page.title }}</span>
                <div v-if="page.author" class="text-muted small">
                  by {{ page.author }}
                </div>
              </td>
              <td><code class="text-muted">{{ page.slug }}</code></td>
              <td>
                <span
                  v-for="cat in page.categories"
                  :key="cat.id"
                  class="badge bg-secondary me-1"
                >
                  {{ cat.name }}
                </span>
                <span v-if="page.categories.length === 0" class="text-muted">—</span>
              </td>
              <td class="text-center">
                <button
                  @click="handleTogglePublished(page)"
                  :class="['btn', 'btn-sm', page.isPublished ? 'btn-success' : 'btn-secondary']"
                >
                  {{ page.isPublished ? 'Yes' : 'No' }}
                </button>
              </td>
              <td class="text-center">
                <button
                  @click="handleToggleHomepage(page)"
                  :class="['btn', 'btn-sm', page.isHomepage ? 'btn-primary' : 'btn-outline-primary']"
                  :disabled="page.isHomepage"
                >
                  {{ page.isHomepage ? 'Yes' : 'Set' }}
                </button>
              </td>
              <td class="text-center text-muted">{{ page.viewCount }}</td>
              <td class="text-muted small">{{ formatDate(page.createdAt) }}</td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a
                    :href="`/admin/pages/${page.id}`"
                    class="btn btn-outline-info"
                    title="View page"
                  >
                    <i class="bi bi-eye"></i>
                  </a>
                  <a
                    :href="`/admin/pages/${page.id}/edit`"
                    class="btn btn-outline-primary"
                    title="Edit page"
                  >
                    <i class="bi bi-pencil"></i>
                  </a>
                  <button
                    @click="handleDelete(page)"
                    class="btn btn-outline-danger"
                    :title="page.isHomepage ? 'Cannot delete homepage' : 'Delete page'"
                    :disabled="page.isHomepage"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Showing {{ ((currentPage - 1) * perPage) + 1 }} to
          {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} pages
        </div>
        <nav v-if="totalPages > 1" aria-label="Page pagination">
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
      <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: var(--neutral-300);"></i>
      <p class="text-muted mt-3">
        {{ searchQuery ? 'No pages found matching your search.' : 'No pages yet.' }}
      </p>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="pageToDelete"
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
            <p>Are you sure you want to delete page <strong>{{ pageToDelete.title }}</strong>?</p>
            <p class="text-danger mb-0">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              This action cannot be undone.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cancelDelete">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDeletePage" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              Delete Page
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
import { usePageGrid } from '../composables/usePageGrid.js';

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
  pages,
  loading,
  error,
  searchQuery,
  sortField,
  sortOrder,
  currentPage,
  totalPages,
  fetchPages,
  search,
  sort: doSort,
  goToPage,
  togglePublished,
  toggleHomepage,
  deletePage,
} = usePageGrid(props.apiUrl, props.csrfToken);

const perPage = ref(10);
const totalItems = computed(() => pages.value.length > 0 ? totalPages.value * perPage.value : 0);
const pageToDelete = ref(null);
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
const handleTogglePublished = async (page) => {
  const result = await togglePublished(page);
  showToast(result.message, result.success ? 'success' : 'error');
};

const handleToggleHomepage = async (page) => {
  const result = await toggleHomepage(page);
  showToast(result.message, result.success ? 'success' : 'error');
};

const handleDelete = (page) => {
  if (page.isHomepage) {
    showToast('Cannot delete homepage page', 'error');
    return;
  }
  pageToDelete.value = page;
};

const cancelDelete = () => {
  pageToDelete.value = null;
};

const confirmDeletePage = async () => {
  if (!pageToDelete.value) return;

  deleting.value = true;
  try {
    const result = await deletePage(pageToDelete.value.id, props.csrfToken);
    showToast(result.message, result.success ? 'success' : 'error');
    pageToDelete.value = null;
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    deleting.value = false;
  }
};

// Formatting
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
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
  fetchPages();
});
</script>

<style scoped>
.page-grid {
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
