import { defineComponent, h } from 'vue'

export const Card = defineComponent({
  name: 'StatamicCardStub',
  inheritAttrs: false,
  setup(_, { attrs, slots }) {
    return () => h('div', attrs, slots.default?.())
  },
})
