// Сервис для взаимодействия с API

import axios from "axios";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "";

const api = axios.create({
  baseURL: `${API_BASE_URL}/api`,
  timeout: 10000,
  withCredentials: true,
});

async function request(endpoint, params = {}) {
  const response = await api.get(endpoint, { params });
  return response.data;
}

// -- Movies --

export const getTrendingMovies = () =>
  request("/movies/trending");

export const getPopularMovies = () =>
  request("/movies/popular");

export const getTopRatedMovies = () =>
  request("/movies/top-rated");

export const getTrendingMoviesWeek = () =>
  request("/movies/trending-week");

export const getNewMovies = () =>
  request("/movies/new");

export const getNowPlayingMovies = () =>
  request("/movies/now-playing");

export const getUpcomingMovies = () =>
  request("/movies/upcoming");

export const getMovie = (id) =>
  request(`/movies/${id}`);

export const getMovieCollection = (id) =>
  request(`/movie-collections/${id}`);

export const getDiscoverMovies = (params = {}) =>
  request("/discover", { ...params, type: "movie" });

export const getSearch = (query, params = {}) =>
  request("/search", { ...params, q: query });

export const getMultiSearch = (query, params = {}) =>
  request("/search", { ...params, q: query, type: "all" });

export const getPeopleSearch = (query, params = {}) =>
  request("/search", { ...params, q: query, type: "person" });

export const getMovieGenres = () =>
  request("/genres");

// -- TV Shows --

export const getTrendingTv = () =>
  request("/tv-shows/trending");

export const getPopularTv = () =>
  request("/tv-shows/popular");

export const getTopRatedTv = () =>
  request("/tv-shows/top-rated");

export const getTrendingTvWeek = () =>
  request("/tv-shows/trending-week");

export const getNewTv = () =>
  request("/tv-shows/new");

export const getOnTheAirTv = () =>
  request("/tv-shows/on-the-air");

export const getAiringTodayTv = () =>
  request("/tv-shows/airing-today");

export const getTvShow = (id) =>
  request(`/tv-shows/${id}`);

export const getTvSeason = (seriesId, seasonNumber) =>
  request(`/tv-shows/${seriesId}/season/${seasonNumber}`);

export const getDiscoverTv = (params = {}) =>
  request("/discover", { ...params, type: "tv" });

export const getTvGenres = () =>
  request("/genres", { type: "tv" });

// -- Authentication --

async function authenticatedWrite(method, endpoint, data = {}) {
  const { data: csrf } = await api.get("/auth/csrf");
  const response = await api.request({
    method,
    url: endpoint,
    data,
    headers: { "X-CSRF-Token": csrf.csrf_token },
  });
  return response.data;
}

export const getCurrentUser = async () => {
  const response = await api.get("/auth/session");
  return response.data.user;
};

export const register = (data) => authenticatedWrite("post", "/auth/register", data);

export const login = (data) => authenticatedWrite("post", "/auth/login", data);

export const logout = () => authenticatedWrite("post", "/auth/logout");

export const getFavorites = async () => (await api.get("/me/favorites")).data.results;

export const getVisitHistory = async () => (await api.get("/me/history")).data.results;

export const getMediaByType = (mediaType, sourceId) => {
  if (mediaType === "movie") return getMovie(sourceId);
  if (mediaType === "tv") return getTvShow(sourceId);
  return Promise.reject(new Error("Unsupported media type"));
};

export const recordMediaVisit = (mediaType, sourceId) =>
  authenticatedWrite("post", `/me/history/${mediaType}/${sourceId}`);

export const clearVisitHistory = () => authenticatedWrite("delete", "/me/history");

export const getFavoriteStatus = async (mediaType, sourceId) =>
  (await api.get(`/me/favorites/${mediaType}/${sourceId}`)).data.is_favorite;

export const setFavorite = async (mediaType, sourceId, isFavorite) =>
  (await authenticatedWrite(isFavorite ? "post" : "delete", `/me/favorites/${mediaType}/${sourceId}`)).is_favorite;
