<template>
  <div class="choices-fieldtype">
    <div
      class="choices-grid"
      :class="[cardWidthClass, variantClass]"
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
        :title="isImageVariant ? option.label : undefined"
        @click="handleCardClick(option.value, $event)"
      >
        <input
          class="choices-card__input"
          :type="inputType"
          :name="inputName"
          :value="option.value"
          :checked="isSelected(option.value)"
          :disabled="isReadOnly || isDisabled"
          :aria-label="option.label"
          @change="handleInput(option.value, $event)"
        />

        <Card
          inset
          variant="flat"
          class="choices-card"
          :class="[
            isImageVariant ? 'choices-card--image' : 'choices-card--content',
            isImageVariant ? imageAspectClass : null,
          ]"
        >
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

          <template v-if="isImageVariant">
            <img
              v-if="option.image_url"
              class="choices-card__image-full"
              :src="option.image_url"
              alt=""
              aria-hidden="true"
            />
            <span v-else class="choices-card__image-fallback">{{ option.label }}</span>
          </template>

          <template v-else>
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
          </template>
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

    isImageVariant(): boolean {
      return (this.config as { variant?: string }).variant === 'image'
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

    cardWidthClass(): string {
      const cardWidth = String((this.config as { card_width?: string | number }).card_width ?? 100)

      return ['100', '50', '33', '25', '20'].includes(cardWidth)
        ? `choices-grid--card-width-${cardWidth}`
        : 'choices-grid--card-width-100'
    },

    variantClass(): string {
      return this.isImageVariant ? 'choices-grid--image-variant' : 'choices-grid--content-variant'
    },

    imageAspectClass(): string {
      const aspect = String((this.config as { image_aspect?: string }).image_aspect ?? '1/1')

      if (aspect === '4/3') return 'choices-card--aspect-4-3'
      if (aspect === '16/9') return 'choices-card--aspect-16-9'

      return 'choices-card--aspect-1-1'
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
.choices-card-shell {
  container-type: inline-size;
}

.choices-card {
  transition:
    box-shadow 120ms ease,
    transform 120ms ease;
}

.choices-card-shell:hover:not(.choices-card-shell--disabled) .choices-card {
  transform: translateY(-1px);
}

.choices-card-shell:has(:focus-visible) .choices-card,
.choices-card-shell--selected .choices-card {
  outline-width: var(--focus-outline-width);
  outline-offset: var(--focus-outline-offset);
  outline-color: var(--focus-outline-color, currentColor);
  outline-style: var(--focus-outline-style, solid);
}

.choices-card__radio,
.choices-card__checkbox {
  width: 1rem;
  height: 1rem;
  border: 1px solid rgb(156 163 175 / 0.75);
  background: white;
  box-shadow: var(--shadow-ui-xs);
}

.dark .choices-card__radio,
.dark .choices-card__checkbox {
  border: none;
  background: var(--color-gray-500);
}

.choices-card__checkbox {
  border-radius: 0.125rem;
  color: white;
}

.choices-card-shell--selected .choices-card__radio {
  border-color: var(--theme-color-ui-accent-bg);
}

.dark .choices-card-shell--selected .choices-card__radio {
  border: none;
  background: var(--color-gray-300);
}

.choices-card-shell--selected .choices-card__checkbox {
  border-color: var(--theme-color-ui-accent-bg);
  background: var(--theme-color-ui-accent-bg);
}

.choices-card__radio-dot {
  display: block;
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 9999px;
  background: var(--theme-color-ui-accent-bg);
}

.choices-card__checkmark {
  width: 0.625rem;
  height: 0.625rem;
}

.choices-card__image {
  border-radius: 8px;
}

.choices-card__image-fallback {
  color: var(--color-gray-925);
}

.dark .choices-card__image-fallback {
  color: var(--color-gray-200);
}

@container (max-width: 220px) {
  .choices-card--content {
    flex-direction: column;
    gap: 10px;
  }

  .choices-card--content .choices-card__control {
    margin-top: 0;
  }

  .choices-card--content .choices-card__image {
    width: 100%;
    height: auto;
    max-height: 120px;
    flex-basis: auto;
  }
}

.choices-card__label {
  color: var(--color-gray-925);
  font-weight: 600;
}

.dark .choices-card__label {
  color: var(--color-gray-200);
}

.choices-card__description,
.choices-card__html {
  color: var(--color-gray-600);
}

.dark .choices-card__description,
.dark .choices-card__html {
  color: var(--color-gray-300);
}
</style>
