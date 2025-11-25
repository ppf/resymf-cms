<template>
  <div class="rich-text-editor">
    <label v-if="label" :for="inputId" class="form-label">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <div class="editor-wrapper">
      <textarea
        :id="inputId"
        :value="modelValue"
        @input="handleInput"
        @blur="$emit('blur')"
        class="form-control"
        :class="{
          'is-invalid': error,
          'is-valid': isValid && !error && modelValue
        }"
        :placeholder="placeholder"
        :rows="rows"
        :disabled="disabled"
      ></textarea>

      <div v-if="showCharCount" class="char-count" :class="{ 'text-danger': isOverLimit }">
        {{ charCount }}{{ maxLength ? ` / ${maxLength}` : '' }} characters
      </div>
    </div>

    <div v-if="error" class="invalid-feedback d-block">
      {{ error }}
    </div>

    <div v-if="helpText && !error" class="form-text">
      {{ helpText }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Enter content...',
  },
  rows: {
    type: Number,
    default: 15,
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  helpText: {
    type: String,
    default: '',
  },
  showCharCount: {
    type: Boolean,
    default: true,
  },
  maxLength: {
    type: Number,
    default: null,
  },
  isValid: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'blur']);

const inputId = computed(() => `editor-${Math.random().toString(36).substr(2, 9)}`);

const charCount = computed(() => {
  return props.modelValue?.length || 0;
});

const isOverLimit = computed(() => {
  return props.maxLength && charCount.value > props.maxLength;
});

const handleInput = (event) => {
  emit('update:modelValue', event.target.value);
};
</script>

<style scoped>
.rich-text-editor {
  margin-bottom: 1rem;
}

.editor-wrapper {
  position: relative;
}

.form-control {
  font-family: 'Courier New', monospace;
  font-size: 0.95rem;
  line-height: 1.6;
  resize: vertical;
  min-height: 200px;
}

.char-count {
  position: absolute;
  bottom: 8px;
  right: 12px;
  font-size: 0.75rem;
  color: #6c757d;
  background: rgba(255, 255, 255, 0.9);
  padding: 2px 6px;
  border-radius: 3px;
  pointer-events: none;
}

.char-count.text-danger {
  color: #dc3545 !important;
  font-weight: 600;
}

/* Adjust textarea padding to prevent overlap with char count */
.form-control {
  padding-bottom: 2rem;
}
</style>
