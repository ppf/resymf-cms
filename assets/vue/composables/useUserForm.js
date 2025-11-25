import { reactive, ref } from 'vue';

export function useUserForm(apiBaseUrl) {
  const form = reactive({
    username: '',
    email: '',
    password: '',
    passwordConfirm: '',
    roles: ['ROLE_USER'],
    isActive: true,
    themeId: null,
  });

  const errors = reactive({});
  const submitting = ref(false);

  const fetchUser = async (id) => {
    try {
      const response = await fetch(`${apiBaseUrl}/${id}`);

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();

      // Populate form with user data (excluding password)
      form.username = data.username || '';
      form.email = data.email || '';
      form.roles = data.roles || ['ROLE_USER'];
      form.isActive = data.isActive ?? true;
      form.themeId = data.themeId || null;
      // Password fields remain empty for security

      return { success: true };
    } catch (error) {
      console.error('Failed to fetch user:', error);
      return { success: false, message: 'Failed to load user' };
    }
  };

  const createUser = async () => {
    submitting.value = true;
    clearErrors();

    try {
      const response = await fetch(apiBaseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          username: form.username,
          email: form.email,
          password: form.password,
          passwordConfirm: form.passwordConfirm,
          roles: form.roles,
          isActive: form.isActive,
          themeId: form.themeId,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        return { success: true, message: data.message, id: data.id };
      } else {
        if (data.errors) {
          Object.assign(errors, data.errors);
        }
        return { success: false, message: data.error || 'Failed to create user' };
      }
    } catch (error) {
      console.error('Network error:', error);
      return { success: false, message: 'Network error occurred' };
    } finally {
      submitting.value = false;
    }
  };

  const updateUser = async (id) => {
    submitting.value = true;
    clearErrors();

    try {
      const payload = {
        username: form.username,
        email: form.email,
        roles: form.roles,
        isActive: form.isActive,
        themeId: form.themeId,
      };

      // Only include password if it's being changed
      if (form.password) {
        payload.password = form.password;
        payload.passwordConfirm = form.passwordConfirm;
      }

      const response = await fetch(`${apiBaseUrl}/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const data = await response.json();

      if (response.ok) {
        return { success: true, message: data.message };
      } else {
        if (data.errors) {
          Object.assign(errors, data.errors);
        }
        return { success: false, message: data.error || 'Failed to update user' };
      }
    } catch (error) {
      console.error('Network error:', error);
      return { success: false, message: 'Network error occurred' };
    } finally {
      submitting.value = false;
    }
  };

  const validateUsernameUniqueness = async (username, excludeId = null) => {
    if (!username) {
      return { valid: false, message: 'Username is required' };
    }

    try {
      const response = await fetch(`${apiBaseUrl}/validate-username`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, excludeId }),
      });

      if (!response.ok) {
        throw new Error('Validation request failed');
      }

      return await response.json();
    } catch (error) {
      console.error('Username validation error:', error);
      return { valid: false, message: 'Failed to validate username' };
    }
  };

  const validateEmailUniqueness = async (email, excludeId = null) => {
    if (!email) {
      return { valid: false, message: 'Email is required' };
    }

    try {
      const response = await fetch(`${apiBaseUrl}/validate-email`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, excludeId }),
      });

      if (!response.ok) {
        throw new Error('Validation request failed');
      }

      return await response.json();
    } catch (error) {
      console.error('Email validation error:', error);
      return { valid: false, message: 'Failed to validate email' };
    }
  };

  const calculatePasswordStrength = (password) => {
    if (!password) return { strength: 0, label: '', color: '' };

    let strength = 0;

    // Length
    if (password.length >= 6) strength += 1;
    if (password.length >= 10) strength += 1;
    if (password.length >= 14) strength += 1;

    // Complexity
    if (/[a-z]/.test(password)) strength += 1;
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 1;

    // Determine label and color
    if (strength <= 2) {
      return { strength, label: 'Weak', color: 'danger' };
    } else if (strength <= 4) {
      return { strength, label: 'Fair', color: 'warning' };
    } else if (strength <= 5) {
      return { strength, label: 'Good', color: 'info' };
    } else {
      return { strength, label: 'Strong', color: 'success' };
    }
  };

  const clearErrors = () => {
    Object.keys(errors).forEach(key => delete errors[key]);
  };

  const resetForm = () => {
    form.username = '';
    form.email = '';
    form.password = '';
    form.passwordConfirm = '';
    form.roles = ['ROLE_USER'];
    form.isActive = true;
    form.themeId = null;
    clearErrors();
  };

  return {
    form,
    errors,
    submitting,
    fetchUser,
    createUser,
    updateUser,
    validateUsernameUniqueness,
    validateEmailUniqueness,
    calculatePasswordStrength,
    clearErrors,
    resetForm,
  };
}
