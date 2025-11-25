<template>
  <div v-if="password" class="password-strength">
    <div class="strength-meter">
      <div
        class="strength-bar"
        :class="`strength-${strengthInfo.color}`"
        :style="{ width: `${(strengthInfo.strength / 7) * 100}%` }"
      ></div>
    </div>
    <div class="strength-info">
      <span :class="`badge bg-${strengthInfo.color}`">
        {{ strengthInfo.label }}
      </span>
      <small class="text-muted ms-2">
        {{ strengthMessage }}
      </small>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  password: {
    type: String,
    default: '',
  },
});

const strengthInfo = computed(() => {
  if (!props.password) {
    return { strength: 0, label: '', color: '' };
  }

  let strength = 0;

  // Length
  if (props.password.length >= 6) strength += 1;
  if (props.password.length >= 10) strength += 1;
  if (props.password.length >= 14) strength += 1;

  // Complexity
  if (/[a-z]/.test(props.password)) strength += 1;
  if (/[A-Z]/.test(props.password)) strength += 1;
  if (/[0-9]/.test(props.password)) strength += 1;
  if (/[^a-zA-Z0-9]/.test(props.password)) strength += 1;

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
});

const strengthMessage = computed(() => {
  const missing = [];

  if (props.password.length < 10) {
    missing.push('longer length');
  }
  if (!/[a-z]/.test(props.password)) {
    missing.push('lowercase');
  }
  if (!/[A-Z]/.test(props.password)) {
    missing.push('uppercase');
  }
  if (!/[0-9]/.test(props.password)) {
    missing.push('numbers');
  }
  if (!/[^a-zA-Z0-9]/.test(props.password)) {
    missing.push('symbols');
  }

  if (missing.length === 0) {
    return 'Great password!';
  } else if (missing.length === 1) {
    return `Add ${missing[0]} for stronger password`;
  } else {
    return `Consider adding ${missing.slice(0, 2).join(' and ')}`;
  }
});
</script>

<style scoped>
.password-strength {
  margin-top: 0.5rem;
}

.strength-meter {
  height: 6px;
  background-color: #e9ecef;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.strength-bar {
  height: 100%;
  transition: width 0.3s ease, background-color 0.3s ease;
  border-radius: 3px;
}

.strength-danger {
  background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
}

.strength-warning {
  background: linear-gradient(90deg, #ffc107 0%, #e0a800 100%);
}

.strength-info {
  background: linear-gradient(90deg, #17a2b8 0%, #138496 100%);
}

.strength-success {
  background: linear-gradient(90deg, #28a745 0%, #218838 100%);
}

.strength-info {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.strength-info .badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
}
</style>
