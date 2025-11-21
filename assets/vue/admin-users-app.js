import { createApp } from 'vue';
import CustomersGrid from './components/CustomersGrid.vue';

const mount = () => {
  const target = document.querySelector('[data-vue-island="customers-grid"]');
  if (!target) {
    return;
  }

  createApp(CustomersGrid).mount(target);
};

document.addEventListener('DOMContentLoaded', mount);
