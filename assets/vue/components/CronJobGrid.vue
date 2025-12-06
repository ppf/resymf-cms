<template>
  <div class="cron-job-grid">
    <!-- Search Bar -->
    <div class="mb-3">
      <input
        type="text"
        class="form-control"
        placeholder="Search by name or command..."
        :value="searchQuery"
        @input="handleSearch"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading && jobs.length === 0" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <!-- Grid Table -->
    <div v-if="!loading || jobs.length > 0" class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th @click="sort('id')" class="sortable" style="width: 60px">
              ID
              <span v-if="sortField === 'id'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('name')" class="sortable">
              Name
              <span v-if="sortField === 'name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('command')" class="sortable">
              Command
              <span v-if="sortField === 'command'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('cronExpression')" class="sortable" style="width: 120px">
              Schedule
              <span v-if="sortField === 'cronExpression'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('isActive')" class="sortable" style="width: 80px">
              Active
              <span v-if="sortField === 'isActive'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('lastStatus')" class="sortable" style="width: 100px">
              Status
              <span v-if="sortField === 'lastStatus'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('lastRunAt')" class="sortable" style="width: 140px">
              Last Run
              <span v-if="sortField === 'lastRunAt'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th style="width: 200px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="job in jobs" :key="job.id">
            <td>{{ job.id }}</td>
            <td>
              <strong>{{ job.name }}</strong>
              <div v-if="job.description" class="text-muted small">
                {{ truncate(job.description, 50) }}
              </div>
            </td>
            <td><code class="small">{{ truncate(job.command, 40) }}</code></td>
            <td><code class="small">{{ job.cronExpression }}</code></td>
            <td>
              <button
                @click="handleToggleActive(job)"
                :class="['btn', 'btn-sm', job.isActive ? 'btn-success' : 'btn-secondary']"
              >
                {{ job.isActive ? 'Yes' : 'No' }}
              </button>
            </td>
            <td>
              <span :class="getStatusBadgeClass(job.lastStatus)">
                {{ job.lastStatus || '—' }}
              </span>
            </td>
            <td>{{ job.lastRunAt ? formatDate(job.lastRunAt) : '—' }}</td>
            <td>
              <button
                @click="handleRun(job)"
                class="btn btn-sm btn-outline-success me-1"
                title="Run Now"
                :disabled="runningJob === job.id"
              >
                <span v-if="runningJob === job.id" class="spinner-border spinner-border-sm"></span>
                <span v-else>Run</span>
              </button>
              <a
                :href="`/admin/cron-jobs/${job.id}`"
                class="btn btn-sm btn-info me-1"
                title="View"
              >
                View
              </a>
              <a
                :href="`/admin/cron-jobs/${job.id}/edit`"
                class="btn btn-sm btn-warning me-1"
                title="Edit"
              >
                Edit
              </a>
              <button
                @click="handleDelete(job)"
                :class="[
                  'btn',
                  'btn-sm',
                  deleteConfirm === job.id ? 'btn-danger' : 'btn-outline-danger'
                ]"
                title="Delete"
              >
                {{ deleteConfirm === job.id ? 'Confirm?' : 'Delete' }}
              </button>
            </td>
          </tr>
          <tr v-if="jobs.length === 0 && !loading">
            <td colspan="8" class="text-center text-muted py-4">
              No cron jobs found
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <nav v-if="totalPages > 1" aria-label="Cron jobs pagination">
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
  sort,
  goToPage,
  toggleActive,
  runJob,
  deleteJob,
} = useCronJobGrid(props.apiUrl, props.csrfToken);

const deleteConfirm = ref(null);
const runningJob = ref(null);
const toast = ref({ show: false, message: '', type: 'success' });
let deleteTimeout = null;

const handleSearch = (event) => {
  search(event.target.value);
};

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

const handleDelete = async (job) => {
  if (deleteConfirm.value === job.id) {
    clearTimeout(deleteTimeout);
    deleteConfirm.value = null;

    const result = await deleteJob(job.id);
    showToast(result.message, result.success ? 'success' : 'error');
  } else {
    deleteConfirm.value = job.id;
    deleteTimeout = setTimeout(() => {
      deleteConfirm.value = null;
    }, 3000);
  }
};

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
  fetchJobs();
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
