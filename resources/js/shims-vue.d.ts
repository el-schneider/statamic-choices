declare module '*.vue' {
  import type { DefineComponent } from 'vue'

  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}

declare const Fieldtype: unknown

declare const Statamic: {
  booting?: (callback: () => void) => void
  $components?: {
    register: (name: string, component: unknown) => void
  }
}

declare function __(key: string, replacements?: Record<string, string | number>): string

declare module '@statamic/cms/ui' {
  import type { DefineComponent } from 'vue'

  export const Card: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
}
