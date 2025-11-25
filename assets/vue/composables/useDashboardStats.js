import { ref } from 'vue';

export function useDashboardStats(apiUrl) {
  const stats = ref(null);
  const loading = ref(false);
  const error = ref(null);

  const fetchStats = async () => {
    loading.value = true;
    error.value = null;

    try {
      const response = await fetch(apiUrl);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      stats.value = data;
    } catch (err) {
      console.error('Failed to fetch dashboard stats:', err);
      error.value = 'Failed to load statistics. Please try again later.';
    } finally {
      loading.value = false;
    }
  };

  const refresh = () => {
    fetchStats();
  };

  return {
    stats,
    loading,
    error,
    fetchStats,
    refresh,
  };
}
