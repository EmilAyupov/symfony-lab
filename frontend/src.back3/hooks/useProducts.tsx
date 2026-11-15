import { useQuery } from '@tanstack/react-query';
import { productService } from '../services/productService';

export function useProducts(categoryId: number | null) {
    return useQuery({
        queryKey: ['products', categoryId],
        queryFn: () => {
            return categoryId === null
                ? productService.getAll()
                : productService.getByCategory(categoryId);
        },
    });
}
