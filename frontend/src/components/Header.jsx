export default function Header() {
    return (
        <header className="header">
            <input
                className="search"
                placeholder="Поиск фильмов и сериалов..."
            />

            <div className="header-actions">
                🔔
                👤
            </div>
        </header>
    );
}