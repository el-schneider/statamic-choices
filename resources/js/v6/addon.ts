// Statamic Choices — Statamic v6 entry point (Vue 3).
import '../shared/choices.css'
import ChoicesFieldtype from './components/ChoicesFieldtype.vue'

Statamic.booting?.(() => {
  Statamic.$components?.register('choices-fieldtype', ChoicesFieldtype)
})
