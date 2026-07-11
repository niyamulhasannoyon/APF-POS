

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

import { createApp } from 'vue';
import PosApp from './components/PosApp.vue';

const appEl = document.getElementById('app');
if (appEl) {
    createApp(PosApp).mount('#app');
}
