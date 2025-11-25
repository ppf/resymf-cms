import { createApp } from 'vue';
import DashboardHello from './components/DashboardHello.vue';
import DashboardStats from './components/DashboardStats.vue';

const mount = () => {
  // Mount Dashboard Hello widget
  const helloTarget = document.querySelector('[data-vue-island="dashboard-hello"]');
  if (helloTarget) {
    const username = helloTarget.dataset.username || 'Admin';
    createApp(DashboardHello, { username }).mount(helloTarget);
  }

  // Mount Dashboard Stats widget
  const statsTarget = document.querySelector('[data-vue-island="dashboard-stats"]');
  if (statsTarget) {
    const apiUrl = statsTarget.dataset.apiUrl;
    const autoRefresh = statsTarget.dataset.autoRefresh === 'true';
    createApp(DashboardStats, { apiUrl, autoRefresh }).mount(statsTarget);
  }
};

document.addEventListener('DOMContentLoaded', mount);
