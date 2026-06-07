const axios = require('axios');
const { TMDB_BASE, TMDB_KEY } = require('../config/env');
const { normalizeTmdb } = require('../utils/normalizer');

const fetchTMDb = async (endpoint, type) => {
  const { data } = await axios.get(`${TMDB_BASE}${endpoint}&api_key=${TMDB_KEY}`);
  if (data.results) return data.results.map(i => normalizeTmdb(i, type));
  return normalizeTmdb(data, type);
};

module.exports = {
  getMovies: () => fetchTMDb('/movie/popular?', 'movie'),
  searchMovies: (query) => fetchTMDb(`/search/movie?query=${encodeURIComponent(query)}`, 'movie'),
  getMovieById: (id) => fetchTMDb(`/movie/${id}?`, 'movie'),

  getSeries: () => fetchTMDb('/tv/popular?', 'series'),
  searchSeries: (query) => fetchTMDb(`/search/tv?query=${encodeURIComponent(query)}`, 'series'),
  getSeriesById: (id) => fetchTMDb(`/tv/${id}?`, 'series')
};
