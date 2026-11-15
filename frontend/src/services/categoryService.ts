import type { Category } from '../types/category';
import { api } from '../lib/api';

export const categoryService = {
    async getAll(): Promise<Category[]> {
        const response = await api.get<Category[]>('/categories');

        return response.data;
    },
};
