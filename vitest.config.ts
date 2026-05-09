import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@statamic/cms/ui': '/tests/js/mocks/statamic-ui.ts',
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['tests/js/**/*.test.ts'],
  },
})
