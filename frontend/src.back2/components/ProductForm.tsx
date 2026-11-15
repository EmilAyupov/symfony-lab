import {useState} from 'react';
import type {Category} from '../types/category';
import {productService} from '../services/productService';
import {useMutation, useQueryClient} from '@tanstack/react-query';

interface ProductFormProps {
    categories: Category[];
}

export function ProductForm({categories}: ProductFormProps) {
    const [name, setName] = useState('');
    const [price, setPrice] = useState('');
    const [categoryId, setCategoryId] = useState('');

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

    async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        try {
            await createProductMutation.mutateAsync({
                name,
                price: Number(price),
                categoryId: Number(categoryId),
            });

            setName('');
            setPrice('');
            setCategoryId('');
        } catch {
            // Ошибка обрабатывается через isError
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            <h2>Добавить продукт</h2>

            <input
                type="text"
                placeholder="Название продукта"
                value={name}
                onChange={(event) => setName(event.target.value)}
                required
            />

            <input
                type="number"
                placeholder="Цена"
                value={price}
                onChange={(event) => setPrice(event.target.value)}
                required
                min="0"
                step="0.01"
            />

            <select
                value={categoryId}
                onChange={(event) => setCategoryId(event.target.value)}
                required
            >
                <option value="">Выберите категорию</option>

                {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                        {category.name}
                    </option>
                ))}
            </select>

            <button type="submit" disabled={isPending}>
                {isPending ? 'Добавление...' : 'Добавить'}
            </button>

            {isError && <p>Не удалось создать продукт</p>}
        </form>
    );
}
