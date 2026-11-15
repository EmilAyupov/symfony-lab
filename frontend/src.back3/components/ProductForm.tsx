import type {Category} from '../types/category';
import {productService} from '../services/productService';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useForm} from 'react-hook-form';

interface ProductFormData {
    name: string;
    price: number;
    categoryId: number;
}

interface ProductFormProps {
    categories: Category[];
}

export function ProductForm({categories}: ProductFormProps) {
    const {
        register,
        handleSubmit,
        reset,
        formState: {errors},
    } = useForm<ProductFormData>();

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
                {...register('name', {required: 'Введите название продукта'})}
            />
            {errors.name && <p>{errors.name.message}</p>}
            <input
                type="number"
                placeholder="Цена"
                step="0.01"
                {...register('price', {
                    required: 'Введите цену',
                    valueAsNumber: true,
                    min: {
                        value: 0,
                        message: 'Цена не может быть отрицательной',
                    },
                })}
            />
            {errors.price && <p>{errors.price.message}</p>}
            <select
                {...register('categoryId', {
                    required: 'Выберите категорию',
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
