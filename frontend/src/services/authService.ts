import type { LoginCredentials, AuthResponse } from '../types/auth';
import { api } from '../lib/api';
import type { User } from '../types/user';

export const authService = {
    async login(credentials: LoginCredentials): Promise<AuthResponse> {
        const response = await api.post<AuthResponse>('/login', credentials);

        return response.data;
    },
    async getCurrentUser(): Promise<User> {
        const response = await api.get<User>('/me');
        return response.data;
    },
    async refreshToken(refreshToken: string): Promise<AuthResponse> {
        const response = await api.post<AuthResponse>('/token/refresh', {
            refresh_token: refreshToken,
        });

        return response.data;
    },
};
