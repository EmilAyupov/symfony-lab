import { useState } from 'react';
import {useCategories} from '../hooks/useCategories';
import {ProductForm} from './ProductForm';
import {useProducts} from '../hooks/useProducts';

export function ProductList() {
    const [selectedCategory, setSelectedCategory] = useState<number | null>(null);

    const {
        data: categories = [],
        isLoading: categoriesLoading,
        isError: categoriesError,
    } = useCategories();

    const {
        data: products = [],
        isLoading,
        isError,
    } = useProducts(selectedCategory); // здесь ругается

    if (isLoading || categoriesLoading) {
        return <p>Загрузка данных...</p>;
    }

    if (isError || categoriesError) {
        return <p>Не удалось загрузить данные</p>;
    }

    return (
        <div>
            <h2>Список продуктов</h2>

            <select
                value={selectedCategory ?? ''} // здесь ругается
                onChange={(event) => {
                    const value = event.target.value;
                    setSelectedCategory(value ? Number(value) : null); // здесь ругается
                }}
            >
                <option value="">Все категории</option>

                {categories.map((category) => (
                    <option key={category.id} value={category.id}>
                        {category.name}
                    </option>
                ))}
            </select>

            <ProductForm
                categories={categories}
            />

            {products.length === 0 ? (
                <p>Продуктов пока нет.</p>
            ) : (
                <ul>
                    {products.map((product) => (
                        <li key={product.id}>
                            {product.name} — {product.price} ₽
                            ({product.categoryName})
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
