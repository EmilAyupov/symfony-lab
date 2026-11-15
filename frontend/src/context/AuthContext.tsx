import {
    createContext,
    useContext,
    useEffect,
    useState,
    type ReactNode,
} from 'react';

import type { User } from '../types/user';
import { authService } from '../services/authService';
import { tokenStorage } from '../services/tokenStorage';

interface AuthContextType {
    user: User | null;
    isAuthenticated: boolean;
    isLoading: boolean;
    setUser: (user: User | null) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
    const [user, setUser] = useState<User | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        async function loadUser() {
            const token = tokenStorage.getAccessToken();

            if (!token) {
                setIsLoading(false);
                return;
            }

            try {
                const currentUser = await authService.getCurrentUser();
                setUser(currentUser);
            } catch (error) {
                console.error('Не удалось загрузить пользователя:', error);
                tokenStorage.clearTokens();
                setUser(null);
            } finally {
                setIsLoading(false);
            }
        }

        loadUser();
    }, []);

    return (
        <AuthContext.Provider
            value={{
                user,
                isAuthenticated: user !== null,
                isLoading,
                setUser,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth должен использоваться внутри AuthProvider');
    }

    return context;
}
