import { reactive, ref } from 'vue';

export function useCategoryForm(apiBaseUrl) {
  const form = reactive({
    name: '',
    slug: '',
    description: '',
    displayOrder: 0,
    isActive: true,
  });

  const errors = reactive({});
  const submitting = ref(false);

  const fetchCategory = async (id) => {
    try {
      const response = await fetch(`${apiBaseUrl}/${id}`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      // Populate form with category data
      form.name = data.name || '';
      form.slug = data.slug || '';
      form.description = data.description || '';
      form.displayOrder = data.displayOrder || 0;
      form.isActive = data.isActive ?? true;

      return { success: true };
    } catch (error) {
      console.error('Failed to fetch category:', error);
      return { success: false, message: 'Failed to load category' };
    }
  };

  const createCategory = async () => {
    submitting.value = true;
    clearErrors();

    try {
      const response = await fetch(apiBaseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      const data = await response.json();

      if (response.ok) {
        return { success: true, message: data.message, id: data.id };
      } else {
        if (data.errors) {
          Object.assign(errors, data.errors);
        }
        return { success: false, message: data.error || 'Failed to create category' };
      }
    } catch (error) {
      console.error('Network error:', error);
      return { success: false, message: 'Network error occurred' };
    } finally {
      submitting.value = false;
    }
  };

  const updateCategory = async (id) => {
    submitting.value = true;
    clearErrors();

    try {
      const response = await fetch(`${apiBaseUrl}/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      const data = await response.json();

      if (response.ok) {
        return { success: true, message: data.message };
      } else {
        if (data.errors) {
          Object.assign(errors, data.errors);
        }
        return { success: false, message: data.error || 'Failed to update category' };
      }
    } catch (error) {
      console.error('Network error:', error);
      return { success: false, message: 'Network error occurred' };
    } finally {
      submitting.value = false;
    }
  };

  const validateSlugUniqueness = async (slug, excludeId = null) => {
    if (!slug) {
      return { valid: false, message: 'Slug is required' };
    }

    try {
      const response = await fetch(`${apiBaseUrl}/validate-slug`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slug, excludeId }),
      });

      if (!response.ok) {
        throw new Error('Validation request failed');
      }

      return await response.json();
    } catch (error) {
      console.error('Slug validation error:', error);
      return { valid: false, message: 'Failed to validate slug' };
    }
  };

  const generateSlug = (text) => {
    return text
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\s-]/g, '') // Remove invalid chars
      .replace(/\s+/g, '-')          // Replace spaces with hyphens
      .replace(/-+/g, '-')           // Replace multiple hyphens with single
      .replace(/^-+|-+$/g, '');      // Remove leading/trailing hyphens
  };

  const clearErrors = () => {
    Object.keys(errors).forEach(key => delete errors[key]);
  };

  const resetForm = () => {
    form.name = '';
    form.slug = '';
    form.description = '';
    form.displayOrder = 0;
    form.isActive = true;
    clearErrors();
  };

  return {
    form,
    errors,
    submitting,
    fetchCategory,
    createCategory,
    updateCategory,
    validateSlugUniqueness,
    generateSlug,
    clearErrors,
    resetForm,
  };
}
