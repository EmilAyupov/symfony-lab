import {useState} from 'react';
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
    } = useProducts(selectedCategory);

    if (isLoading || categoriesLoading) {
        return <p>Загрузка данных...</p>;
    }

    if (isError || categoriesError) {
        return <p>Не удалось загрузить данные</p>;
    }

    return (
        <div>
            <h2>Список продуктов</h2>

            <div className="products-layout">
                <div className="products-content">
                    <label htmlFor="category-filter">Фильтр по категории</label>
                    <select
                        id="category-filter"
                        className="category-filter"
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
                    {products.length === 0 ? (
                        <p>Продуктов пока нет.</p>
                    ) : (
                        <ul>
                            {products.map((product) => (
                                <li key={product.id} className="product-item">
                                    <span className="product-name">{product.name}</span>
                                    <span className="product-price">{product.price} ₽</span>
                                    <span className="product-category">{product.categoryName}</span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <aside className="products-sidebar">
                    <ProductForm categories={categories}/>
                </aside>
            </div>

        </div>
    );
}
