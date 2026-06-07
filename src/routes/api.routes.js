const router = require('express').Router();
const c = require('../controllers/media.controller');
const stream = require('../controllers/stream.controller');

// Stream endpoints (video proxy)
router.get('/stream/info/:imdbId', stream.getStreamInfo);
router.get('/stream/proxy/:imdbId', stream.proxyStream);

router.get('/movies/popular', c.moviesPopular);
router.get('/movies/search', c.moviesSearch);
router.get('/movies/:id', c.moviesDetails);

router.get('/series/popular', c.seriesPopular);
router.get('/series/search', c.seriesSearch);
router.get('/series/:id', c.seriesDetails);

router.get('/anime/popular', c.animePopular);
router.get('/anime/search', c.animeSearch);
router.get('/anime/trending', c.animeTrending);

router.get('/home', c.getHome);

module.exports = router;
