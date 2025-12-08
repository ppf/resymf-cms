<template>
  <div class="user-form-container">
    <form @submit.prevent="handleSubmit" class="user-form">
      <!-- Username Field -->
      <div class="mb-3">
        <label for="user-username" class="form-label">
          Username <span class="text-danger">*</span>
        </label>
        <input
          id="user-username"
          v-model="form.username"
          type="text"
          class="form-control"
          :class="{
            'is-invalid': errors.username,
            'is-valid': usernameValid && !errors.username && form.username
          }"
          placeholder="Enter username"
          maxlength="25"
          :disabled="validatingUsername"
          @input="handleUsernameInput"
          @blur="validateUsername"
        />
        <div v-if="errors.username" class="invalid-feedback">
          {{ errors.username }}
        </div>
        <div v-if="usernameValid && !errors.username && form.username" class="valid-feedback">
          {{ usernameValidMessage }}
        </div>
        <small class="form-text text-muted">
          3-25 characters, letters, numbers, underscores, hyphens only
        </small>
      </div>

      <!-- Email Field -->
      <div class="mb-3">
        <label for="user-email" class="form-label">
          Email <span class="text-danger">*</span>
        </label>
        <input
          id="user-email"
          v-model="form.email"
          type="email"
          class="form-control"
          :class="{
            'is-invalid': errors.email,
            'is-valid': emailValid && !errors.email && form.email
          }"
          placeholder="user@example.com"
          maxlength="180"
          :disabled="validatingEmail"
          @input="handleEmailInput"
          @blur="validateEmail"
        />
        <div v-if="errors.email" class="invalid-feedback">
          {{ errors.email }}
        </div>
        <div v-if="emailValid && !errors.email && form.email" class="valid-feedback">
          {{ emailValidMessage }}
        </div>
        <small class="form-text text-muted">
          Valid email address (max 180 characters)
        </small>
      </div>

      <!-- Password Fields -->
      <div class="password-section">
        <h5 class="section-title">
          {{ isEditMode ? 'Change Password (Optional)' : 'Password' }}
          <span v-if="!isEditMode" class="text-danger">*</span>
        </h5>

        <div class="mb-3">
          <label for="user-password" class="form-label">
            {{ isEditMode ? 'New Password' : 'Password' }}
            <span v-if="!isEditMode" class="text-danger">*</span>
          </label>
          <div class="password-input-group">
            <input
              id="user-password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              class="form-control"
              :class="{ 'is-invalid': errors.password }"
              :placeholder="isEditMode ? 'Leave blank to keep current password' : 'Enter password'"
              @input="handlePasswordInput"
            />
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="showPassword = !showPassword"
              tabindex="-1"
            >
              <i :class="['bi', showPassword ? 'bi-eye-slash' : 'bi-eye']"></i>
            </button>
          </div>
          <div v-if="errors.password" class="invalid-feedback d-block">
            {{ errors.password }}
          </div>
          <small v-if="!isEditMode" class="form-text text-muted">
            Minimum 6 characters
          </small>
          <PasswordStrength v-if="form.password" :password="form.password" />
        </div>

        <div v-if="form.password" class="mb-3">
          <label for="user-password-confirm" class="form-label">
            Confirm Password <span class="text-danger">*</span>
          </label>
          <input
            id="user-password-confirm"
            v-model="form.passwordConfirm"
            :type="showPassword ? 'text' : 'password'"
            class="form-control"
            :class="{
              'is-invalid': errors.passwordConfirm || (form.passwordConfirm && form.password !== form.passwordConfirm),
              'is-valid': form.passwordConfirm && form.password === form.passwordConfirm
            }"
            placeholder="Confirm password"
            @input="delete errors.passwordConfirm"
          />
          <div v-if="errors.passwordConfirm" class="invalid-feedback">
            {{ errors.passwordConfirm }}
          </div>
          <div v-else-if="form.passwordConfirm && form.password !== form.passwordConfirm" class="invalid-feedback">
            Passwords do not match
          </div>
          <div v-else-if="form.passwordConfirm && form.password === form.passwordConfirm" class="valid-feedback">
            Passwords match
          </div>
        </div>
      </div>

      <!-- Roles Section -->
      <div class="roles-section mb-4">
        <h5 class="section-title">Roles & Permissions</h5>

        <div class="role-checkboxes">
          <div class="form-check">
            <input
              id="role-user"
              v-model="form.roles"
              type="checkbox"
              class="form-check-input"
              value="ROLE_USER"
              disabled
            />
            <label for="role-user" class="form-check-label">
              <strong>User</strong>
              <span class="text-muted d-block small">Basic user access (always enabled)</span>
            </label>
          </div>

          <div class="form-check">
            <input
              id="role-admin"
              v-model="form.roles"
              type="checkbox"
              class="form-check-input"
              value="ROLE_ADMIN"
            />
            <label for="role-admin" class="form-check-label">
              <strong>Admin</strong>
              <span class="text-muted d-block small">Full administrative access to manage content and users</span>
            </label>
          </div>

        </div>
      </div>

      <!-- Active Toggle -->
      <div class="mb-4">
        <div class="form-check form-switch">
          <input
            id="user-active"
            v-model="form.isActive"
            type="checkbox"
            class="form-check-input"
            role="switch"
          />
          <label for="user-active" class="form-check-label">
            <strong>Active Account</strong>
            <span class="text-muted ms-2">
              {{ form.isActive ? 'User can log in' : 'User cannot log in' }}
            </span>
          </label>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <button
          type="submit"
          class="btn btn-primary"
          :disabled="submitting || !isFormValid || validatingUsername || validatingEmail"
        >
          <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
          <i v-else class="bi bi-check-lg me-1"></i>
          {{ isEditMode ? 'Update User' : 'Create User' }}
        </button>

        <a :href="cancelUrl" class="btn btn-secondary">
          <i class="bi bi-x-lg me-1"></i>
          Cancel
        </a>

        <button
          v-if="isEditMode && hasChanges"
          type="button"
          class="btn btn-outline-secondary"
          @click="resetToOriginal"
        >
          <i class="bi bi-arrow-counterclockwise me-1"></i>
          Reset
        </button>
      </div>

      <!-- Validation Summary -->
      <div v-if="Object.keys(errors).length > 0" class="alert alert-danger mt-3">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
          <li v-for="(error, field) in errors" :key="field">{{ error }}</li>
        </ul>
      </div>
    </form>

    <!-- Toast Notification -->
    <div
      v-if="toast.show"
      :class="['toast-notification', `toast-${toast.type}`]"
      role="alert"
    >
      <i :class="['bi', toastIcon, 'me-2']"></i>
      {{ toast.message }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useUserForm } from '../composables/useUserForm.js';
import PasswordStrength from './PasswordStrength.vue';

const props = defineProps({
  userId: {
    type: Number,
    default: null,
  },
  apiUrl: {
    type: String,
    required: true,
  },
  cancelUrl: {
    type: String,
    default: '/admin/users',
  },
});

const {
  form,
  errors,
  submitting,
  fetchUser,
  createUser,
  updateUser,
  validateUsernameUniqueness,
  validateEmailUniqueness,
  clearErrors,
} = useUserForm(props.apiUrl);

const isEditMode = computed(() => !!props.userId);
const usernameValid = ref(false);
const usernameValidMessage = ref('');
const validatingUsername = ref(false);
const emailValid = ref(false);
const emailValidMessage = ref('');
const validatingEmail = ref(false);
const showPassword = ref(false);
const toast = ref({ show: false, message: '', type: 'success' });
const originalData = ref(null);

const hasChanges = computed(() => {
  if (!originalData.value) return false;
  return (
    form.username !== originalData.value.username ||
    form.email !== originalData.value.email ||
    JSON.stringify(form.roles) !== JSON.stringify(originalData.value.roles) ||
    form.isActive !== originalData.value.isActive ||
    form.password !== ''
  );
});

// Handle username input
const handleUsernameInput = () => {
  usernameValid.value = false;
  delete errors.username;
};

// Validate username
const validateUsername = async () => {
  if (!form.username) {
    errors.username = 'Username is required';
    usernameValid.value = false;
    return;
  }

  // Check format
  if (form.username.length < 3) {
    errors.username = 'Username must be at least 3 characters';
    usernameValid.value = false;
    return;
  }

  if (!/^[a-zA-Z0-9_-]+$/.test(form.username)) {
    errors.username = 'Username can only contain letters, numbers, underscores, and hyphens';
    usernameValid.value = false;
    return;
  }

  validatingUsername.value = true;
  const result = await validateUsernameUniqueness(form.username, props.userId);
  validatingUsername.value = false;

  if (result.valid) {
    delete errors.username;
    usernameValid.value = true;
    usernameValidMessage.value = result.message || 'Username is available';
  } else {
    errors.username = result.message;
    usernameValid.value = false;
  }
};

// Handle email input
const handleEmailInput = () => {
  emailValid.value = false;
  delete errors.email;
};

// Validate email
const validateEmail = async () => {
  if (!form.email) {
    errors.email = 'Email is required';
    emailValid.value = false;
    return;
  }

  // Check format
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email address';
    emailValid.value = false;
    return;
  }

  validatingEmail.value = true;
  const result = await validateEmailUniqueness(form.email, props.userId);
  validatingEmail.value = false;

  if (result.valid) {
    delete errors.email;
    emailValid.value = true;
    emailValidMessage.value = result.message || 'Email is available';
  } else {
    errors.email = result.message;
    emailValid.value = false;
  }
};

// Handle password input
const handlePasswordInput = () => {
  delete errors.password;
};

// Check if form is valid
const isFormValid = computed(() => {
  // Username and email must be valid
  if (!form.username || !form.email) return false;
  if (Object.keys(errors).length > 0) return false;

  // For new users, password is required
  if (!isEditMode.value && !form.password) return false;

  // If password is provided, confirmation must match
  if (form.password && form.password !== form.passwordConfirm) return false;

  // At least ROLE_USER must be selected
  if (!form.roles.includes('ROLE_USER')) return false;

  return true;
});

// Reset to original data
const resetToOriginal = () => {
  if (originalData.value) {
    Object.assign(form, originalData.value);
    form.password = '';
    form.passwordConfirm = '';
    clearErrors();
    usernameValid.value = false;
    emailValid.value = false;
  }
};

// Handle form submission
const handleSubmit = async () => {
  // Clear previous errors
  clearErrors();

  // Validate all fields
  await validateUsername();
  await validateEmail();

  // Validate password for new users
  if (!isEditMode.value && !form.password) {
    errors.password = 'Password is required';
  }

  // Validate password confirmation
  if (form.password && form.password !== form.passwordConfirm) {
    errors.passwordConfirm = 'Passwords do not match';
  }

  if (!isFormValid.value) {
    showToast('Please fix all errors before submitting', 'error');
    return;
  }

  // Submit form
  let result;
  if (isEditMode.value) {
    result = await updateUser(props.userId);
  } else {
    result = await createUser();
  }

  if (result.success) {
    showToast(result.message, 'success');

    // Redirect after short delay
    setTimeout(() => {
      window.location.href = props.cancelUrl;
    }, 1000);
  } else {
    showToast(result.message || 'An error occurred', 'error');
  }
};

// Toast helpers
const toastIcon = computed(() => {
  switch (toast.value.type) {
    case 'success': return 'bi-check-circle-fill';
    case 'error': return 'bi-exclamation-circle-fill';
    case 'warning': return 'bi-exclamation-triangle-fill';
    default: return 'bi-info-circle-fill';
  }
});

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 3000);
};

// Load user data on mount (if editing)
onMounted(async () => {
  if (isEditMode.value) {
    const result = await fetchUser(props.userId);
    if (result.success) {
      // Store original data for reset
      originalData.value = {
        username: form.username,
        email: form.email,
        roles: [...form.roles],
        isActive: form.isActive,
      };

      // Validate username and email to show they're available
      await validateUsername();
      await validateEmail();
    } else {
      showToast('Failed to load user', 'error');
    }
  }
});
</script>

<style scoped>
/* ============================================
   Design System: Refined Editorial
   Aligned with admin sidebar aesthetic
   ============================================ */

/* Color Palette */
.user-form-container {
  --primary: #3b82f6;
  --primary-hover: #2563eb;
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --slate-50: #f8fafc;
  --slate-100: #f1f5f9;
  --slate-200: #e2e8f0;
  --slate-300: #cbd5e1;
  --slate-500: #64748b;
  --slate-600: #475569;
  --slate-700: #334155;
  --slate-900: #0f172a;

  max-width: 800px;
  margin: 0 auto;
}

/* Main Form Container */
.user-form {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid var(--slate-200);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Section Titles */
.section-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--slate-900);
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--slate-200);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title::before {
  content: '';
  width: 4px;
  height: 1rem;
  background: var(--primary);
  border-radius: 2px;
}

/* Form Sections */
.password-section,
.roles-section {
  background: var(--slate-50);
  padding: 1.5rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  border: 1px solid var(--slate-100);
}

/* Form Labels */
.user-form :deep(.form-label) {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--slate-700);
  margin-bottom: 0.375rem;
}

/* Form Inputs */
.user-form :deep(.form-control) {
  border: 1px solid var(--slate-200);
  border-radius: 8px;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  color: var(--slate-900);
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.user-form :deep(.form-control:focus) {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  outline: none;
}

.user-form :deep(.form-control::placeholder) {
  color: var(--slate-300);
}

.user-form :deep(.form-control.is-valid) {
  border-color: var(--success);
  background-image: none;
}

.user-form :deep(.form-control.is-invalid) {
  border-color: var(--danger);
  background-image: none;
}

/* Password Input Group */
.password-input-group {
  display: flex;
  gap: 0;
}

.password-input-group input {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}

.password-input-group button {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  border-left: 0;
  border: 1px solid var(--slate-200);
  background: var(--slate-50);
  color: var(--slate-600);
  padding: 0.625rem 1rem;
  font-size: 0.8125rem;
  transition: all 0.15s ease;
}

.password-input-group button:hover {
  background: var(--slate-100);
  color: var(--slate-900);
}

/* Role Checkboxes */
.role-checkboxes {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.role-checkboxes .form-check {
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 1px solid var(--slate-200);
  transition: all 0.15s ease;
}

.role-checkboxes .form-check:hover {
  border-color: var(--primary);
  background: rgba(59, 130, 246, 0.02);
}

.user-form :deep(.form-check-input) {
  width: 1.125rem;
  height: 1.125rem;
  border: 1.5px solid var(--slate-300);
  border-radius: 4px;
  margin-top: 0.125rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.user-form :deep(.form-check-input:checked) {
  background-color: var(--primary);
  border-color: var(--primary);
}

.user-form :deep(.form-check-input:focus) {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
  border-color: var(--primary);
}

.user-form :deep(.form-check-input:disabled) {
  opacity: 0.5;
  cursor: not-allowed;
}

.user-form :deep(.form-check-label) {
  font-size: 0.875rem;
  color: var(--slate-700);
  cursor: pointer;
}

.user-form :deep(.form-check-label strong) {
  display: block;
  margin-bottom: 0.25rem;
  color: var(--slate-900);
  font-weight: 500;
}

/* Toggle Switch */
.user-form :deep(.form-switch .form-check-input) {
  width: 2.75rem;
  height: 1.5rem;
  border-radius: 1rem;
  background-color: var(--slate-200);
  border: none;
}

.user-form :deep(.form-switch .form-check-input:checked) {
  background-color: var(--success);
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  padding-top: 1.25rem;
  margin-top: 0.5rem;
  border-top: 1px solid var(--slate-200);
}

.form-actions .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  padding: 0.625rem 1.25rem;
  transition: all 0.15s ease;
}

/* Primary Button */
.user-form :deep(.btn-primary) {
  background: var(--primary);
  border: none;
  color: white;
}

.user-form :deep(.btn-primary:hover:not(:disabled)) {
  background: var(--primary-hover);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
}

.user-form :deep(.btn-primary:active) {
  transform: translateY(0);
}

.user-form :deep(.btn-primary:disabled) {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Secondary Button */
.user-form :deep(.btn-secondary) {
  background: var(--slate-100);
  border: 1px solid var(--slate-200);
  color: var(--slate-700);
}

.user-form :deep(.btn-secondary:hover) {
  background: var(--slate-200);
  color: var(--slate-900);
}

/* Outline Secondary Button */
.user-form :deep(.btn-outline-secondary) {
  background: transparent;
  border: 1px solid var(--slate-200);
  color: var(--slate-600);
}

.user-form :deep(.btn-outline-secondary:hover) {
  background: var(--slate-50);
  border-color: var(--slate-300);
  color: var(--slate-900);
}

/* Help Text */
.user-form :deep(.form-text) {
  font-size: 0.75rem;
  color: var(--slate-500);
  margin-top: 0.375rem;
}

.user-form :deep(.form-text.text-success) {
  color: var(--success);
}

.user-form :deep(.form-text.text-danger) {
  color: var(--danger);
}

/* Validation Feedback */
.user-form :deep(.invalid-feedback) {
  font-size: 0.75rem;
  color: var(--danger);
  margin-top: 0.375rem;
}

.user-form :deep(.valid-feedback) {
  font-size: 0.75rem;
  color: var(--success);
  margin-top: 0.375rem;
}

/* Alert */
.user-form :deep(.alert-danger) {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #991b1b;
  padding: 1rem;
  font-size: 0.875rem;
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 1rem 1.5rem;
  border-radius: 10px;
  color: white;
  font-weight: 500;
  font-size: 0.875rem;
  z-index: 9999;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  animation: slideIn 0.3s ease-out;
  display: flex;
  align-items: center;
  max-width: 400px;
}

.toast-success {
  background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
}

.toast-error {
  background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
}

.toast-warning {
  background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Loading Spinner */
.user-form :deep(.spinner-border-sm) {
  width: 1rem;
  height: 1rem;
  border-width: 0.15em;
}

/* Responsive */
@media (max-width: 768px) {
  .user-form {
    padding: 1.25rem;
  }

  .password-section,
  .roles-section {
    padding: 1rem;
  }

  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .form-actions .btn {
    width: 100%;
  }

  .toast-notification {
    left: 20px;
    right: 20px;
    max-width: none;
  }
}
</style>
