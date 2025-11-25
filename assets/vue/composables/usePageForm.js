import { reactive, ref } from 'vue';

export function usePageForm(apiBaseUrl) {
  const form = reactive({
    title: '',
    slug: '',
    content: '',
    metaDescription: '',
    metaKeywords: '',
    isPublished: false,
    isHomepage: false,
    displayOrder: 0,
    publishedAt: null,
    categoryIds: [],
  });

  const errors = reactive({});
  const submitting = ref(false);

  const fetchPage = async (id) => {
    try {
      const response = await fetch(`${apiBaseUrl}/${id}`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      // Populate form with page data
      form.title = data.title || '';
      form.slug = data.slug || '';
      form.content = data.content || '';
      form.metaDescription = data.metaDescription || '';
      form.metaKeywords = data.metaKeywords || '';
      form.isPublished = data.isPublished ?? false;
      form.isHomepage = data.isHomepage ?? false;
      form.displayOrder = data.displayOrder ?? 0;
      form.publishedAt = data.publishedAt || null;
      form.categoryIds = data.categoryIds || [];

      return { success: true };
    } catch (error) {
      console.error('Failed to fetch page:', error);
      return { success: false, message: 'Failed to load page' };
    }
  };

  const createPage = async () => {
    submitting.value = true;
    clearErrors();

    try {
      const response = await fetch(apiBaseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: form.title,
          slug: form.slug,
          content: form.content,
          metaDescription: form.metaDescription,
          metaKeywords: form.metaKeywords,
          isPublished: form.isPublished,
          isHomepage: form.isHomepage,
          displayOrder: form.displayOrder,
          publishedAt: form.publishedAt,
          categoryIds: form.categoryIds,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        return { success: true, message: data.message, id: data.id };
      } else {
        if (data.errors) {
          Object.assign(errors, data.errors);
        }
        return { success: false, message: data.error || 'Failed to create page' };
      }
    } catch (error) {
      console.error('Network error:', error);
      return { success: false, message: 'Network error occurred' };
    } finally {
      submitting.value = false;
    }
  };

  const updatePage = async (id) => {
    submitting.value = true;
    clearErrors();

    try {
      const response = await fetch(`${apiBaseUrl}/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          title: form.title,
          slug: form.slug,
          content: form.content,
          metaDescription: form.metaDescription,
          metaKeywords: form.metaKeywords,
          isPublished: form.isPublished,
          isHomepage: form.isHomepage,
          displayOrder: form.displayOrder,
          publishedAt: form.publishedAt,
          categoryIds: form.categoryIds,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        return { success: true, message: data.message };
      } else {
        if (data.errors) {
          Object.assign(errors, data.errors);
        }
        return { success: false, message: data.error || 'Failed to update page' };
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

  const generateSlug = async (title) => {
    if (!title) {
      return { success: false, message: 'Title is required' };
    }

    try {
      const response = await fetch(`${apiBaseUrl}/generate-slug`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title }),
      });

      if (!response.ok) {
        throw new Error('Slug generation failed');
      }

      const data = await response.json();
      return { success: true, slug: data.slug };
    } catch (error) {
      console.error('Slug generation error:', error);
      return { success: false, message: 'Failed to generate slug' };
    }
  };

  const clearErrors = () => {
    Object.keys(errors).forEach(key => delete errors[key]);
  };

  const resetForm = () => {
    form.title = '';
    form.slug = '';
    form.content = '';
    form.metaDescription = '';
    form.metaKeywords = '';
    form.isPublished = false;
    form.isHomepage = false;
    form.displayOrder = 0;
    form.publishedAt = null;
    form.categoryIds = [];
    clearErrors();
  };

  return {
    form,
    errors,
    submitting,
    fetchPage,
    createPage,
    updatePage,
    validateSlugUniqueness,
    generateSlug,
    clearErrors,
    resetForm,
  };
}
