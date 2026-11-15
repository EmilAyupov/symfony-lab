import {useEffect, useMemo, useRef, useState} from 'react';
import {
    flexRender,
    getCoreRowModel,
    useReactTable,
    type ColumnDef,
} from '@tanstack/react-table';

import {useProducts} from '../hooks/useProducts';
import type {Product} from '../types/product';
import {useCategories} from '../hooks/useCategories';
import {productFormSchema} from '../schemas/productFormSchema';

import './ProductsTablePage.css';

export function ProductsTablePage() {
    const {data: categories = []} = useCategories();

    const {data: products, isLoading, isError} = useProducts(null);

    const [tableProducts, setTableProducts] = useState<Product[]>([]);
    const [editingProductId, setEditingProductId] = useState<number | null>(null);

    const [originalProduct, setOriginalProduct] = useState<Product | null>(null);

    const [validationErrors, setValidationErrors] = useState<
        Record<string, string>
    >({});

    const validationErrorsRef = useRef(validationErrors);
    validationErrorsRef.current = validationErrors;

    useEffect(() => {
        if (products) {
            setTableProducts(products);
        }
    }, [products]);

    const updateProductName = (id: number, name: string) => {
        setTableProducts((prev) =>
            prev.map((product) =>
                product.id === id ? {...product, name} : product
            )
        );
    };

    const updateProductPrice = (id: number, price: number) => {
        setTableProducts((prev) =>
            prev.map((product) =>
                product.id === id ? {...product, price} : product
            )
        );
    };

    const columns = useMemo<ColumnDef<Product>[]>(() => [
        {
            accessorKey: 'id',
            header: 'ID',
            cell: ({ row }) => (
                <span className="product-id">
            #{row.original.id}
        </span>
            ),
        },
        {
            accessorKey: 'name',
            header: 'Название',
            cell: ({row}) =>
                editingProductId === row.original.id ? (
                    <div>
                        <input
                            type="text"
                            value={row.original.name}
                            onChange={(e) => {
                                updateProductName(row.original.id, e.target.value);

                                setValidationErrors((prev) => ({
                                    ...prev,
                                    name: '',
                                }));
                            }}
                        />

                        {validationErrors.name && (
                            <div style={{color: 'red', fontSize: '12px'}}>
                                {validationErrors.name}
                            </div>
                        )}
                    </div>
                ) : (
                    row.original.name
                ),
        },
        {
            accessorKey: 'price',
            header: 'Цена',
            cell: ({row}) =>
                editingProductId === row.original.id ? (
                    <div>
                        <input
                            type="number"
                            value={row.original.price}
                            onChange={(e) => {
                                updateProductPrice(row.original.id, Number(e.target.value));
                                setValidationErrors((prev) => ({
                                    ...prev,
                                    price: '',
                                }));
                            }}
                        />
                        {validationErrors.price && (
                            <div style={{color: 'red', fontSize: '12px'}}>
                                {validationErrors.price}
                            </div>
                        )}
                    </div>
                ) : (
                    row.original.price
                ),
        },
        {
            accessorKey: 'categoryName',
            header: 'Категория',
            cell: ({row}) =>
                editingProductId === row.original.id ? (
                    <div>
                        <select
                            value={row.original.categoryName}
                            onChange={(e) => {
                                const categoryName = e.target.value;

                                setTableProducts((prev) =>
                                    prev.map((product) =>
                                        product.id === row.original.id
                                            ? {...product, categoryName}
                                            : product
                                    )
                                );

                                setValidationErrors((prev) => ({
                                    ...prev,
                                    categoryId: '',
                                }));
                            }}
                        >
                            {categories.map((category) => (
                                <option key={category.id} value={category.name}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                        {validationErrors.categoryId && (
                            <div style={{color: 'red', fontSize: '12px'}}>
                                {validationErrors.categoryId}
                            </div>
                        )}
                    </div>

                ) : (
                    row.original.categoryName
                ),
        },
        {
            id: 'actions',
            header: 'Действия',
            cell: ({row}) =>
                editingProductId === row.original.id ? (
                    <>
                        <button
                            onClick={() => {
                                const product = tableProducts.find(
                                    (item) => item.id === editingProductId
                                );

                                if (!product) return;

                                const result = productFormSchema.safeParse({
                                    name: product.name,
                                    price: product.price,
                                    categoryId: categories.find(
                                        (category) => category.name === product.categoryName
                                    )?.id,
                                });

                                if (!result.success) {
                                    const errors: Record<string, string> = {};

                                    result.error.issues.forEach((issue) => {
                                        const field = String(issue.path[0]);
                                        errors[field] = issue.message;
                                    });

                                    setValidationErrors(errors);
                                    return;
                                }

                                setValidationErrors({});

                                setEditingProductId(null);
                                setOriginalProduct(null);
                            }}
                        >
                            Сохранить
                        </button>

                        <button
                            onClick={() => {
                                if (originalProduct) {
                                    setTableProducts((prev) =>
                                        prev.map((product) =>
                                            product.id === originalProduct.id
                                                ? originalProduct
                                                : product
                                        )
                                    );
                                }

                                setEditingProductId(null);
                                setOriginalProduct(null);
                            }}
                        >
                            Отмена
                        </button>
                    </>
                ) : (
                    <button
                        onClick={() => {
                            setOriginalProduct({...row.original});
                            setEditingProductId(row.original.id);
                        }}
                    >
                        Редактировать
                    </button>
                ),
        },
    ], [editingProductId, categories]);

    const table = useReactTable({
        data: tableProducts,
        columns,
        getCoreRowModel: getCoreRowModel(),
    });


    if (isLoading) {
        return <p>Загрузка товаров...</p>;
    }

    if (isError) {
        return <p>Не удалось загрузить товары.</p>;
    }

    return (
        <div className="products-page">
            <h1>Управление товарами</h1>

            <div className="products-table-wrapper">
                <table className="products-table">
                    <thead>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <tr key={headerGroup.id}>
                            {headerGroup.headers.map((header) => (
                                <th key={header.id}>
                                    {flexRender(
                                        header.column.columnDef.header,
                                        header.getContext()
                                    )}
                                </th>
                            ))}
                        </tr>
                    ))}
                    </thead>

                    <tbody>
                    {table.getRowModel().rows.map((row) => (
                        <tr key={row.id}>
                            {row.getVisibleCells().map((cell) => (
                                <td key={cell.id}>
                                    {flexRender(
                                        cell.column.columnDef.cell,
                                        cell.getContext()
                                    )}
                                </td>
                            ))}
                        </tr>
                    ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
