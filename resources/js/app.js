import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
// import { initTheme } from './composables/useTheme';

// Инициализируем тему до монтирования приложения
// initTheme();

const app = createApp(App);
app.use(router);
app.mount('#app');
