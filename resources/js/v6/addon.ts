// Statamic Choices — Statamic v6 entry point (Vue 3).
import ChoicesFieldtype from './components/ChoicesFieldtype.vue'

declare const Statamic: any

Statamic.booting?.(() => {
  Statamic.$components?.register('choices-fieldtype', ChoicesFieldtype)
})
