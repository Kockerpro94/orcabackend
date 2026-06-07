const normalizeTmdb = (item, type) => ({
  id: String(item.id),
  title: item.title || item.name,
  image: item.poster_path ? `https://image.tmdb.org/t/p/w500${item.poster_path}` : '',
  type: type,
  rating: String(item.vote_average || 0),
  source: 'tmdb',
  description: item.overview || '',
  episodes: type === 'series' ? 'N/A' : null
});

const normalizeJikan = (item) => ({
  id: String(item.mal_id),
  title: item.title_english || item.title,
  image: item.images?.jpg?.large_image_url || '',
  type: 'anime',
  rating: String(item.score || 0),
  source: 'jikan',
  description: item.synopsis || '',
  episodes: String(item.episodes || '?')
});

module.exports = { normalizeTmdb, normalizeJikan };
