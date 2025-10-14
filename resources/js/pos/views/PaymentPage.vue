<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useSessionStore } from "../stores/useSessionStore.js";
import { getCache, setCache } from "../composables/useCache.js";

const route = useRoute();
const router = useRouter();
const sessionStore = useSessionStore();
const paymentMethods = ref([]);
const paymentAmounts = ref({});
const activeMethodId = ref(null);

// Datos del carrito persistidos antes de navegar
const cached = getCache("pos:checkout", {
    items: [],
    subtotal: 0,
    tax: 0,
    total: 0,
});
const orderLines = ref(cached.items || []);
const orderSubtotal = ref(Number(cached.subtotal || 0));
const orderTax = ref(Number(cached.tax || 0));
const orderTotal = ref(Number(cached.total || 0));

// Selección de tipo de documento
const docType = ref("boleta"); // "boleta" | "factura"

// Cliente seleccionado (por ahora usamos el default del bootstrap)
const customer = ref(sessionStore.defaultCustomer || null);

// Métodos de pago y montos
const paidTotal = computed(() => {
    return Object.values(paymentAmounts.value).reduce(
        (sum, amt) => sum + Number(amt || 0),
        0
    );
});
const remaining = computed(() =>
    Math.max(0, orderTotal.value - paidTotal.value)
);

function selectDoc(type) {
    docType.value = type;
}

function selectCustomer() {
    // Placeholder: usar cliente por defecto del config
    customer.value = sessionStore.defaultCustomer || customer.value;
}

function setActive(id) {
    activeMethodId.value = id;
}

function handleAmountInput(e) {
    const val = Math.max(0, Number(e.target.value || 0));
    if (activeMethodId.value) {
        paymentAmounts.value[activeMethodId.value] = val;
    }
}

function fillExact() {
    // Completa el monto restante en el método activo
    const current = Number(paymentAmounts.value[activeMethodId.value] || 0);
    paymentAmounts.value[activeMethodId.value] = current + remaining.value;
}

function goBack() {
    router.push({ name: "pos-session", params: { id: route.params.id } });
}

async function fetchPaymentMethods() {
    try {
        // Asegurar cookie CSRF para peticiones stateful
        if (!sessionStore.getXsrfToken()) {
            await fetch(`/sanctum/csrf-cookie`, { credentials: "include" });
        }
        const token = sessionStore.getXsrfToken();
        const res = await fetch(`/api/payment-methods`, {
            method: "GET",
            credentials: "include",
            headers: {
                Accept: "application/json",
                ...(token ? { "X-XSRF-TOKEN": token } : {}),
            },
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        paymentMethods.value = Array.isArray(data) ? data : [];
        // Inicializar montos y método activo
        paymentAmounts.value = {};
        paymentMethods.value.forEach((m) => (paymentAmounts.value[m.id] = 0));
        activeMethodId.value = paymentMethods.value[0]?.id || null;
        // Cachear métodos de pago para modo offline
        setCache("pos:paymentMethods", paymentMethods.value);
    } catch (e) {
        console.error("Error al obtener métodos de pago:", e);
        // Marcar desconexión y usar cache local si existe
        sessionStore.setOnline(false);
        const cached = getCache("pos:paymentMethods", []);
        paymentMethods.value = Array.isArray(cached) ? cached : [];
        paymentAmounts.value = {};
        paymentMethods.value.forEach((m) => (paymentAmounts.value[m.id] = 0));
        activeMethodId.value = paymentMethods.value[0]?.id || null;
    }
}

onMounted(() => {
    fetchPaymentMethods();
});

async function pay() {
    // Validación básica
    if (orderTotal.value <= 0 || orderLines.value.length === 0) {
        alert("No hay productos para pagar.");
        return;
    }
    if (paidTotal.value < orderTotal.value) {
        alert("El monto pagado es insuficiente.");
        return;
    }

    // Construir payload compatible con contrato del backend
    const payments = [];
    for (const [id, amount] of Object.entries(paymentAmounts.value)) {
        if (Number(amount) > 0) {
            payments.push({
                payment_method_id: Number(id),
                amount: Number(amount),
            });
        }
    }

    const lines = orderLines.value.map((item) => ({
        variant_id: item.id, // en el carrito usamos variant.id
        quantity: Number(item.quantity || 1),
        price: Number(item.price || 0),
        subtotal: Number(item.price || 0) * Number(item.quantity || 1),
    }));

    const payload = {
        customer_id: customer.value?.id || sessionStore.defaultCustomer?.id,
        voucher_type: docType.value === "factura" ? "invoice" : "receipt",
        lines,
        payments,
        total_amount: orderTotal.value,
    };

    try {
        const result = await sessionStore.sync([payload]);
        console.log("Sync OK", result);
        const info = result?.synced?.[0] || {};

        // Construir datos de boleta para impresión
        const receiptRef = info.sale_id || String(Date.now());
        const receiptData = {
            type: payload.voucher_type,
            serie:
                info.serie ||
                sessionStore.sequences?.[payload.voucher_type]?.serie_code ||
                "",
            correlative: info.correlative || null,
            date: new Date().toISOString(),
            customer: customer.value || sessionStore.defaultCustomer,
            items: orderLines.value.map((item) => ({
                name: item.name || item.product_name || `Var #${item.id}`,
                variant_id: item.id,
                quantity: Number(item.quantity || 1),
                price: Number(item.price || 0),
                subtotal: Number(item.price || 0) * Number(item.quantity || 1),
            })),
            payments: payments.map((p) => ({
                name:
                    paymentMethods.value.find(
                        (m) => m.id === p.payment_method_id
                    )?.name || "Método",
                amount: p.amount,
            })),
            subtotal: orderSubtotal.value,
            tax: orderTax.value,
            total: orderTotal.value,
            isOffline: false,
        };
        setCache(`pos:receipt:${receiptRef}`, receiptData);
        setCache("pos:receipt:last", receiptData);
        // Navegar a vista de boleta para imprimir
        router.push({
            name: "pos-receipt",
            params: { id: route.params.id, ref: receiptRef },
        });
    } catch (e) {
        console.error("Error al sincronizar:", e);
        // Fallback OFFLINE: generar numeración local y permitir imprimir
        try {
            const counter = getCache("pos:offlineReceiptCounter", { next: 1 });
            const offlineCorrelative = String(counter.next).padStart(8, "0");
            const serieCode =
                sessionStore.sequences?.receipt?.serie_code || "LOCAL";
            const receiptRef = `offline-${Date.now()}`;
            const receiptData = {
                type: payload.voucher_type,
                serie: serieCode,
                correlative: offlineCorrelative,
                date: new Date().toISOString(),
                customer: customer.value || sessionStore.defaultCustomer,
                items: orderLines.value.map((item) => ({
                    name: item.name || item.product_name || `Var #${item.id}`,
                    variant_id: item.id,
                    quantity: Number(item.quantity || 1),
                    price: Number(item.price || 0),
                    subtotal:
                        Number(item.price || 0) * Number(item.quantity || 1),
                })),
                payments: payments.map((p) => ({
                    name:
                        paymentMethods.value.find(
                            (m) => m.id === p.payment_method_id
                        )?.name || "Método",
                    amount: p.amount,
                })),
                subtotal: orderSubtotal.value,
                tax: orderTax.value,
                total: orderTotal.value,
                isOffline: true,
            };
            setCache("pos:offlineReceiptCounter", {
                next: Number(counter.next || 1) + 1,
            });
            setCache(`pos:receipt:${receiptRef}`, receiptData);
            setCache("pos:receipt:last", receiptData);
            router.push({
                name: "pos-receipt",
                params: { id: route.params.id, ref: receiptRef },
            });
        } catch (inner) {
            alert(
                `Error al registrar el pago: ${(e && e.message) || e}.` +
                    ` También falló el modo offline: ${
                        (inner && inner.message) || inner
                    }`
            );
        }
    }
}
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- Header simple -->
        <div
            class="flex items-center justify-between px-4 py-2 border-b bg-white"
        >
            <div class="text-sm text-gray-600">
                Sesión #{{ route.params.id }}
            </div>
            <button
                class="text-blue-600 hover:underline text-sm"
                @click="goBack"
            >
                ← Volver
            </button>
        </div>

        <div class="flex-1 grid grid-cols-3 gap-4 p-4 bg-gray-50">
            <!-- Izquierda: Tipo de documento y cliente -->
            <div class="bg-white border rounded-lg p-4 flex flex-col">
                <h3 class="font-semibold mb-3">Documento</h3>
                <div class="flex gap-2 mb-4">
                    <button
                        :class="[
                            'px-3 py-2 rounded border',
                            docType === 'boleta'
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-700',
                        ]"
                        @click="selectDoc('boleta')"
                    >
                        Boleta
                    </button>
                    <button
                        :class="[
                            'px-3 py-2 rounded border',
                            docType === 'factura'
                                ? 'bg-blue-600 text-white border-blue-600'
                                : 'bg-white text-gray-700',
                        ]"
                        @click="selectDoc('factura')"
                    >
                        Factura
                    </button>
                </div>

                <h3 class="font-semibold mb-2">Cliente</h3>
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm text-gray-600 truncate">
                        {{
                            customer?.name ||
                            sessionStore.defaultCustomer?.name ||
                            "Sin cliente seleccionado"
                        }}
                    </div>
                    <button
                        class="px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 border"
                        @click="selectCustomer"
                    >
                        Seleccionar cliente
                    </button>
                </div>

                <div class="mt-auto text-sm text-gray-500">
                    <div class="flex justify-between">
                        <span>Subtotal</span
                        ><span>{{ orderSubtotal.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>IGV</span><span>{{ orderTax.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between font-semibold">
                        <span>Total</span
                        ><span>{{ orderTotal.toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Centro: Monto -->
            <div class="bg-white border rounded-lg p-4 flex flex-col">
                <h3 class="font-semibold mb-3">Monto</h3>
                <div class="mb-2 text-sm text-gray-600">
                    Método activo:
                    {{
                        paymentMethods.find((m) => m.id === activeMethodId)
                            ?.name || "—"
                    }}
                </div>
                <input
                    class="border rounded px-3 py-2 text-xl tracking-wider"
                    type="number"
                    min="0"
                    step="0.01"
                    :value="paymentAmounts[activeMethodId] || 0"
                    @input="handleAmountInput"
                />
                <div class="mt-3 flex gap-2">
                    <button
                        class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700"
                        @click="fillExact"
                    >
                        Completar restante
                    </button>
                    <button
                        v-for="m in paymentMethods"
                        :key="m.id"
                        class="px-3 py-2 rounded bg-gray-100 text-gray-700 border"
                        @click="setActive(m.id)"
                    >
                        {{ m.name }}
                    </button>
                </div>

                <div class="mt-auto text-sm text-gray-700">
                    <div class="flex justify-between">
                        <span>Pagado</span
                        ><span>{{ paidTotal.toFixed(2) }}</span>
                    </div>
                    <div
                        class="flex justify-between"
                        :class="
                            remaining === 0
                                ? 'text-green-600'
                                : 'text-orange-600'
                        "
                    >
                        <span>Restante</span
                        ><span>{{ remaining.toFixed(2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Derecha: Métodos de pago y acción -->
            <div class="bg-white border rounded-lg p-4 flex flex-col">
                <h3 class="font-semibold mb-3">Método de pago</h3>
                <div class="space-y-2">
                    <div
                        v-for="m in paymentMethods"
                        :key="m.id"
                        class="flex items-center justify-between border rounded px-3 py-2"
                    >
                        <div>
                            <div class="text-sm font-medium">{{ m.name }}</div>
                            <div class="text-xs text-gray-500">
                                Ingresa el monto
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-700">{{
                                Number(paymentAmounts[m.id] || 0).toFixed(2)
                            }}</span>
                            <button
                                class="px-2 py-1 rounded bg-gray-100 text-gray-700 border"
                                @click="setActive(m.id)"
                            >
                                Editar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="flex gap-3 mt-4">
                        <button
                            class="flex-1 px-4 py-3 rounded bg-gray-100 text-gray-800 border hover:bg-gray-200"
                            @click="goBack"
                        >
                            Regresar
                        </button>
                        <button
                            class="flex-1 px-4 py-3 rounded bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
                            :disabled="
                                orderTotal <= 0 || orderLines.length === 0
                            "
                            @click="pay"
                        >
                            Pagar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
