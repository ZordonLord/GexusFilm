// Сервис для взаимодействия с API

const API_BASE = "http://138.124.240.208:8000/api";

async function request(endpoint) {
  const response = await fetch(
    `${API_BASE}/${endpoint}`
  );

  if (!response.ok) {
    throw new Error("API Error");
  }

  return response.json();
}

export const getTrendingMovies = () =>
  request("trending.php");

export const getPopularMovies = () =>
  request("movies.php");

export const getNowPlayingMovies = () =>
  request("now-playing.php");

export const getUpcomingMovies = () =>
  request("upcoming.php");

export const getMovie = (id) =>
  request(`movie.php?id=${id}`);