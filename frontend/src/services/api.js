// Сервис для взаимодействия с API

import axios from "axios";

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "";

const api = axios.create({
  baseURL: `${API_BASE_URL}/api`,
  timeout: 10000,
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

export const getNowPlayingMovies = () =>
  request("/movies/now-playing");

export const getUpcomingMovies = () =>
  request("/movies/upcoming");

export const getMovie = (id) =>
  request(`/movies/${id}`);

export const getDiscoverMovies = (params = {}) =>
  request("/discover", { ...params, type: "movie" });

export const getMovieGenres = () =>
  request("/genres");

// -- TV Shows --

export const getTrendingTv = () =>
  request("/tv-shows/trending");

export const getPopularTv = () =>
  request("/tv-shows/popular");

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