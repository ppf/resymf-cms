import { ref, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';

export function useCronJobGrid(apiUrl, csrfToken) {
  const jobs = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const searchQuery = ref('');
  const sortField = ref('name');
  const sortOrder = ref('asc');
  const currentPage = ref(1);
  const perPage = ref(20);
  const total = ref(0);

  const totalPages = computed(() => Math.ceil(total.value / perPage.value));

  const fetchJobs = async () => {
    loading.value = true;
    error.value = null;

    try {
      const params = new URLSearchParams({
        search: searchQuery.value,
        sort: sortField.value,
        order: sortOrder.value,
        page: currentPage.value,
        perPage: perPage.value,
      });

      const response = await fetch(`${apiUrl}?${params}`);
      if (!response.ok) throw new Error('Failed to fetch cron jobs');

      const data = await response.json();
      jobs.value = data.data;
      total.value = data.total;
      currentPage.value = data.page;
    } catch (err) {
      error.value = err.message;
      console.error('Error fetching cron jobs:', err);
    } finally {
      loading.value = false;
    }
  };

  const debouncedFetch = useDebounceFn(fetchJobs, 300);

  const search = (query) => {
    searchQuery.value = query;
    currentPage.value = 1;
    debouncedFetch();
  };

  const sort = (field) => {
    if (sortField.value === field) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortOrder.value = 'asc';
    }
    currentPage.value = 1;
    fetchJobs();
  };

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
      fetchJobs();
    }
  };

  const toggleActive = async (job) => {
    const originalStatus = job.isActive;
    job.isActive = !job.isActive; // Optimistic update

    try {
      const response = await fetch(`/api/cron-jobs/${job.id}/toggle-active`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_token=${encodeURIComponent(csrfToken)}`,
      });

      if (!response.ok) {
        throw new Error('Failed to toggle job status');
      }

      const data = await response.json();
      return { success: true, message: data.message };
    } catch (err) {
      job.isActive = originalStatus; // Revert on failure
      return { success: false, message: err.message };
    }
  };

  const runJob = async (job) => {
    try {
      const response = await fetch(`/api/cron-jobs/${job.id}/run`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_token=${encodeURIComponent(csrfToken)}`,
      });

      if (!response.ok) {
        throw new Error('Failed to run job');
      }

      const data = await response.json();

      // Refresh to get updated status
      setTimeout(fetchJobs, 1000);

      return { success: true, message: data.message };
    } catch (err) {
      return { success: false, message: err.message };
    }
  };

  const deleteJob = async (id) => {
    try {
      const response = await fetch(`/api/cron-jobs/${id}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-Token': csrfToken,
        },
      });

      if (!response.ok) throw new Error('Failed to delete job');

      await fetchJobs(); // Refresh list
      return { success: true, message: 'Cron job deleted' };
    } catch (err) {
      return { success: false, message: err.message };
    }
  };

  return {
    jobs,
    loading,
    error,
    searchQuery,
    sortField,
    sortOrder,
    currentPage,
    perPage,
    total,
    totalPages,
    fetchJobs,
    search,
    sort,
    goToPage,
    toggleActive,
    runJob,
    deleteJob,
  };
}
