import { createRouter, createWebHistory } from "vue-router";
import ProductList from "../components/ProductList.vue";

const routes = [
    {
        path: "/",
        name: "pos",
        component: ProductList,
    },
    {
        path: "/:id",
        name: "pos-session",
        component: ProductList,
    },
];

const router = createRouter({
    history: createWebHistory("/pos/"),
    routes,
});

export default router;
