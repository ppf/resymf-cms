<template>
  <div class="page-grid">
    <!-- Search Bar -->
    <div class="mb-3">
      <input
        type="text"
        class="form-control"
        placeholder="Search pages by title, slug, or content..."
        :value="searchQuery"
        @input="handleSearch"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading && pages.length === 0" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <!-- Grid Table -->
    <div v-if="!loading || pages.length > 0" class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th @click="sort('id')" class="sortable" style="width: 60px">
              ID
              <span v-if="sortField === 'id'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('title')" class="sortable">
              Title
              <span v-if="sortField === 'title'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('slug')" class="sortable">
              Slug
              <span v-if="sortField === 'slug'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th>Categories</th>
            <th @click="sort('isPublished')" class="sortable" style="width: 100px">
              Published
              <span v-if="sortField === 'isPublished'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('isHomepage')" class="sortable" style="width: 100px">
              Homepage
              <span v-if="sortField === 'isHomepage'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('viewCount')" class="sortable" style="width: 80px">
              Views
              <span v-if="sortField === 'viewCount'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('createdAt')" class="sortable" style="width: 120px">
              Created
              <span v-if="sortField === 'createdAt'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th style="width: 200px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="page in pages" :key="page.id">
            <td>{{ page.id }}</td>
            <td>
              <strong>{{ page.title }}</strong>
              <div v-if="page.author" class="text-muted small">
                by {{ page.author }}
              </div>
            </td>
            <td><code>{{ page.slug }}</code></td>
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
            <td>
              <button
                @click="handleTogglePublished(page)"
                :class="['btn', 'btn-sm', page.isPublished ? 'btn-success' : 'btn-secondary']"
              >
                {{ page.isPublished ? 'Yes' : 'No' }}
              </button>
            </td>
            <td>
              <button
                @click="handleToggleHomepage(page)"
                :class="['btn', 'btn-sm', page.isHomepage ? 'btn-primary' : 'btn-outline-primary']"
                :disabled="page.isHomepage"
              >
                {{ page.isHomepage ? 'Yes' : 'Set' }}
              </button>
            </td>
            <td>{{ page.viewCount }}</td>
            <td>{{ formatDate(page.createdAt) }}</td>
            <td>
              <a
                :href="`/admin/pages/${page.id}`"
                class="btn btn-sm btn-info me-1"
                title="View"
              >
                View
              </a>
              <a
                :href="`/admin/pages/${page.id}/edit`"
                class="btn btn-sm btn-warning me-1"
                title="Edit"
              >
                Edit
              </a>
              <button
                @click="handleDelete(page)"
                :class="[
                  'btn',
                  'btn-sm',
                  deleteConfirm === page.id ? 'btn-danger' : 'btn-outline-danger'
                ]"
                :title="page.isHomepage ? 'Cannot delete homepage' : 'Delete'"
              >
                {{ deleteConfirm === page.id ? 'Confirm?' : 'Delete' }}
              </button>
            </td>
          </tr>
          <tr v-if="pages.length === 0 && !loading">
            <td colspan="9" class="text-center text-muted py-4">
              No pages found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="totalPages > 1" aria-label="Page pagination">
      <ul class="pagination justify-content-center">
        <li class="page-item" :class="{ disabled: currentPage === 1 }">
          <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
        </li>
        <li
          v-for="page in visiblePages"
          :key="page"
          class="page-item"
          :class="{ active: currentPage === page }"
        >
          <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
        </li>
        <li class="page-item" :class="{ disabled: currentPage === totalPages }">
          <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
        </li>
      </ul>
    </nav>

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
  sort,
  goToPage,
  togglePublished,
  toggleHomepage,
  deletePage,
} = usePageGrid(props.apiUrl, props.csrfToken);

const deleteConfirm = ref(null);
const toast = ref({ show: false, message: '', type: 'success' });
let deleteTimeout = null;

const handleSearch = (event) => {
  search(event.target.value);
};

const handleTogglePublished = async (page) => {
  const result = await togglePublished(page);
  showToast(result.message, result.success ? 'success' : 'error');
};

const handleToggleHomepage = async (page) => {
  const result = await toggleHomepage(page);
  showToast(result.message, result.success ? 'success' : 'error');
};

const handleDelete = async (page) => {
  if (page.isHomepage) {
    showToast('Cannot delete homepage page', 'error');
    return;
  }

  if (deleteConfirm.value === page.id) {
    clearTimeout(deleteTimeout);
    deleteConfirm.value = null;

    const result = await deletePage(page.id, props.csrfToken);
    showToast(result.message, result.success ? 'success' : 'error');
  } else {
    deleteConfirm.value = page.id;
    deleteTimeout = setTimeout(() => {
      deleteConfirm.value = null;
    }, 3000);
  }
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const visiblePages = computed(() => {
  const pages = [];
  const maxVisible = 7;
  const total = totalPages.value;
  const current = currentPage.value;

  if (total <= maxVisible) {
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i);
      pages.push('...');
      pages.push(total);
    } else if (current >= total - 3) {
      pages.push(1);
      pages.push('...');
      for (let i = total - 4; i <= total; i++) pages.push(i);
    } else {
      pages.push(1);
      pages.push('...');
      for (let i = current - 1; i <= current + 1; i++) pages.push(i);
      pages.push('...');
      pages.push(total);
    }
  }

  return pages.filter(p => p !== '...' || pages.indexOf(p) === pages.lastIndexOf(p));
});

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
.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 12px 24px;
  border-radius: 4px;
  color: white;
  font-weight: 500;
  z-index: 9999;
  animation: slideIn 0.3s ease-out;
}

.toast-success {
  background-color: #28a745;
}

.toast-error {
  background-color: #dc3545;
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
