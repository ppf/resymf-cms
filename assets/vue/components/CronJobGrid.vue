<template>
  <div class="cron-job-grid">
    <!-- Header with Search -->
    <div class="grid-header mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h2 class="mb-0">Cron Jobs</h2>
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
              placeholder="Search by name or command..."
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
    <div v-if="loading && jobs.length === 0" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-3">Loading cron jobs...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchJobs">
        Try Again
      </button>
    </div>

    <!-- Cron Jobs Table -->
    <div v-else-if="jobs.length" class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th @click="sort('id')" class="sortable" style="width: 60px">
                ID
                <i :class="getSortIcon('id')"></i>
              </th>
              <th @click="sort('name')" class="sortable">
                Name
                <i :class="getSortIcon('name')"></i>
              </th>
              <th @click="sort('command')" class="sortable">
                Command
                <i :class="getSortIcon('command')"></i>
              </th>
              <th @click="sort('cronExpression')" class="sortable" style="width: 120px">
                Schedule
                <i :class="getSortIcon('cronExpression')"></i>
              </th>
              <th @click="sort('isActive')" class="sortable text-center" style="width: 80px">
                Active
                <i :class="getSortIcon('isActive')"></i>
              </th>
              <th @click="sort('lastStatus')" class="sortable text-center" style="width: 100px">
                Status
                <i :class="getSortIcon('lastStatus')"></i>
              </th>
              <th @click="sort('lastRunAt')" class="sortable" style="width: 140px">
                Last Run
                <i :class="getSortIcon('lastRunAt')"></i>
              </th>
              <th class="text-end" style="width: 140px">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="job in jobs" :key="job.id">
              <td class="text-muted">{{ job.id }}</td>
              <td>
                <span class="fw-semibold">{{ job.name }}</span>
                <div v-if="job.description" class="text-muted small">
                  {{ truncate(job.description, 50) }}
                </div>
              </td>
              <td><code class="text-muted small">{{ truncate(job.command, 40) }}</code></td>
              <td><code class="text-muted small">{{ job.cronExpression }}</code></td>
              <td class="text-center">
                <button
                  @click="handleToggleActive(job)"
                  :class="['btn', 'btn-sm', job.isActive ? 'btn-success' : 'btn-secondary']"
                >
                  {{ job.isActive ? 'Yes' : 'No' }}
                </button>
              </td>
              <td class="text-center">
                <span :class="getStatusBadgeClass(job.lastStatus)">
                  {{ job.lastStatus || '—' }}
                </span>
              </td>
              <td class="text-muted small">{{ job.lastRunAt ? formatDate(job.lastRunAt) : '—' }}</td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <button
                    @click="handleRun(job)"
                    class="btn btn-outline-success"
                    title="Run now"
                    :disabled="runningJob === job.id"
                  >
                    <span v-if="runningJob === job.id" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="bi bi-play-fill"></i>
                  </button>
                  <a
                    :href="`/admin/cron-jobs/${job.id}`"
                    class="btn btn-outline-info"
                    title="View job"
                  >
                    <i class="bi bi-eye"></i>
                  </a>
                  <a
                    :href="`/admin/cron-jobs/${job.id}/edit`"
                    class="btn btn-outline-primary"
                    title="Edit job"
                  >
                    <i class="bi bi-pencil"></i>
                  </a>
                  <button
                    @click="handleDelete(job)"
                    class="btn btn-outline-danger"
                    title="Delete job"
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
          {{ Math.min(currentPage * perPage, totalItems) }} of {{ totalItems }} jobs
        </div>
        <nav v-if="totalPages > 1" aria-label="Cron jobs pagination">
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
      <i class="bi bi-clock-history" style="font-size: 4rem; color: var(--neutral-300);"></i>
      <p class="text-muted mt-3">
        {{ searchQuery ? 'No cron jobs found matching your search.' : 'No cron jobs yet.' }}
      </p>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="jobToDelete"
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
            <p>Are you sure you want to delete cron job <strong>{{ jobToDelete.name }}</strong>?</p>
            <p class="text-danger mb-0">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              This action cannot be undone.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cancelDelete">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDeleteJob" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              Delete Job
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
import { useCronJobGrid } from '../composables/useCronJobGrid.js';

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
  jobs,
  loading,
  error,
  searchQuery,
  sortField,
  sortOrder,
  currentPage,
  totalPages,
  fetchJobs,
  search,
  sort: doSort,
  goToPage,
  toggleActive,
  runJob,
  deleteJob,
} = useCronJobGrid(props.apiUrl, props.csrfToken);

const perPage = ref(10);
const totalItems = computed(() => jobs.value.length > 0 ? totalPages.value * perPage.value : 0);
const jobToDelete = ref(null);
const deleting = ref(false);
const runningJob = ref(null);
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
const handleToggleActive = async (job) => {
  const result = await toggleActive(job);
  showToast(result.message, result.success ? 'success' : 'error');
};

const handleRun = async (job) => {
  runningJob.value = job.id;
  const result = await runJob(job);
  runningJob.value = null;
  showToast(result.message, result.success ? 'success' : 'error');
};

const handleDelete = (job) => {
  jobToDelete.value = job;
};

const cancelDelete = () => {
  jobToDelete.value = null;
};

const confirmDeleteJob = async () => {
  if (!jobToDelete.value) return;

  deleting.value = true;
  try {
    const result = await deleteJob(jobToDelete.value.id);
    showToast(result.message, result.success ? 'success' : 'error');
    jobToDelete.value = null;
  } catch (err) {
    showToast(err.message, 'error');
  } finally {
    deleting.value = false;
  }
};

// Helpers
const truncate = (str, len) => {
  if (!str) return '';
  return str.length > len ? str.slice(0, len) + '...' : str;
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const getStatusBadgeClass = (status) => {
  const classes = {
    success: 'badge bg-success',
    failed: 'badge bg-danger',
    running: 'badge bg-warning',
    locked: 'badge bg-info',
  };
  return classes[status] || 'badge bg-secondary';
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
  fetchJobs();
});
</script>

<style scoped>
.cron-job-grid {
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
