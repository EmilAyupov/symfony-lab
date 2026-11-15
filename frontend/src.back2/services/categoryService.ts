import type { Category } from '../types/category';

const API_URL = '/categories';

export const categoryService = {
    async getAll(): Promise<Category[]> {
        const response = await fetch(API_URL);

        if (!response.ok) {
            throw new Error('Не удалось загрузить категории');
        }

        return response.json() as Promise<Category[]>;
    },
};
