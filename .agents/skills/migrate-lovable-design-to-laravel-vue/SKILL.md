---
name: migrate-lovable-design-to-laravel-vue
description: >
  Переносит дизайн (только UI/вёрстку) из React-проекта, созданного в Lovable.dev,
  в Laravel-проект на Vue 3. Адаптирует shadcn/ui-компоненты под shadcn-vue,
  Tailwind CSS v3 под v4, React-хуки под Vue Composition API. Логика и API-запросы
  не переносятся — только визуальная структура, стили, компоновка и минимальные
  заглушки состояний. Используйте этот скилл при запросах на перенос фронтенд-дизайна
  из Lovable в Laravel + Vue 3.
---

# Перенос дизайна из Lovable (React) в Laravel + Vue 3

## Когда использовать

- Пользователь просит перенести **только дизайн** (вёрстку, стили, компоновку) из React-проекта (Lovable.dev) в Laravel + Vue 3.
- Нужно сохранить визуальный вид: цвета, типографику, отступы, анимации, поведение компонентов.
- Исходный проект использует **shadcn/ui**, **Tailwind CSS**, **lucide-react**.

## Когда НЕ использовать

- Если нужен перенос бизнес-логики, API-интеграций, авторизации, состояний данных — это задача backend/fullstack-разработки, а не дизайна.
- Если целевой проект не на Laravel + Vue 3.
- Если нужно создать дизайн с нуля — используйте `frontend-design`.

## Технологический стек

| Слой | Источник (Lovable/React) | Цель (Laravel/Vue 3) |
|------|--------------------------|----------------------|
| Фреймворк | React 18 | Vue 3.5+ (Composition API, `<script setup>`) |
| Стили | Tailwind CSS v3 | Tailwind CSS v4 |
| UI-компоненты | shadcn/ui (Radix UI) | shadcn-vue (Reka UI) |
| Иконки | `lucide-react` | `lucide-vue-next` |
| Утилита классов | `cn()` (clsx + tailwind-merge) | `cn()` (clsx + tailwind-merge) |
| Роутинг | `react-router-dom` | `vue-router` |
| Состояние UI | React hooks | Vue `ref` / `reactive` / Pinia |
| Шрифты | Google Fonts / local | Google Fonts / `laravel-vite-plugin/fonts` |

## Подготовка целевого проекта (Laravel)

1. Убедитесь, что Laravel-проект использует **Tailwind CSS v4** и **Vite**.
2. Установите зависимости Vue:
   ```bash
   npm install vue vue-router@4 pinia
   npm install -D @vitejs/plugin-vue
   ```
3. Установите зависимости shadcn-vue:
   ```bash
   npm install reka-ui lucide-vue-next clsx tailwind-merge tw-animate-css class-variance-authority
   ```
4. Инициализируйте shadcn-vue:
   ```bash
   npx shadcn-vue@latest init
   ```
   - Выберите `reka-nova` стиль (или тот, что ближе к исходному).
   - Укажите `resources/css/app.css` как CSS-файл.
   - Включите CSS-переменные.

5. Настройте `vite.config.js` — добавьте плагин Vue:
   ```js
   import vue from '@vitejs/plugin-vue';
   // ...
   plugins: [
     laravel({ input: ['resources/css/app.css', 'resources/js/app.js'], refresh: true }),
     tailwindcss(),
     vue({ template: { transformAssetUrls: { includeAbsolute: false } } }),
   ]
   ```

6. Настройте `tsconfig.json` (или `jsconfig.json`) с алиасом `@`:
   ```json
   {
     "compilerOptions": {
       "baseUrl": ".",
       "paths": { "@/*": ["./resources/js/*"] }
     }
   }
   ```

7. Настройте точку входа `resources/js/app.js`:
   ```js
   import './bootstrap';
   import '../css/app.css';
   import { createApp } from 'vue';
   import { createPinia } from 'pinia';
   import App from './App.vue';
   import router from './router';

   const app = createApp(App);
   app.use(createPinia());
   app.use(router);
   app.mount('#app');
   ```

8. Создайте `resources/views/app.blade.php` с `<div id="app"></div>`.

## Перенос дизайн-системы (CSS-переменные)

1. Откройте исходный `src/index.css` (React).
2. Скопируйте блок `:root` с CSS-переменными (цвета HSL, `--radius`, `--sidebar-*`).
3. Вставьте в `resources/css/app.css` целевого проекта **внутри** `:root { ... }`.
4. Скопируйте блок `.dark { ... }` — вставьте аналогично.
5. Добавьте `@theme inline` маппинги для Tailwind v4 (если отсутствуют):
   ```css
   @theme inline {
     --color-background: var(--background);
     --color-foreground: var(--foreground);
     --color-card: var(--card);
     --color-card-foreground: var(--card-foreground);
     --color-popover: var(--popover);
     --color-popover-foreground: var(--popover-foreground);
     --color-primary: var(--primary);
     --color-primary-foreground: var(--primary-foreground);
     --color-secondary: var(--secondary);
     --color-secondary-foreground: var(--secondary-foreground);
     --color-muted: var(--muted);
     --color-muted-foreground: var(--muted-foreground);
     --color-accent: var(--accent);
     --color-accent-foreground: var(--accent-foreground);
     --color-destructive: var(--destructive);
     --color-border: var(--border);
     --color-input: var(--input);
     --color-ring: var(--ring);
     --radius-sm: calc(var(--radius) - 4px);
     --radius-md: calc(var(--radius) - 2px);
     --radius-lg: var(--radius);
     --radius-xl: calc(var(--radius) + 4px);
   }
   ```
6. Добавьте `@layer base` для `body`:
   ```css
   @layer base {
     * { @apply border-border outline-ring/50; }
     body { @apply bg-background text-foreground font-sans; }
   }
   ```

## Перенос UI-компонентов (shadcn/ui → shadcn-vue)

1. Для каждого используемого в дизайне компонента shadcn установите Vue-версию:
   ```bash
   npx shadcn-vue@latest add button card badge select input dialog sheet table
   ```
   (укажите только те, что реально используются в исходном проекте).

2. Если компонент кастомизирован в исходном проекте (изменены стили `cva` или разметка):
   - Сравните сгенерированный shadcn-vue компонент с исходным React-компонентом.
   - Перенесите **только CSS-классы** и структуру слотов — логику Radix заменяет Reka UI автоматически.
   - Не копируйте React-специфичный код (`forwardRef`, `useState`, `children`).

3. Утилита `cn()` уже идентична — используйте `resources/js/lib/utils.js`:
   ```js
   import { clsx } from 'clsx';
   import { twMerge } from 'tailwind-merge';
   export function cn(...inputs) { return twMerge(clsx(inputs)); }
   ```

## Перенос кастомных компонентов (React → Vue SFC)

### Общие правила трансформации

| React (JSX) | Vue (SFC) |
|-------------|-----------|
| `import { Icon } from 'lucide-react'` | `import { Icon } from 'lucide-vue-next'` |
| `const [state, setState] = useState(x)` | `const state = ref(x)` |
| `useEffect(() => { ... }, [])` | `onMounted(() => { ... })` или `watch()` |
| `props: { name: string }` | `defineProps({ name: String })` или `<script setup lang="ts">` с интерфейсой |
| `children` | `<slot />` или именованные `<slot name="action" />` |
| `onClick={handler}` | `@click="handler"` |
| `onChange={(e) => setX(e.target.value)}` | `v-model="x"` или `@update:modelValue` |
| `className={cn(...)}` | `:class="cn(...)"` или `class="..."` |
| `{condition && <A />}` | `<A v-if="condition" />` |
| `{array.map(item => <X key={item.id} />)}` | `<X v-for="item in array" :key="item.id" />` |
| `<Outlet />` (react-router) | `<RouterView />` (vue-router) |
| `useNavigate()` | `useRouter()` из `vue-router` |

### Пример: перенос Layout-компонента

**React (исходный):**
```tsx
export function AppLayout() {
  const [addDialogOpen, setAddDialogOpen] = useState(false);
  return (
    <div className="flex min-h-screen w-full flex-col bg-background md:flex-row">
      <MobileNav onAddClick={() => setAddDialogOpen(true)} />
      <AppSidebar onAddClick={() => setAddDialogOpen(true)} />
      <main className="flex-1 overflow-auto"><Outlet /></main>
      <AddSeriesDialog open={addDialogOpen} onOpenChange={setAddDialogOpen} />
    </div>
  );
}
```

**Vue (результат):**
```vue
<template>
  <div class="flex min-h-screen w-full flex-col bg-background md:flex-row">
    <MobileNav @addClick="setAddDialogOpen(true)" />
    <AppSidebar @addClick="setAddDialogOpen(true)" />
    <main class="flex-1 overflow-auto"><RouterView /></main>
    <AddSeriesDialog :open="addDialogOpen" @update:open="setAddDialogOpen" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { RouterView } from 'vue-router';
const addDialogOpen = ref(false);
function setAddDialogOpen(v) { addDialogOpen.value = v; }
</script>
```

### Пример: перенос Page-компонента

**React (исходный):**
```tsx
export default function Dashboard() {
  const [searchQuery, setSearchQuery] = useState('');
  return (
    <div className="p-6 space-y-6">
      <Input value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} />
      {filteredSeries.length > 0 ? <SeriesTable series={filteredSeries} /> : <EmptyState />}
    </div>
  );
}
```

**Vue (результат):**
```vue
<template>
  <div class="p-6 space-y-6">
    <Input v-model="searchQuery" />
    <SeriesTable v-if="filteredSeries.length > 0" :series="filteredSeries" />
    <EmptyState v-else />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
const searchQuery = ref('');
// Минимальная заглушка данных — только для отображения дизайна
const filteredSeries = ref([ /* ... минимум 1-2 элемента ... */ ]);
</script>
```

## Перенос страниц и роутинга

1. Создайте `resources/js/router/index.js`:
   ```js
   import { createRouter, createWebHistory } from 'vue-router';
   import Dashboard from '@/pages/Dashboard.vue';
   // ... импорты других страниц

   const routes = [
     { path: '/', component: Dashboard },
     { path: '/downloads', component: () => import('@/pages/ActiveDownloads.vue') },
     // ... остальные маршруты
     { path: '/:pathMatch(.*)*', component: () => import('@/pages/NotFound.vue') },
   ];

   export default createRouter({ history: createWebHistory(), routes });
   ```

2. Скопируйте структуру маршрутов из `src/App.tsx` (React Router) в `router/index.js`.

3. Перенесите каждую страницу из `src/pages/*.tsx` → `resources/js/pages/*.vue`:
   - Сохраните CSS-классы и разметку.
   - Замените React-импорты на Vue-импорты.
   - Замените состояния на `ref` / `computed`.
   - Добавьте минимальные mock-данные, чтобы компоненты отображались корректно.

## Перенос темы (ThemeProvider)

1. Создайте Pinia-store `resources/js/stores/ThemeStore.js`:
   ```js
   import { defineStore } from 'pinia';
   import { ref, computed } from 'vue';

   export const useThemeStore = defineStore('theme', () => {
     const theme = ref(localStorage.getItem('theme') || 'dark');
     const isDark = computed(() => theme.value === 'dark');
     function setTheme(t) { theme.value = t; localStorage.setItem('theme', t); }
     return { theme, isDark, setTheme };
   });
   ```

2. В `App.vue` добавьте `:class="{ dark: isDark }"` на корневой элемент.

3. Перенесите `ThemeToggle.vue` — замените `useTheme` из React на `useThemeStore` из Pinia.

## Чек-лист завершения переноса

- [ ] Все CSS-переменные (`:root` и `.dark`) перенесены в `resources/css/app.css`.
- [ ] Все shadcn-компоненты установлены через `shadcn-vue add`.
- [ ] Все кастомные компоненты перенесены в `resources/js/components/` как `.vue`.
- [ ] Все страницы перенесены в `resources/js/pages/` как `.vue`.
- [ ] Роутер создан и маршруты соответствуют исходному проекту.
- [ ] Иконки заменены на `lucide-vue-next` (имена импортов не меняются).
- [ ] Состояния UI (тема, диалоги, сайдбар) реализованы через `ref` / Pinia.
- [ ] Mock-данные добавлены минимально — только для корректного отображения UI.
- [ ] `npm run build` выполняется без ошибок.
- [ ] Визуальный результат идентичен исходному (проверить в браузере).

## Типичные проблемы

| Проблема | Решение |
|----------|---------|
| `className` остался в шаблоне | Заменить на `class` или `:class` |
| `children` не определён | Заменить на `<slot />` |
| Иконки не отображаются | Проверить импорт из `lucide-vue-next`, а не `lucide-react` |
| `v-model` не работает на кастомном input | Убедиться, что компонент эмитит `update:modelValue` |
| Tailwind классы не применяются | Проверить `@source` в `app.css` — должны быть пути к `.vue` и `.blade.php` |
| Тёмная тема не переключается | Проверить наличие `.dark` класса на `<html>` или корневом элементе |
| `cn()` не импортируется | Проверить алиас `@` в `vite.config.js` и `tsconfig.json` |

## Ограничения

- Этот скилл переносит **только дизайн** — визуальную структуру, стили, компоновку.
- Логика обработки данных, API-запросы, авторизация, валидация форм — не переносятся.
- React-специфичные библиотеки (React Query, React Router, Zustand и т.д.) заменяются на Vue-эквиваленты только в объёме, необходимом для отображения UI.
