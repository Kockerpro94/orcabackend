const tmdb = require('../services/tmdb.service');
const jikan = require('../services/jikan.service');
const cache = require('../utils/cache');

// Higher order function to handle caching & errors automatically
const handleRequest = (cacheKeyPrefix, fetcher) => async (req, res, next) => {
  try {
    const key = `${cacheKeyPrefix}_${req.params.id || req.query.query || 'all'}`;
    if (cache.has(key)) return res.json(cache.get(key));
    
    const data = await fetcher(req);
    cache.set(key, data);
    res.json(data);
  } catch (err) {
    next(err);
  }
};

module.exports = {
  moviesPopular: handleRequest('mov_pop', () => tmdb.getMovies()),
  moviesSearch: handleRequest('mov_search', req => tmdb.searchMovies(req.query.query)),
  moviesDetails: handleRequest('mov_det', req => tmdb.getMovieById(req.params.id)),

  seriesPopular: handleRequest('ser_pop', () => tmdb.getSeries()),
  seriesSearch: handleRequest('ser_search', req => tmdb.searchSeries(req.query.query)),
  seriesDetails: handleRequest('ser_det', req => tmdb.getSeriesById(req.params.id)),

  animePopular: handleRequest('ani_pop', () => jikan.getPopularAnime()),
  animeSearch: handleRequest('ani_search', req => jikan.searchAnime(req.query.query)),
  animeTrending: handleRequest('ani_trend', () => jikan.getTrendingAnime()),

  getHome: handleRequest('home', async () => {
    // Parallel fetching for maximum speed
    const [movies, series, anime] = await Promise.all([
      tmdb.getMovies(),
      tmdb.getSeries(),
      jikan.getTrendingAnime()
    ]);
    return { trendingMovies: movies, popularSeries: series, topAnime: anime };
  })
};
