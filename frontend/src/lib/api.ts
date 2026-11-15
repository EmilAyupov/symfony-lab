import axios from 'axios';
import type { AuthResponse } from '../types/auth';
import { tokenStorage } from '../services/tokenStorage';

declare module 'axios' {
    interface InternalAxiosRequestConfig {
        _retry?: boolean;
    }
}

export const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
    },
});

// Добавляем access token к каждому запросу
api.interceptors.request.use((config) => {
    const accessToken = tokenStorage.getAccessToken();

    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`;
    }

    return config;
});

// Обрабатываем истёкший access token
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        if (
            error.response?.status !== 401 ||
            !originalRequest ||
            originalRequest._retry ||
            originalRequest.url === '/token/refresh'
        ) {
            return Promise.reject(error);
        }

        const refreshToken = tokenStorage.getRefreshToken();

        if (!refreshToken) {
            tokenStorage.clearTokens();
            return Promise.reject(error);
        }

        originalRequest._retry = true;

        try {
            const response = await axios.post<AuthResponse>(
                '/api/token/refresh',
                { refresh_token: refreshToken }
            );

            const { token, refresh_token } = response.data;

            tokenStorage.setTokens(token, refresh_token);

            originalRequest.headers.Authorization = `Bearer ${token}`;

            return api(originalRequest);
        } catch (refreshError) {
            tokenStorage.clearTokens();
            return Promise.reject(refreshError);
        }
    }
);
