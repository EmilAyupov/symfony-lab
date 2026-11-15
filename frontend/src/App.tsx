import { Link, Route, Routes } from 'react-router';
import { HomePage } from './pages/HomePage';
import { ProductsPage } from './pages/ProductsPage';
import { LoginPage } from './pages/LoginPage';
import { ProtectedRoute } from './components/ProtectedRoute';
import { useAuth } from './context/AuthContext';
import './App.css';
import { ProductsTablePage } from './pages/ProductsTablePage';

function App() {
    const { user, isAuthenticated, isLoading } = useAuth();

    return (
        <main>
            <nav>
                <Link to="/">Главная</Link>
                {' | '}
                <Link to="/products">Продукты</Link>
                {' | '}

                {isLoading ? (
                    <span>Проверка авторизации...</span>
                ) : isAuthenticated ? (
                    <span>{user?.email}</span>
                ) : (
                    <Link to="/login">Вход</Link>
                )}
            </nav>

            <Routes>
                <Route path="/" element={<HomePage />} />
                <Route path="/login" element={<LoginPage />} />

                <Route element={<ProtectedRoute />}>
                    <Route path="/products" element={<ProductsPage />} />
                    <Route path="/products-table" element={<ProductsTablePage />} />
                </Route>
            </Routes>
        </main>
    );
}

export default App;
