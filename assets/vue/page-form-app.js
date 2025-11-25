import { createApp } from 'vue';
import PageForm from './components/PageForm.vue';

const mount = () => {
  const target = document.querySelector('[data-vue-island="page-form"]');
  if (!target) return;

  const pageId = target.dataset.pageId ? parseInt(target.dataset.pageId, 10) : null;
  const apiUrl = target.dataset.apiUrl;
  const cancelUrl = target.dataset.cancelUrl || '/admin/pages';
  const categoriesData = target.dataset.categoriesData || '[]';

  createApp(PageForm, {
    pageId,
    apiUrl,
    cancelUrl,
    categoriesData,
  }).mount(target);
};

document.addEventListener('DOMContentLoaded', mount);
