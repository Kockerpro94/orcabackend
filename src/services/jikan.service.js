const axios = require('axios');
const { JIKAN_BASE } = require('../config/env');
const { normalizeJikan } = require('../utils/normalizer');

const fetchJikan = async (endpoint) => {
  const { data } = await axios.get(`${JIKAN_BASE}${endpoint}`);
  if (Array.isArray(data.data)) return data.data.map(normalizeJikan);
  return normalizeJikan(data.data);
};

module.exports = {
  getPopularAnime: () => fetchJikan('/top/anime?filter=bypopularity'),
  searchAnime: (query) => fetchJikan(`/anime?q=${encodeURIComponent(query)}`),
  getTrendingAnime: () => fetchJikan('/top/anime?filter=airing')
};
