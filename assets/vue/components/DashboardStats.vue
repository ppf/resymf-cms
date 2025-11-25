<template>
  <div class="dashboard-stats">
    <!-- Loading State -->
    <div v-if="loading && !stats" class="stats-grid">
      <div v-for="i in 4" :key="i" class="stat-card loading">
        <div class="stat-icon">
          <div class="skeleton skeleton-icon"></div>
        </div>
        <div class="stat-value">
          <div class="skeleton skeleton-text"></div>
        </div>
        <div class="stat-label">
          <div class="skeleton skeleton-text-sm"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error && !stats" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle"></i>
      {{ error }}
      <button @click="refresh" class="btn btn-sm btn-outline-danger ms-2">
        Retry
      </button>
    </div>

    <!-- Stats Grid -->
    <div v-if="stats" class="stats-grid">
      <!-- Users Stat -->
      <div class="stat-card">
        <div class="stat-icon users">
          <i class="bi bi-people"></i>
        </div>
        <div class="stat-value">{{ stats.users.total }}</div>
        <div class="stat-label">Total Users</div>
        <div v-if="stats.users.newThisMonth > 0" class="stat-trend positive">
          <i class="bi bi-arrow-up"></i>
          +{{ stats.users.newThisMonth }} this month
        </div>
        <div class="stat-details">
          <span class="badge bg-success">{{ stats.users.active }} active</span>
          <span v-if="stats.users.inactive > 0" class="badge bg-secondary">
            {{ stats.users.inactive }} inactive
          </span>
        </div>
      </div>

      <!-- Pages Stat -->
      <div class="stat-card">
        <div class="stat-icon pages">
          <i class="bi bi-file-text"></i>
        </div>
        <div class="stat-value">{{ stats.pages.published }}</div>
        <div class="stat-label">Published Pages</div>
        <div v-if="stats.pages.drafts > 0" class="stat-trend neutral">
          <i class="bi bi-pencil"></i>
          {{ stats.pages.drafts }} draft{{ stats.pages.drafts !== 1 ? 's' : '' }}
        </div>
        <div class="stat-details">
          <span class="badge bg-primary">{{ stats.pages.total }} total</span>
        </div>
      </div>

      <!-- Categories Stat -->
      <div class="stat-card">
        <div class="stat-icon categories">
          <i class="bi bi-tags"></i>
        </div>
        <div class="stat-value">{{ stats.categories.total }}</div>
        <div class="stat-label">Categories</div>
        <div class="stat-details">
          <span class="badge bg-success">{{ stats.categories.active }} active</span>
          <span v-if="stats.categories.inactive > 0" class="badge bg-secondary">
            {{ stats.categories.inactive }} inactive
          </span>
        </div>
      </div>

      <!-- Activity Stat -->
      <div class="stat-card">
        <div class="stat-icon activity">
          <i class="bi bi-activity"></i>
        </div>
        <div class="stat-value">{{ stats.activity.recentPages }}</div>
        <div class="stat-label">Recent Activity</div>
        <div class="stat-trend neutral">
          <i class="bi bi-clock"></i>
          Last {{ stats.activity.period }}
        </div>
        <div class="stat-details">
          <span class="badge bg-info">Pages created</span>
        </div>
      </div>
    </div>

    <!-- Refresh Button -->
    <div v-if="stats" class="text-end mt-3">
      <button
        @click="refresh"
        :disabled="loading"
        class="btn btn-sm btn-outline-secondary"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="bi bi-arrow-clockwise"></i>
        Refresh
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useDashboardStats } from '../composables/useDashboardStats.js';

const props = defineProps({
  apiUrl: {
    type: String,
    required: true,
  },
  autoRefresh: {
    type: Boolean,
    default: false,
  },
  refreshInterval: {
    type: Number,
    default: 300000, // 5 minutes
  },
});

const { stats, loading, error, fetchStats, refresh } = useDashboardStats(props.apiUrl);

onMounted(() => {
  fetchStats();

  // Auto-refresh if enabled
  if (props.autoRefresh) {
    setInterval(() => {
      fetchStats();
    }, props.refreshInterval);
  }
});
</script>

<style scoped>
.dashboard-stats {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1rem;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s, box-shadow 0.2s;
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-card.loading {
  pointer-events: none;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  margin-bottom: 1rem;
  color: white;
}

.stat-icon.users {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.pages {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.categories {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.activity {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  line-height: 1.2;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #718096;
  font-weight: 500;
  margin-bottom: 0.75rem;
}

.stat-trend {
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-bottom: 0.75rem;
}

.stat-trend.positive {
  color: #38a169;
}

.stat-trend.negative {
  color: #e53e3e;
}

.stat-trend.neutral {
  color: #718096;
}

.stat-trend i {
  font-size: 0.875rem;
}

.stat-details {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.stat-details .badge {
  font-size: 0.7rem;
  font-weight: 500;
  padding: 0.25rem 0.5rem;
}

/* Loading Skeletons */
.skeleton {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s ease-in-out infinite;
  border-radius: 4px;
}

.skeleton-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
}

.skeleton-text {
  width: 80px;
  height: 32px;
  margin-bottom: 0.5rem;
}

.skeleton-text-sm {
  width: 100px;
  height: 14px;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Alert styling */
.alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-value {
    font-size: 1.75rem;
  }
}
</style>
