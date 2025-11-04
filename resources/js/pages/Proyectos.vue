<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import  Inertia from '@inertiajs/inertia';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Productos', href: '/products' },
];

const props = defineProps<{
    products: Array<{ id: number, name: string, price: number, codigo_inmueble: string, category?: { category_name: string } }>,
    categories: Array<{ id: number, category_name: string }>
}>();

const newProduct = ref({
    name: '',
    codigo_inmueble: '',
    price: 0,
    category_id: null as number | null
});

const createProduct = () => {
    Inertia.post('/products', newProduct.value, {
        onSuccess: () => {
            newProduct.value = { name: '', codigo_inmueble: '', price: 0, category_id: null };
        }
    });
};

const deleteProduct = (id: number) => {
    if(confirm('¿Deseas eliminar este producto?')) {
        Inertia.delete(`/products/${id}`);
    }
};
</script>


<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Productos" />

        <h1 class="text-2xl font-bold mb-4">Productos</h1>

        <!-- Formulario para crear productos -->
        <div class="mb-6 p-4 border rounded">
            <h2 class="font-semibold mb-2">Agregar Producto</h2>
            <input v-model="newProduct.name" placeholder="Nombre" class="border p-2 mb-2 w-full"/>
            <input v-model="newProduct.codigo_inmueble" placeholder="Código" class="border p-2 mb-2 w-full"/>
            <input v-model.number="newProduct.price" type="number" placeholder="Precio" class="border p-2 mb-2 w-full"/>
            <select v-model="newProduct.category_id" class="border p-2 mb-2 w-full">
                <option value="">Seleccionar categoría</option>
                <option v-for="cat in props.categories" :key="cat.id" :value="cat.id">
                    {{ cat.category_name }}
                </option>
            </select>
            <button @click="createProduct" class="bg-blue-500 text-white px-4 py-2 rounded">Crear Producto</button>
        </div>

        <!-- Tabla de productos -->
        <table class="w-full border-collapse border">
            <thead>
                <tr>
                    <th class="border p-2">ID</th>
                    <th class="border p-2">Nombre</th>
                    <th class="border p-2">Código</th>
                    <th class="border p-2">Precio</th>
                    <th class="border p-2">Categoría</th>
                    <th class="border p-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in props.products" :key="product.id">
                    <td class="border p-2">{{ product.id }}</td>
                    <td class="border p-2">{{ product.name }}</td>
                    <td class="border p-2">{{ product.codigo_inmueble }}</td>
                    <td class="border p-2">{{ product.price }}</td>
                    <td class="border p-2">{{ product.category?.category_name || '-' }}</td>
                    <td class="border p-2">
                        <button @click="deleteProduct(product.id)" class="bg-red-500 text-white px-2 py-1 rounded">Eliminar</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </AppLayout>
</template>
