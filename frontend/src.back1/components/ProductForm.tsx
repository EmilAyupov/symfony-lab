import { useState } from 'react';
import type { Category } from '../types/category';
import { productService } from '../services/productService';

interface ProductFormProps {
    categories: Category[];
    onProductCreated: () => void;
}

export function ProductForm({ categories, onProductCreated }: ProductFormProps) {
    const [name, setName] = useState('');
    const [price, setPrice] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        setError(null);
        setLoading(true);

        try {
            await productService.create({
                name,
                price: Number(price),
                categoryId: Number(categoryId),
            });

            setName('');
            setPrice('');
            setCategoryId('');

            onProductCreated();
        } catch {
            setError('Не удалось создать продукт');
        } finally {
            setLoading(false);
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

            <button type="submit" disabled={loading}>
                {loading ? 'Добавление...' : 'Добавить'}
            </button>

            {error && <p>{error}</p>}
        </form>
    );
}
