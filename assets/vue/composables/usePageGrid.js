import { ref, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';

export function usePageGrid(apiUrl, csrfToken) {
  const pages = ref([]);
  const loading = ref(false);
  const error = ref(null);
  const searchQuery = ref('');
  const sortField = ref('createdAt');
  const sortOrder = ref('desc');
  const currentPage = ref(1);
  const perPage = ref(20);
  const total = ref(0);
  const categoryFilter = ref(null);

  const totalPages = computed(() => Math.ceil(total.value / perPage.value));

  const fetchPages = async () => {
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

      if (categoryFilter.value) {
        params.append('category', categoryFilter.value);
      }

      const response = await fetch(`${apiUrl}?${params}`);
      if (!response.ok) throw new Error('Failed to fetch pages');

      const data = await response.json();
      pages.value = data.data;
      total.value = data.total;
      currentPage.value = data.page;
    } catch (err) {
      error.value = err.message;
      console.error('Error fetching pages:', err);
    } finally {
      loading.value = false;
    }
  };

  const debouncedFetch = useDebounceFn(fetchPages, 300);

  const search = (query) => {
    searchQuery.value = query;
    currentPage.value = 1;
    debouncedFetch();
  };

  const filterByCategory = (categoryId) => {
    categoryFilter.value = categoryId;
    currentPage.value = 1;
    fetchPages();
  };

  const sort = (field) => {
    if (sortField.value === field) {
      sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortField.value = field;
      sortOrder.value = 'asc';
    }
    currentPage.value = 1;
    fetchPages();
  };

  const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
      currentPage.value = page;
      fetchPages();
    }
  };

  const togglePublished = async (page) => {
    const originalStatus = page.isPublished;
    page.isPublished = !page.isPublished; // Optimistic update

    try {
      const response = await fetch(`/admin/pages/${page.id}/toggle-published`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_token=${encodeURIComponent(csrfToken)}`,
      });

      if (!response.ok) {
        throw new Error('Failed to toggle page status');
      }

      return { success: true, message: `Page ${page.isPublished ? 'published' : 'unpublished'}` };
    } catch (err) {
      page.isPublished = originalStatus; // Revert on failure
      return { success: false, message: err.message };
    }
  };

  const toggleHomepage = async (page) => {
    if (page.isHomepage) {
      return { success: false, message: 'Cannot unset homepage' };
    }

    const originalHomepages = pages.value.map(p => ({ id: p.id, isHomepage: p.isHomepage }));
    
    // Optimistic update
    pages.value.forEach(p => {
      p.isHomepage = p.id === page.id;
    });

    try {
      const response = await fetch(`/admin/pages/${page.id}/toggle-homepage`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_token=${encodeURIComponent(csrfToken)}`,
      });

      if (!response.ok) {
        throw new Error('Failed to set page as homepage');
      }

      return { success: true, message: `Page "${page.title}" set as homepage` };
    } catch (err) {
      // Revert on failure
      originalHomepages.forEach(orig => {
        const p = pages.value.find(pg => pg.id === orig.id);
        if (p) p.isHomepage = orig.isHomepage;
      });
      return { success: false, message: err.message };
    }
  };

  const deletePage = async (id, csrfToken) => {
    try {
      const response = await fetch(`/admin/pages/${id}/delete`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `_token=${encodeURIComponent(csrfToken)}`,
      });

      if (!response.ok) throw new Error('Failed to delete page');

      await fetchPages(); // Refresh list
      return { success: true, message: 'Page deleted' };
    } catch (err) {
      return { success: false, message: err.message };
    }
  };

  return {
    pages,
    loading,
    error,
    searchQuery,
    sortField,
    sortOrder,
    currentPage,
    perPage,
    total,
    totalPages,
    categoryFilter,
    fetchPages,
    search,
    filterByCategory,
    sort,
    goToPage,
    togglePublished,
    toggleHomepage,
    deletePage,
  };
}
