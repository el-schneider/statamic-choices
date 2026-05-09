<template>
  <div class="choices-fieldtype">
    <div
      class="choices-grid"
      :class="layoutClass"
      :role="isMultiple ? 'group' : 'radiogroup'"
      :aria-label="config.display || handle"
    >
      <label
        v-for="option in options"
        :key="option.value"
        class="choices-card-shell"
        :class="{
          'choices-card-shell--selected': isSelected(option.value),
          'choices-card-shell--disabled': isReadOnly || isDisabled,
        }"
        @click="handleCardClick(option.value, $event)"
      >
        <input
          class="choices-card__input"
          :type="inputType"
          :name="inputName"
          :value="option.value"
          :checked="isSelected(option.value)"
          :disabled="isReadOnly || isDisabled"
          @change="handleInput(option.value, $event)"
        />

        <Card inset variant="flat" class="choices-card">
          <span class="choices-card__control" aria-hidden="true">
            <span v-if="isMultiple" class="choices-card__checkbox">
              <svg
                v-if="isSelected(option.value)"
                viewBox="0 0 10 8"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                class="choices-card__checkmark"
                aria-hidden="true"
              >
                <path
                  d="M9 1L3.5 6.5L1 4"
                  stroke="currentColor"
                  stroke-width="1.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </span>
            <span v-else class="choices-card__radio">
              <span v-if="isSelected(option.value)" class="choices-card__radio-dot"></span>
            </span>
          </span>

          <img
            v-if="!option.use_html && option.image_url"
            class="choices-card__image"
            :src="option.image_url"
            :alt="option.image_alt || option.label"
          />

          <span class="choices-card__body">
            <span class="choices-card__label">{{ option.label }}</span>
            <span v-if="option.use_html && option.html" class="choices-card__html" v-html="option.html"></span>
            <span v-else-if="option.description" class="choices-card__description">{{ option.description }}</span>
          </span>
        </Card>
      </label>
    </div>
  </div>
</template>

<script lang="ts">
import { Card } from '@statamic/cms/ui'

type ChoiceOption = {
  value: string
  label: string
  use_html?: boolean
  image?: string | null
  image_url?: string | null
  image_alt?: string | null
  description?: string | null
  html?: string | null
}

export default {
  name: 'ChoicesFieldtype',

  components: {
    Card,
  },

  emits: ['update:value', 'replicator-preview-updated'],

  props: {
    handle: { type: String, required: true },
    value: { type: [String, Array, Number, Boolean, null], default: null },
    meta: { type: Object, default: () => ({}) },
    config: { type: Object, default: () => ({}) },
    readOnly: { type: Boolean, default: false },
    namePrefix: { type: String, default: null },
    showFieldPreviews: { type: Boolean, default: false },
  },

  computed: {
    options(): ChoiceOption[] {
      return ((this.meta as { options?: ChoiceOption[] })?.options ?? []) as ChoiceOption[]
    },

    isMultiple(): boolean {
      return (this.config as { mode?: string }).mode === 'multiple'
    },

    inputType(): string {
      return this.isMultiple ? 'checkbox' : 'radio'
    },

    inputName(): string {
      const name = this.namePrefix ? `${this.namePrefix}[${this.handle}]` : this.handle

      return this.isMultiple ? `${name}[]` : name
    },

    selectedValues(): string[] {
      if (this.isMultiple) {
        return Array.isArray(this.value) ? this.sortValues(this.value.map(String)) : []
      }

      return this.value === null || this.value === undefined || this.value === '' ? [] : [String(this.value)]
    },

    layoutClass(): string {
      return (this.config as { layout?: string }).layout === 'two_columns'
        ? 'choices-grid--two-columns'
        : 'choices-grid--one-column'
    },

    isReadOnly(): boolean {
      const config = this.config as { visibility?: string }

      return this.readOnly || config.visibility === 'read_only' || config.visibility === 'computed'
    },

    isDisabled(): boolean {
      return Boolean((this.config as { disabled?: boolean }).disabled)
    },

    replicatorPreview(): string | undefined {
      if (!this.showFieldPreviews) return undefined

      return this.selectedValues
        .map((value) => this.options.find((option) => option.value === value)?.label ?? value)
        .join(', ')
    },
  },

  watch: {
    replicatorPreview: {
      immediate: true,
      handler(text: string | undefined) {
        if (!this.showFieldPreviews) return

        this.$emit('replicator-preview-updated', text)
      },
    },
  },

  methods: {
    isSelected(value: string): boolean {
      return this.selectedValues.includes(value)
    },

    handleInput(value: string, event: Event): void {
      if (this.isReadOnly || this.isDisabled) return

      if (!this.isMultiple) {
        this.$emit('update:value', value)
        return
      }

      this.setMultipleSelection(value, (event.target as HTMLInputElement).checked)
    },

    handleCardClick(value: string, event: MouseEvent): void {
      if (this.isReadOnly || this.isDisabled) return

      const target = event.target as HTMLElement

      if (target.closest('a[href], button, input, select, textarea, [role="button"]')) {
        return
      }

      event.preventDefault()

      if (!this.isMultiple) {
        this.$emit('update:value', value)
        return
      }

      this.setMultipleSelection(value, !this.isSelected(value))
    },

    setMultipleSelection(value: string, selected: boolean): void {
      if (this.isReadOnly || this.isDisabled) return

      const values = new Set(this.selectedValues)

      if (selected) {
        values.add(value)
      } else {
        values.delete(value)
      }

      this.$emit('update:value', this.sortValues([...values]))
    },

    sortValues(values: string[]): string[] {
      return this.options.filter((option) => values.includes(option.value)).map((option) => option.value)
    },

    focus(): void {
      ;(this.$el as HTMLElement).querySelector<HTMLInputElement>('input')?.focus()
    },
  },
}
</script>

<style scoped>
.choices-fieldtype {
  width: 100%;
}

.choices-grid {
  display: grid;
  gap: 12px;
}

.choices-grid--one-column {
  grid-template-columns: minmax(0, 1fr);
}

.choices-grid--two-columns {
  grid-template-columns: minmax(0, 1fr);
}

@media (min-width: 760px) {
  .choices-grid--two-columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.choices-card-shell {
  position: relative;
  display: block;
  min-width: 0;
  cursor: pointer;
}

.choices-card-shell--disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.choices-card {
  display: flex;
  min-width: 0;
  gap: 12px;
  padding: 14px;
  transition:
    box-shadow 120ms ease,
    transform 120ms ease;
}

.choices-card-shell:hover:not(.choices-card-shell--disabled) .choices-card {
  transform: translateY(-1px);
}

.choices-card-shell:has(:focus-visible) .choices-card {
  outline: 2px solid var(--theme-color-ui-accent-bg);
  outline-offset: 2px;
}

.choices-card-shell--selected .choices-card {
  box-shadow:
    0 0 0 2px var(--theme-color-ui-accent-bg),
    var(--shadow-ui-md);
}

.choices-card__input {
  position: absolute;
  inset: 0;
  z-index: 1;
  width: 100%;
  height: 100%;
  appearance: none;
  cursor: inherit;
  opacity: 0;
  outline: none;
  pointer-events: none;
}

.choices-card__control {
  margin-top: 2px;
  flex: 0 0 auto;
}

.choices-card__radio {
  display: flex;
  width: 1rem;
  height: 1rem;
  align-items: center;
  justify-content: center;
  border: 1px solid rgb(156 163 175 / 0.75);
  border-radius: 9999px;
  background: white;
  box-shadow: var(--shadow-ui-xs);
}

.dark .choices-card__radio {
  border: none;
  background: var(--color-gray-500);
}

.choices-card-shell--selected .choices-card__radio {
  border-color: var(--theme-color-ui-accent-bg);
}

.dark .choices-card-shell--selected .choices-card__radio {
  border: none;
  background: var(--color-gray-300);
}

.choices-card__radio-dot {
  display: block;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 9999px;
  background: var(--theme-color-ui-accent-bg);
}

.dark .choices-card__radio-dot {
  background: var(--theme-color-ui-accent-bg);
}

.choices-card__checkbox {
  display: flex;
  width: 1rem;
  height: 1rem;
  align-items: center;
  justify-content: center;
  border: 1px solid rgb(156 163 175 / 0.75);
  border-radius: 0.125rem;
  background: white;
  color: white;
  box-shadow: var(--shadow-ui-xs);
}

.dark .choices-card__checkbox {
  border: none;
  background: var(--color-gray-500);
}

.choices-card-shell--selected .choices-card__checkbox {
  border-color: var(--theme-color-ui-accent-bg);
  background: var(--theme-color-ui-accent-bg);
}

.choices-card__checkmark {
  width: 0.625rem;
  height: 0.625rem;
  flex-shrink: 0;
}

.choices-card__image {
  width: 72px;
  height: 72px;
  flex: 0 0 72px;
  align-self: flex-start;
  border-radius: 8px;
  object-fit: cover;
}

.choices-card__body {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  gap: 5px;
}

.choices-card__label {
  color: var(--color-gray-925);
  font-weight: 600;
  line-height: 1.3;
}

.dark .choices-card__label {
  color: var(--color-gray-200);
}

.choices-card__description,
.choices-card__html {
  color: var(--color-gray-600);
  font-size: 13px;
  line-height: 1.45;
}

.dark .choices-card__description,
.dark .choices-card__html {
  color: var(--color-gray-300);
}

.choices-card__html :deep(*) {
  margin-top: 0;
  margin-bottom: 0;
}
</style>
