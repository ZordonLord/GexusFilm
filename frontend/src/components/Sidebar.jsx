import "../styles/Sidebar.css";

export default function Sidebar() {
  return (
    <aside className="sidebar">
      <h2 className="logo">Gexus Film</h2>

      <nav>
        <a href="#">🏠 Главная</a>
        <a href="#">🎬 Фильмы</a>
        <a href="#">📺 Сериалы</a>
        <a href="#">📚 Каталог</a>
        <a href="#">🔍 Поиск</a>
        <a href="#">❤️ Избранное</a>
        <a href="#">🕒 История</a>
      </nav>
    </aside>
  );
}