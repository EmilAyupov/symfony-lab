export function LoginPage() {
    return (
        <div>
            <h1>Вход в систему</h1>

            <form>
                <div>
                    <label htmlFor="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        placeholder="Введите email"
                    />
                </div>

                <div>
                    <label htmlFor="password">Пароль</label>
                    <input
                        id="password"
                        type="password"
                        placeholder="Введите пароль"
                    />
                </div>

                <button type="submit">Войти</button>
            </form>
        </div>
    );
}
