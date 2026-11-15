import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { loginSchema, type LoginFormData } from '../schemas/loginSchema';
import { authService } from '../services/authService';
import { tokenStorage } from '../services/tokenStorage';
import { useAuth } from '../context/AuthContext';

export function LoginPage() {
    const { setUser } = useAuth();

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<LoginFormData>({
        resolver: zodResolver(loginSchema),
    });

    async function onSubmit(data: LoginFormData) {
        try {
            const result = await authService.login(data);

            tokenStorage.setTokens(result.token, result.refresh_token);

            const user = await authService.getCurrentUser();

            setUser(user);

            console.log('Авторизация успешна:', user);
        } catch (error) {
            console.error('Ошибка авторизации:', error);
        }
    }

    return (
        <div>
            <h1>Вход в систему</h1>

            <form onSubmit={handleSubmit(onSubmit)}>
                <div>
                    <label htmlFor="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        placeholder="Введите email"
                        {...register('email')}
                    />
                    {errors.email && <p>{errors.email.message}</p>}
                </div>

                <div>
                    <label htmlFor="password">Пароль</label>
                    <input
                        id="password"
                        type="password"
                        placeholder="Введите пароль"
                        {...register('password')}
                    />
                    {errors.password && <p>{errors.password.message}</p>}
                </div>

                <button type="submit">Войти</button>
            </form>
        </div>
    );
}
