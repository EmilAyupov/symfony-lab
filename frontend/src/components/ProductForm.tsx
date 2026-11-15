import type {Category} from '../types/category';
import {productService} from '../services/productService';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useForm} from 'react-hook-form';
import { z } from 'zod';
import { productSchema } from '../schemas/productSchema';
import { zodResolver } from '@hookform/resolvers/zod';

type ProductFormData = z.infer<typeof productSchema>;

interface ProductFormProps {
    categories: Category[];
}

export function ProductForm({categories}: ProductFormProps) {
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors },
    } = useForm<ProductFormData>({
        resolver: zodResolver(productSchema),
    });

    const queryClient = useQueryClient();

    const createProductMutation = useMutation({
        mutationFn: productService.create,

        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: ['products'],
            });
        },
    });

    const {
        isPending,
        isError,
    } = createProductMutation;

    async function onSubmit(data: ProductFormData) {
        try {
            await createProductMutation.mutateAsync(data);
            reset();
        } catch {
            // Ошибка обрабатывается через isError
        }
    }

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <h2>Добавить продукт</h2>

            <input
                type="text"
                placeholder="Название продукта"
                {...register('name')}
            />
            {errors.name && <p>{errors.name.message}</p>}
            <input
                type="number"
                placeholder="Цена"
                step="0.01"
                {...register('price', {
                    valueAsNumber: true,
                })}
            />
            {errors.price && <p>{errors.price.message}</p>}
            <select
                {...register('categoryId', {
                    valueAsNumber: true,
                })}
            >
                <option value="">Выберите категорию</option>

                {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                        {category.name}
                    </option>
                ))}
            </select>
            {errors.categoryId && <p>{errors.categoryId.message}</p>}
            <button type="submit" disabled={isPending}>
                {isPending ? 'Добавление...' : 'Добавить'}
            </button>

            {isError && <p>Не удалось создать продукт</p>}
        </form>
    );
}
