<template>
  <div class="customers-grid">
    <!-- Header with Search -->
    <div class="grid-header mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h2 class="mb-0">Users</h2>
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
              placeholder="Search by username or email..."
              @input="debouncedSearch"
            />
            <button v-if="searchQuery" class="btn btn-outline-secondary" @click="clearSearch">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !users.length" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="text-muted mt-3">Loading users...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchUsers">
        Try Again
      </button>
    </div>

    <!-- Users Table -->
    <div v-else-if="users.length" class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th @click="sort('id')" class="sortable">
                ID
                <i :class="getSortIcon('id')"></i>
              </th>
              <th @click="sort('username')" class="sortable">
                Username
                <i :class="getSortIcon('username')"></i>
              </th>
              <th @click="sort('email')" class="sortable">
                Email
                <i :class="getSortIcon('email')"></i>
              </th>
              <th>Roles</th>
              <th @click="sort('isActive')" class="sortable text-center">
                Status
                <i :class="getSortIcon('isActive')"></i>
              </th>
              <th @click="sort('createdAt')" class="sortable">
                Created
                <i :class="getSortIcon('createdAt')"></i>
              </th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td class="text-muted">{{ user.id }}</td>
              <td class="fw-semibold">{{ user.username }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span
                  v-for="role in user.roles"
                  :key="role"
                  :class="getRoleBadgeClass(role)"
                  class="badge me-1"
                >
                  {{ formatRole(role) }}
                </span>
              </td>
              <td class="text-center">
                <span v-if="user.isActive" class="badge bg-success">
                  <i class="bi bi-check-circle-fill"></i> Active
                </span>
                <span v-else class="badge bg-secondary">
                  <i class="bi bi-x-circle-fill"></i> Inactive
                </span>
              </td>
              <td class="text-muted small">
                {{ formatDate(user.createdAt) }}
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a
                    :href="`/admin/users/${user.id}/edit`"
                    class="btn btn-outline-primary"
                    title="Edit user"
                  >
                    <i class="bi bi-pencil"></i>
                  </a>
                  <button
                    class="btn btn-outline-danger"
                    title="Delete user"
                    @click="confirmDelete(user)"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Showing {{ ((meta.page - 1) * meta.limit) + 1 }} to
          {{ Math.min(meta.page * meta.limit, meta.total) }} of {{ meta.total }} users
        </div>
        <nav aria-label="Users pagination">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !meta.has_prev }">
              <button class="page-link" @click="goToPage(meta.page - 1)" :disabled="!meta.has_prev">
                <i class="bi bi-chevron-left"></i>
              </button>
            </li>
            <li
              v-for="page in visiblePages"
              :key="page"
              class="page-item"
              :class="{ active: page === meta.page }"
            >
              <button class="page-link" @click="goToPage(page)">
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: !meta.has_next }">
              <button class="page-link" @click="goToPage(meta.page + 1)" :disabled="!meta.has_next">
                <i class="bi bi-chevron-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-5">
      <i class="bi bi-people" style="font-size: 4rem; color: var(--neutral-300);"></i>
      <p class="text-muted mt-3">
        {{ searchQuery ? 'No users found matching your search.' : 'No users yet.' }}
      </p>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      v-if="userToDelete"
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
            <p>Are you sure you want to delete user <strong>{{ userToDelete.username }}</strong>?</p>
            <p class="text-danger mb-0">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              This action cannot be undone.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cancelDelete">Cancel</button>
            <button type="button" class="btn btn-danger" @click="deleteUser" :disabled="deleting">
              <span v-if="deleting" class="spinner-border spinner-border-sm me-2"></span>
              Delete User
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const users = ref([]);
const loading = ref(false);
const error = ref(null);
const searchQuery = ref('');
const sortField = ref('createdAt');
const sortOrder = ref('DESC');
const userToDelete = ref(null);
const deleting = ref(false);

const meta = ref({
  page: 1,
  limit: 10,
  total: 0,
  pages: 0,
  has_next: false,
  has_prev: false,
});

let searchTimeout = null;

// Fetch users from API
const fetchUsers = async () => {
  loading.value = true;
  error.value = null;

  try {
    const params = new URLSearchParams({
      page: meta.value.page.toString(),
      limit: meta.value.limit.toString(),
      sort: sortField.value,
      order: sortOrder.value,
    });

    if (searchQuery.value) {
      params.append('q', searchQuery.value);
    }

    const response = await fetch(`/api/users?${params}`);

    if (!response.ok) {
      throw new Error(`Failed to load users (${response.status})`);
    }

    const data = await response.json();
    users.value = data.items;
    meta.value = data.meta;
  } catch (err) {
    error.value = err.message;
    console.error('Error fetching users:', err);
  } finally {
    loading.value = false;
  }
};

// Debounced search
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    meta.value.page = 1; // Reset to first page on search
    fetchUsers();
  }, 300);
};

// Clear search
const clearSearch = () => {
  searchQuery.value = '';
  meta.value.page = 1;
  fetchUsers();
};

// Sorting
const sort = (field) => {
  if (sortField.value === field) {
    sortOrder.value = sortOrder.value === 'ASC' ? 'DESC' : 'ASC';
  } else {
    sortField.value = field;
    sortOrder.value = 'ASC';
  }
  fetchUsers();
};

const getSortIcon = (field) => {
  if (sortField.value !== field) {
    return 'bi bi-chevron-expand text-muted';
  }
  return sortOrder.value === 'ASC' ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
};

// Pagination
const goToPage = (page) => {
  if (page < 1 || page > meta.value.pages) return;
  meta.value.page = page;
  fetchUsers();
};

const visiblePages = computed(() => {
  const pages = [];
  const total = meta.value.pages;
  const current = meta.value.page;

  if (total <= 7) {
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

  return pages;
});

// Delete user
const confirmDelete = (user) => {
  userToDelete.value = user;
};

const cancelDelete = () => {
  userToDelete.value = null;
};

const deleteUser = async () => {
  if (!userToDelete.value) return;

  deleting.value = true;
  try {
    const response = await fetch(`/api/users/${userToDelete.value.id}`, {
      method: 'DELETE',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.error || 'Failed to delete user');
    }

    // Refresh the list
    await fetchUsers();
    userToDelete.value = null;
  } catch (err) {
    error.value = err.message;
    console.error('Error deleting user:', err);
  } finally {
    deleting.value = false;
  }
};

// Formatting helpers
const formatRole = (role) => {
  return role.replace('ROLE_', '').replace('_', ' ');
};

const getRoleBadgeClass = (role) => {
  if (role === 'ROLE_ADMIN') return 'bg-danger';
  if (role === 'ROLE_EDITOR') return 'bg-warning';
  return 'bg-secondary';
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Initialize
onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
.customers-grid {
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
</style>
