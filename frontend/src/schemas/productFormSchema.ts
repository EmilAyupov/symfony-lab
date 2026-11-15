import { z } from 'zod';

export const productFormSchema = z.object({
    name: z
        .string()
        .min(1, 'Введите название товара')
        .max(100, 'Максимум 100 символов'),

    price: z
        .number({ error: 'Введите корректную цену' })
        .positive('Цена должна быть больше нуля'),

    categoryId: z
        .number({ error: 'Выберите категорию' })
        .int('Выберите корректную категорию')
        .positive('Выберите категорию'),
});

export type ProductFormValues = z.infer<typeof productFormSchema>;
