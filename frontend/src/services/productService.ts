import type { Product } from '../types/product';
import { api } from '../lib/api';

export const productService = {
    async getAll(): Promise<Product[]> {
        const response = await api.get<Product[]>('/products');
        return response.data;
    },

    async getByCategory(categoryId: number): Promise<Product[]> {
        const response = await api.get<Product[]>('/products', {
            params: { categoryId },
        });

        return response.data;
    },

    async create(data: {
        name: string;
        price: number;
        categoryId: number;
    }): Promise<Product> {
        const response = await api.post<Product>('/products', data);
        return response.data;
    },
};
