const API_BASE = "http://138.124.240.208:8000/api";

export async function getTrendingMovies() {
    const response = await fetch(`${API_BASE}/trending.php`);

    if (!response.ok) {
        throw new Error("Ошибка загрузки фильмов");
    }

    return await response.json();
}

export async function getMovie(id) {
    const response = await fetch(
        `${API_BASE}/movie.php?id=${id}`
    );

    if (!response.ok) {
        throw new Error("Ошибка загрузки фильма");
    }

    return await response.json();
}