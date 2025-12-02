import { createRouter, createWebHistory } from 'vue-router';
import ProductList from '../components/ProductList.vue';
import PaymentPage from '../views/PaymentPage.vue';
import ReceiptPage from '../views/ReceiptPage.vue';

const routes = [
  {
    path: '/',
    name: 'pos',
    component: ProductList,
  },
  {
    path: '/:id',
    name: 'pos-session',
    component: ProductList,
  },
  {
    path: '/:id/checkout',
    name: 'pos-checkout',
    component: PaymentPage,
  },
  {
    path: '/:id/receipt/:ref',
    name: 'pos-receipt',
    component: ReceiptPage,
  },
];

const router = createRouter({
  history: createWebHistory('/pos/'),
  routes,
});

export default router;
