import { z } from 'zod';

export const productSchema = z.object({
    name: z.string().min(1, 'Введите название продукта!!!'),
    price: z.number().min(0, 'Цена не может быть отрицательной'),
    categoryId: z.number({
        error: 'Выберите категорию',
    }).min(1, 'Выберите категорию'),
});
