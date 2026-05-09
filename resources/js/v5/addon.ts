// Statamic Choices — Statamic v5 entry point (Vue 2 / Options API).

declare const Fieldtype: any
declare const Statamic: any

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

type ChoicesComponent = {
  value: string | number | boolean | string[] | null
  handle: string
  name?: string
  namePrefix?: string | null
  meta?: { options?: ChoiceOption[] }
  config?: Record<string, unknown>
  readOnly?: boolean
  showFieldPreviews?: boolean
  options: ChoiceOption[]
  selectedValues: string[]
  isMultiple: boolean
  isImageVariant: boolean
  isReadOnly: boolean
  isDisabled: boolean
  isSelected: (value: string) => boolean
  setMultipleSelection: (value: string, selected: boolean) => void
  sortValues: (values: string[]) => string[]
  update: (value: string | string[] | null) => void
  $emit: (event: string, value?: string) => void
  $el: HTMLElement
}

const ChoicesFieldtype = {
  name: 'ChoicesFieldtype',

  mixins: [Fieldtype],

  props: {
    handle: { type: String, required: true },
    value: { type: [String, Array, Number, Boolean], default: null },
    meta: { type: Object, default: () => ({}) },
    config: { type: Object, default: () => ({}) },
    readOnly: { type: Boolean, default: false },
    namePrefix: { type: String, default: null },
    showFieldPreviews: { type: Boolean, default: false },
  },

  computed: {
    options(): ChoiceOption[] {
      const self = this as unknown as ChoicesComponent

      return self.meta?.options ?? []
    },

    isMultiple(): boolean {
      const self = this as unknown as ChoicesComponent

      return self.config?.mode === 'multiple'
    },

    isImageVariant(): boolean {
      const self = this as unknown as ChoicesComponent

      return self.config?.variant === 'image'
    },

    inputType(): string {
      const self = this as unknown as ChoicesComponent

      return self.isMultiple ? 'checkbox' : 'radio'
    },

    inputName(): string {
      const self = this as unknown as ChoicesComponent
      const baseName = self.name ?? (self.namePrefix ? `${self.namePrefix}[${self.handle}]` : self.handle)

      return self.isMultiple ? `${baseName}[]` : baseName
    },

    selectedValues(): string[] {
      const self = this as unknown as ChoicesComponent

      if (self.isMultiple) {
        return Array.isArray(self.value) ? self.sortValues(self.value.map(String)) : []
      }

      return self.value === null || self.value === undefined || self.value === '' ? [] : [String(self.value)]
    },

    cardWidthClass(): string {
      const self = this as unknown as ChoicesComponent
      const cardWidth = String(self.config?.card_width ?? 100)

      return ['100', '50', '33', '25', '20'].includes(cardWidth)
        ? `choices-grid--card-width-${cardWidth}`
        : 'choices-grid--card-width-100'
    },

    variantClass(): string {
      const self = this as unknown as ChoicesComponent

      return self.isImageVariant ? 'choices-grid--image-variant' : 'choices-grid--content-variant'
    },

    imageAspectClass(): string {
      const self = this as unknown as ChoicesComponent
      const aspect = String(self.config?.image_aspect ?? '1/1')

      if (aspect === '4/3') return 'choices-card--aspect-4-3'
      if (aspect === '16/9') return 'choices-card--aspect-16-9'

      return 'choices-card--aspect-1-1'
    },

    isReadOnly(): boolean {
      const self = this as unknown as ChoicesComponent

      return self.readOnly === true || self.config?.visibility === 'read_only' || self.config?.visibility === 'computed'
    },

    isDisabled(): boolean {
      const self = this as unknown as ChoicesComponent

      return Boolean(self.config?.disabled)
    },

    replicatorPreview(): string | undefined {
      const self = this as unknown as ChoicesComponent

      if (!self.showFieldPreviews) return undefined

      return self.selectedValues
        .map((value) => self.options.find((option) => String(option.value) === value)?.label ?? value)
        .join(', ')
    },
  },

  watch: {
    replicatorPreview: {
      immediate: true,
      handler(text: string | undefined) {
        const self = this as unknown as ChoicesComponent

        if (!self.showFieldPreviews) return

        self.$emit('replicator-preview-updated', text)
      },
    },
  },

  methods: {
    isSelected(value: string): boolean {
      const self = this as unknown as ChoicesComponent

      return self.selectedValues.includes(String(value))
    },

    handleInput(value: string, event: Event): void {
      const self = this as unknown as ChoicesComponent

      if (self.isReadOnly || self.isDisabled) return

      if (!self.isMultiple) {
        self.update(value)
        return
      }

      self.setMultipleSelection(value, (event.target as HTMLInputElement).checked)
    },

    handleCardClick(value: string, event: MouseEvent): void {
      const self = this as unknown as ChoicesComponent

      if (self.isReadOnly || self.isDisabled) return

      const target = event.target as HTMLElement

      if (target.closest('a[href], button, input, select, textarea, [role="button"]')) {
        return
      }

      event.preventDefault()

      if (!self.isMultiple) {
        self.update(value)
        return
      }

      self.setMultipleSelection(value, !self.isSelected(value))
    },

    setMultipleSelection(value: string, selected: boolean): void {
      const self = this as unknown as ChoicesComponent

      if (self.isReadOnly || self.isDisabled) return

      const values = new Set(self.selectedValues)

      if (selected) {
        values.add(String(value))
      } else {
        values.delete(String(value))
      }

      self.update(self.sortValues([...values]))
    },

    sortValues(values: string[]): string[] {
      const self = this as unknown as ChoicesComponent

      return self.options
        .filter((option) => values.includes(String(option.value)))
        .map((option) => String(option.value))
    },

    focus(): void {
      const self = this as unknown as ChoicesComponent

      self.$el.querySelector<HTMLInputElement>('input')?.focus()
    },
  },

  template: /* vue */ `
        <div class="choices-fieldtype choices-fieldtype--v5">
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
                        'choices-card-shell--disabled': isReadOnly || isDisabled
                    }"
                    :title="isImageVariant ? option.label : null"
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

                    <span
                        class="choices-card"
                        :class="[
                            isImageVariant ? 'choices-card--image' : 'choices-card--content',
                            isImageVariant ? imageAspectClass : null
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
                    </span>
                </label>
            </div>
        </div>
    `,
}

Statamic.booting?.(() => {
  Statamic.$components?.register('choices-fieldtype', ChoicesFieldtype)
})
