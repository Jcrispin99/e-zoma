import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import PosApp from './views/PosApp.vue';

const pinia = createPinia();
const app = createApp(PosApp);

app.use(pinia);
app.use(router);
app.mount('#pos');