import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PosApp from './views/PosApp.vue';

const pinia = createPinia();
const app = createApp(PosApp);

app.use(pinia);
app.mount('#pos');