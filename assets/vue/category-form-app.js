import { createApp } from 'vue';
import CategoryForm from './components/CategoryForm.vue';

const mount = () => {
  const target = document.querySelector('[data-vue-island="category-form"]');
  if (!target) {
    return;
  }

  const categoryId = target.dataset.categoryId ? parseInt(target.dataset.categoryId, 10) : null;
  const apiUrl = target.dataset.apiUrl;
  const cancelUrl = target.dataset.cancelUrl || '/admin/categories';

  createApp(CategoryForm, { categoryId, apiUrl, cancelUrl }).mount(target);
};

document.addEventListener('DOMContentLoaded', mount);
