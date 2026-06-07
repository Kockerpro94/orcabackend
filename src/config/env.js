require('dotenv').config();

module.exports = {
  PORT: process.env.PORT || 3000,
  BASE_URL: process.env.BASE_URL || 'http://localhost:3000',
  TMDB_KEY: process.env.TMDB_KEY || '3e974fca',
  TMDB_BASE: 'https://api.themoviedb.org/3',
  JIKAN_BASE: 'https://api.jikan.moe/v4'
};
