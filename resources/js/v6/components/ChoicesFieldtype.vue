<template>
  <div class="choices-fieldtype">
    <div class="choices-grid" :class="layoutClass" role="group" :aria-label="config.display || handle">
      <label
        v-for="option in options"
        :key="option.value"
        class="choices-card"
        :class="{
          'choices-card--selected': isSelected(option.value),
          'choices-card--disabled': isReadOnly || config.disabled,
        }"
      >
        <input
          class="choices-card__input"
          :type="inputType"
          :name="inputName"
          :value="option.value"
          :checked="isSelected(option.value)"
          :disabled="isReadOnly || config.disabled"
          @change="toggle(option.value)"
        />

        <span class="choices-card__indicator" aria-hidden="true">
          <span class="choices-card__indicator-dot"></span>
        </span>

        <img
          v-if="option.image_url"
          class="choices-card__image"
          :src="option.image_url"
          :alt="option.image_alt || option.label"
        />

        <span class="choices-card__body">
          <span class="choices-card__label">{{ option.label }}</span>
          <span v-if="option.description" class="choices-card__description">{{ option.description }}</span>
          <span v-if="option.html" class="choices-card__html" v-html="option.html"></span>
        </span>
      </label>
    </div>
  </div>
</template>

<script lang="ts">
type ChoiceOption = {
  value: string
  label: string
  image?: string | null
  image_url?: string | null
  image_alt?: string | null
  description?: string | null
  html?: string | null
}

export default {
  name: 'ChoicesFieldtype',

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

    toggle(value: string): void {
      if (this.isReadOnly || (this.config as { disabled?: boolean }).disabled) return

      if (!this.isMultiple) {
        this.$emit('update:value', value)
        return
      }

      const selected = new Set(this.selectedValues)

      if (selected.has(value)) {
        selected.delete(value)
      } else {
        selected.add(value)
      }

      this.$emit('update:value', this.sortValues([...selected]))
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

.choices-card {
  position: relative;
  display: flex;
  min-width: 0;
  cursor: pointer;
  gap: 12px;
  overflow: hidden;
  border: 1px solid var(--ui-border, rgb(203 213 225));
  border-radius: 8px;
  background: var(--ui-bg, white);
  padding: 14px;
  transition:
    border-color 120ms ease,
    box-shadow 120ms ease,
    background-color 120ms ease;
}

.choices-card:hover {
  border-color: var(--ui-border-hover, rgb(148 163 184));
}

.choices-card:focus-within {
  outline: 2px solid var(--ui-focus, rgb(37 99 235));
  outline-offset: 2px;
}

.choices-card--selected {
  border-color: var(--ui-accent, rgb(37 99 235));
  box-shadow: inset 0 0 0 1px var(--ui-accent, rgb(37 99 235));
}

.choices-card--disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.choices-card__input {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  clip-path: inset(50%);
}

.choices-card__indicator {
  display: flex;
  width: 18px;
  height: 18px;
  flex: 0 0 18px;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--ui-border, rgb(148 163 184));
  border-radius: 999px;
  margin-top: 1px;
}

.choices-card__input[type='checkbox'] + .choices-card__indicator {
  border-radius: 5px;
}

.choices-card--selected .choices-card__indicator {
  border-color: var(--ui-accent, rgb(37 99 235));
  background: var(--ui-accent, rgb(37 99 235));
}

.choices-card__indicator-dot {
  display: none;
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: white;
}

.choices-card--selected .choices-card__indicator-dot {
  display: block;
}

.choices-card__image {
  width: 72px;
  height: 72px;
  flex: 0 0 72px;
  align-self: flex-start;
  border-radius: 6px;
  background: var(--ui-bg-subtle, rgb(248 250 252));
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
  color: var(--ui-text, rgb(15 23 42));
  font-weight: 600;
  line-height: 1.3;
}

.choices-card__description {
  color: var(--ui-text-muted, rgb(71 85 105));
  font-size: 13px;
  line-height: 1.45;
}

.choices-card__html {
  color: var(--ui-text-muted, rgb(71 85 105));
  font-size: 13px;
  line-height: 1.45;
}

.choices-card__html :deep(*) {
  margin-top: 0;
  margin-bottom: 0;
}
</style>
