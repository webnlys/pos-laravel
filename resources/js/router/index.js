import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    { path: '/login', name: 'login', component: () => import('../pages/Login.vue'), meta: { guest: true } },
    {
        path: '/admin',
        component: () => import('../layouts/AdminLayout.vue'),
        meta: { auth: true, role: 'admin' },
        children: [
            { path: '', name: 'admin.home', component: () => import('../pages/admin/Home.vue') },
            { path: 'products', name: 'admin.products', component: () => import('../pages/admin/products/Index.vue') },
            { path: 'products/create', name: 'admin.products.create', component: () => import('../pages/admin/products/Form.vue') },
            { path: 'products/:id/edit', name: 'admin.products.edit', component: () => import('../pages/admin/products/Form.vue') },
            { path: 'customers', name: 'admin.customers', component: () => import('../pages/admin/customers/Index.vue') },
            { path: 'customers/create', name: 'admin.customers.create', component: () => import('../pages/admin/customers/Form.vue') },
            { path: 'customers/:id/edit', name: 'admin.customers.edit', component: () => import('../pages/admin/customers/Form.vue') },
            { path: 'suppliers', name: 'admin.suppliers', component: () => import('../pages/admin/suppliers/Index.vue') },
            { path: 'suppliers/create', name: 'admin.suppliers.create', component: () => import('../pages/admin/suppliers/Form.vue') },
            { path: 'suppliers/:id/edit', name: 'admin.suppliers.edit', component: () => import('../pages/admin/suppliers/Form.vue') },
            { path: 'purchases', name: 'admin.purchases', component: () => import('../pages/admin/purchases/Index.vue') },
            { path: 'purchases/create', name: 'admin.purchases.create', component: () => import('../pages/admin/purchases/Form.vue') },
            { path: 'purchases/:id/edit', name: 'admin.purchases.edit', component: () => import('../pages/admin/purchases/Form.vue') },
            { path: 'sales', name: 'admin.sales', component: () => import('../pages/admin/sales/Index.vue') },
            { path: 'sales/create', name: 'admin.sales.create', component: () => import('../pages/admin/sales/Form.vue') },
            { path: 'sales/:id/edit', name: 'admin.sales.edit', component: () => import('../pages/admin/sales/Form.vue') },
            { path: 'quotations', name: 'admin.quotations', component: () => import('../pages/admin/quotations/Index.vue') },
            { path: 'quotations/:id', name: 'admin.quotations.show', component: () => import('../pages/admin/quotations/Show.vue') },
            { path: 'payments', name: 'admin.payments', component: () => import('../pages/admin/payments/Index.vue') },
            { path: 'payments/create', name: 'admin.payments.create', component: () => import('../pages/admin/payments/Form.vue') },
            { path: 'payments/:id/edit', name: 'admin.payments.edit', component: () => import('../pages/admin/payments/Form.vue') },
            { path: 'taxes', name: 'admin.taxes', component: () => import('../pages/admin/taxes/Index.vue') },
            { path: 'taxes/create', name: 'admin.taxes.create', component: () => import('../pages/admin/taxes/Form.vue') },
            { path: 'taxes/:id/edit', name: 'admin.taxes.edit', component: () => import('../pages/admin/taxes/Form.vue') },
            { path: 'settings', name: 'admin.settings', component: () => import('../pages/admin/Settings.vue') },
            { path: 'reports', name: 'admin.reports', component: () => import('../pages/admin/reports/Index.vue') },
        ],
    },
    {
        path: '/customer',
        component: () => import('../layouts/CustomerLayout.vue'),
        meta: { auth: true, role: 'customer' },
        children: [
            { path: '', redirect: { name: 'customer.quotations' } },
            { path: 'quotations', name: 'customer.quotations', component: () => import('../pages/customer/quotations/Index.vue') },
            { path: 'quotations/create', name: 'customer.quotations.create', component: () => import('../pages/customer/quotations/Form.vue') },
            { path: 'quotations/:id/edit', name: 'customer.quotations.edit', component: () => import('../pages/customer/quotations/Form.vue') },
            { path: 'sales', name: 'customer.sales', component: () => import('../pages/customer/sales/Index.vue') },
            { path: 'sales/create', name: 'customer.sales.create', component: () => import('../pages/customer/sales/Form.vue') },
            { path: 'sales/:id/edit', name: 'customer.sales.edit', component: () => import('../pages/customer/sales/Form.vue') },
        ],
    },
    { path: '/', redirect: '/login' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();
    if (!auth.user) {
        await auth.fetchUser();
    }

    if (to.meta.auth && !auth.user) {
        return { name: 'login' };
    }

    if (to.meta.guest && auth.user) {
        return auth.isAdmin ? { name: 'admin.home' } : { name: 'customer.quotations' };
    }

    if (to.meta.role && auth.user && auth.user.role !== to.meta.role) {
        return auth.isAdmin ? { name: 'admin.home' } : { name: 'customer.quotations' };
    }

    return true;
});

export default router;
