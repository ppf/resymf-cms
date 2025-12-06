import { createApp } from 'vue';
import CronJobGrid from './components/CronJobGrid.vue';

const mount = () => {
  const target = document.querySelector('[data-vue-island="cron-job-grid"]');
  if (!target) {
    return;
  }

  const apiUrl = target.dataset.apiUrl || '/api/cron-jobs/list';
  const csrfToken = target.dataset.csrfToken || '';

  createApp(CronJobGrid, { apiUrl, csrfToken }).mount(target);
};

document.addEventListener('DOMContentLoaded', mount);
