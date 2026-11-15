import { Link, Route, Routes } from 'react-router';
import { HomePage } from './pages/HomePage';
import { ProductsPage } from './pages/ProductsPage';
import { LoginPage } from './pages/LoginPage';
import './App.css';

function App() {
    return (
        <main>
            <nav>
                <Link to="/">Главная</Link>
                {' | '}
                <Link to="/products">Продукты</Link>
                {' | '}
                <Link to="/login">Вход</Link>
            </nav>

            <Routes>
                <Route path="/" element={<HomePage />} />
                <Route path="/products" element={<ProductsPage />} />
                <Route path="/login" element={<LoginPage />} />
            </Routes>
        </main>
    );
}

export default App;
