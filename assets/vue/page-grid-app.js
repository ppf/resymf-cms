import { createApp } from 'vue';
import PageGrid from './components/PageGrid.vue';

const mount = () => {
  const target = document.querySelector('[data-vue-island="page-grid"]');
  if (!target) {
    return;
  }

  const apiUrl = target.dataset.apiUrl || '/api/pages/list';
  const csrfToken = target.dataset.csrfToken || '';

  createApp(PageGrid, { apiUrl, csrfToken }).mount(target);
};

document.addEventListener('DOMContentLoaded', mount);
