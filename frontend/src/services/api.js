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
  request("/trending");

export const getPopularMovies = () =>
  request("/movies");

export const getNowPlayingMovies = () =>
  request("/now-playing");

export const getUpcomingMovies = () =>
  request("/upcoming");

export const getMovie = (id) =>
  request("/movie", { id });

export const getDiscoverMovies = (params = {}) =>
  request("/discover", params);

export const getMovieGenres = () =>
  request("/genres");

// -- TV Shows --

export const getTrendingTv = () =>
  request("/tv/trending");

export const getPopularTv = () =>
  request("/tv/popular");

export const getOnTheAirTv = () =>
  request("/tv/on-the-air");

export const getAiringTodayTv = () =>
  request("/tv/airing-today");

export const getTvShow = (id) =>
  request("/tv-shows", { id });

export const getTvSeason = (seriesId, seasonNumber) =>
  request("/tv-shows/season", { series_id: seriesId, season_number: seasonNumber });

export const getDiscoverTv = (params = {}) =>
  request("/tv/discover", params);

export const getTvGenres = () =>
  request("/tv/genres");