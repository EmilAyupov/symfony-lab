import type {Product} from '../types/product';

const API_URL = '/products';

export const productService = {
    async getAll(): Promise<Product[]> {
        const response = await fetch(API_URL);

        if (!response.ok) {
            throw new Error('Не удалось загрузить продукты');
        }

        return response.json() as Promise<Product[]>;
    },
    async getByCategory(categoryId: number): Promise<Product[]> {
        const response = await fetch(`${API_URL}?categoryId=${categoryId}`);

        if (!response.ok) {
            throw new Error('Не удалось загрузить продукты категории');
        }

        return response.json() as Promise<Product[]>;
    },
    async create(data: {
        name: string;
        price: number;
        categoryId: number;
    }): Promise<Product> {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            throw new Error('Не удалось создать продукт');
        }

        return response.json() as Promise<Product>;
    },
};
