import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import ChoicesFieldtype from '../../resources/js/v6/components/ChoicesFieldtype.vue'

type ChoiceOption = {
  value: string
  label: string
  use_html?: boolean
  image_url?: string | null
  image_alt?: string | null
  description?: string | null
  html?: string | null
}

const options: ChoiceOption[] = [
  {
    value: 'basic',
    label: 'Basic',
    image_url: '/basic.svg',
    image_alt: 'Basic illustration',
    description: 'Simple publishing workflow.',
  },
  {
    value: 'pro',
    label: 'Pro',
    image_url: '/pro.svg',
    image_alt: 'Pro illustration',
    description: 'More automation.',
  },
  {
    value: 'enterprise',
    label: 'Enterprise',
    image_url: '/enterprise.svg',
    image_alt: 'Enterprise illustration',
    description: 'Advanced controls.',
  },
]

function mountChoices(props: Record<string, unknown> = {}) {
  return mount(ChoicesFieldtype, {
    props: {
      handle: 'plan',
      value: null,
      meta: { options },
      config: { mode: 'single', variant: 'content', card_width: '50' },
      ...props,
    },
  })
}

describe('ChoicesFieldtype v6', () => {
  it('emits a scalar value when a single choice card is clicked', async () => {
    const wrapper = mountChoices()

    await wrapper.findAll('.choices-card-shell')[1].trigger('click')

    expect(wrapper.emitted('update:value')).toEqual([['pro']])
  })

  it('toggles multiple selections in configured option order', async () => {
    const wrapper = mountChoices({
      value: ['enterprise'],
      config: { mode: 'multiple', variant: 'content', card_width: '50' },
    })

    await wrapper.findAll('.choices-card-shell')[0].trigger('click')
    await wrapper.setProps({ value: ['basic', 'enterprise'] })
    await wrapper.findAll('.choices-card-shell')[2].trigger('click')

    expect(wrapper.emitted('update:value')).toEqual([[['basic', 'enterprise']], [['basic']]])
  })

  it('does not emit updates when disabled or read-only', async () => {
    const disabled = mountChoices({ config: { mode: 'single', disabled: true } })
    const readOnly = mountChoices({ readOnly: true })

    await disabled.find('.choices-card-shell').trigger('click')
    await readOnly.find('.choices-card-shell').trigger('click')

    expect(disabled.emitted('update:value')).toBeUndefined()
    expect(readOnly.emitted('update:value')).toBeUndefined()
  })

  it('renders trusted custom html instead of image and description for content cards', () => {
    const wrapper = mountChoices({
      meta: {
        options: [
          {
            value: 'custom',
            label: 'Custom',
            use_html: true,
            image_url: null,
            description: null,
            html: '<strong data-test="trusted-html">Trusted</strong>',
          },
        ],
      },
    })

    expect(wrapper.find('[data-test="trusted-html"]').exists()).toBe(true)
    expect(wrapper.find('.choices-card__image').exists()).toBe(false)
    expect(wrapper.find('.choices-card__description').exists()).toBe(false)
  })

  it('renders image variant cards with an accessible input label and visual title', () => {
    const wrapper = mountChoices({
      meta: { options: [options[1]] },
      config: { mode: 'single', variant: 'image', image_aspect: '16/9', card_width: '25' },
    })

    expect(wrapper.find('.choices-grid--image-variant').exists()).toBe(true)
    expect(wrapper.find('.choices-card--image').exists()).toBe(true)
    expect(wrapper.find('.choices-card--aspect-16-9').exists()).toBe(true)
    expect(wrapper.find('.choices-card-shell').attributes('title')).toBe('Pro')
    expect(wrapper.find('input').attributes('aria-label')).toBe('Pro')
    expect(wrapper.find('.choices-card__image-full').attributes('src')).toBe('/pro.svg')
  })

  it('maps supported card width config values to grid classes', () => {
    const wrapper = mountChoices({ config: { mode: 'single', variant: 'content', card_width: '33' } })

    expect(wrapper.find('.choices-grid').classes()).toContain('choices-grid--card-width-33')
  })
})
