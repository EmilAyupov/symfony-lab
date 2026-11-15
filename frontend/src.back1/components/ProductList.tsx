import {useEffect, useState} from 'react';
import type {Product} from '../types/product';
import {productService} from '../services/productService';
import type {Category} from '../types/category';
import {categoryService} from '../services/categoryService';
import { ProductForm } from './ProductForm';

export function ProductList() {
    const [products, setProducts] = useState<Product[]>([]);
    const [categories, setCategories] = useState<Category[]>([]);
    const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [refreshKey, setRefreshKey] = useState(0);

    useEffect(() => {
        categoryService.getAll()
            .then(setCategories)
            .catch(() => setError('Не удалось загрузить категории'));
    }, []);

    useEffect(() => {
        setLoading(true);
        setError(null);

        const request = selectedCategory === null
            ? productService.getAll()
            : productService.getByCategory(selectedCategory);

        request
            .then(setProducts)
            .catch(() => setError('Не удалось загрузить продукты'))
            .finally(() => setLoading(false));
    }, [selectedCategory, refreshKey]);

    if (loading) {
        return <p>Загрузка продуктов...</p>;
    }

    if (error) {
        return <p>{error}</p>;
    }

    return (
        <div>
            <h2>Список продуктов</h2>

            <select
                value={selectedCategory ?? ''}
                onChange={(event) => {
                    const value = event.target.value;
                    setSelectedCategory(value ? Number(value) : null);
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
                onProductCreated={() => setRefreshKey((key) => key + 1)}
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
