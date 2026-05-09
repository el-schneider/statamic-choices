import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vitest/config'

const statamicUiMock = new URL('./tests/js/mocks/statamic-ui.ts', import.meta.url).pathname

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@statamic/cms/ui': statamicUiMock,
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['tests/js/**/*.test.ts'],
  },
})
