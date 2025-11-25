import { createApp } from 'vue';
import UserForm from './components/UserForm.vue';

const mount = () => {
  const target = document.querySelector('[data-vue-island="user-form"]');
  if (!target) {
    return;
  }

  const userId = target.dataset.userId ? parseInt(target.dataset.userId, 10) : null;
  const apiUrl = target.dataset.apiUrl;
  const cancelUrl = target.dataset.cancelUrl || '/admin/users';
  const themesData = target.dataset.themesData || '[]';

  createApp(UserForm, { userId, apiUrl, cancelUrl, themesData }).mount(target);
};

document.addEventListener('DOMContentLoaded', mount);
