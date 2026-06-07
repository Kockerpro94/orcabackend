const axios = require('axios');

/**
 * STREAM CONTROLLER
 * 
 * The backend acts purely as a NETWORK PROXY.
 * It never saves the video file to disk.
 * It pipes the video bytes from the external source directly into the HTTP response.
 * The mobile app receives a clean, resumable byte stream.
 */

// Primary and fallback stream sources (by IMDB ID)
const STREAM_SOURCES = [
  (imdbId) => `https://vidsrc.to/embed/movie/${imdbId}`,
  (imdbId) => `https://vidsrc.me/embed/movie?imdb=${imdbId}`,
];

/**
 * Resolves the actual direct video URL from a vidsrc embed page.
 * We extract the direct m3u8 or mp4 link from the source HTML.
 */
const resolveStreamUrl = async (imdbId) => {
  for (const sourceFn of STREAM_SOURCES) {
    try {
      const embedUrl = sourceFn(imdbId);
      const { data: html } = await axios.get(embedUrl, {
        headers: {
          'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
          'Referer': 'https://vidsrc.to',
        },
        timeout: 10000,
      });

      // Try to extract direct mp4 or m3u8 stream URL from embed HTML
      const mp4Match = html.match(/https?:\/\/[^\s"'<>]+\.mp4[^\s"'<>]*/i);
      const m3u8Match = html.match(/https?:\/\/[^\s"'<>]+\.m3u8[^\s"'<>]*/i);

      if (mp4Match) return { url: mp4Match[0], type: 'mp4' };
      if (m3u8Match) return { url: m3u8Match[0], type: 'm3u8' };
    } catch (_) {
      continue;
    }
  }
  return null;
};

/**
 * GET /stream/info/:imdbId
 * Returns the proxy stream URL and metadata for the Flutter app.
 * The Flutter app will then call /stream/proxy/:imdbId to get actual bytes.
 */
const getStreamInfo = async (req, res) => {
  const { imdbId } = req.params;

  if (!imdbId || !imdbId.startsWith('tt')) {
    return res.status(400).json({ error: 'Invalid IMDB ID. Must start with "tt".' });
  }

  const resolved = await resolveStreamUrl(imdbId);

  if (!resolved) {
    // Return a fallback public-domain test stream if source resolution fails
    return res.json({
      imdbId,
      streamType: 'mp4',
      proxyUrl: `${process.env.BASE_URL || 'http://localhost:3000'}/stream/proxy/${imdbId}`,
      directFallback: 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
      resolved: false,
    });
  }

  res.json({
    imdbId,
    streamType: resolved.type,
    proxyUrl: `${process.env.BASE_URL || 'http://localhost:3000'}/stream/proxy/${imdbId}`,
    directFallback: null,
    resolved: true,
  });
};

/**
 * GET /stream/proxy/:imdbId
 * 
 * THE CORE PROXY ENGINE.
 * 
 * The server fetches the video bytes from the external source and 
 * pipes them directly into the mobile app's HTTP response.
 * 
 * IMPORTANT: This uses Node.js streams (pipe) so the server NEVER 
 * downloads the full file into memory or disk. It just forwards bytes.
 * This is what "acting like the network" means.
 */
const proxyStream = async (req, res) => {
  const { imdbId } = req.params;

  if (!imdbId || !imdbId.startsWith('tt')) {
    return res.status(400).json({ error: 'Invalid IMDB ID.' });
  }

  try {
    const resolved = await resolveStreamUrl(imdbId);

    // If resolution fails, serve Big Buck Bunny as a public domain fallback
    const videoUrl = resolved?.url ||
      'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';

    // Handle HTTP Range requests (required for video seeking on mobile)
    const rangeHeader = req.headers['range'];
    const headers = {
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
      'Referer': 'https://vidsrc.to',
    };
    if (rangeHeader) headers['Range'] = rangeHeader;

    // Stream the video: axios fetches it as a Node.js ReadableStream
    const upstreamResponse = await axios.get(videoUrl, {
      responseType: 'stream',
      headers,
      timeout: 30000,
    });

    // Forward all content headers from the upstream source to the mobile client
    res.setHeader('Content-Type', upstreamResponse.headers['content-type'] || 'video/mp4');
    res.setHeader('Accept-Ranges', 'bytes');

    if (upstreamResponse.headers['content-length']) {
      res.setHeader('Content-Length', upstreamResponse.headers['content-length']);
    }
    if (upstreamResponse.headers['content-range']) {
      res.setHeader('Content-Range', upstreamResponse.headers['content-range']);
    }

    // Set the correct HTTP status (206 for range requests, 200 for full stream)
    res.status(upstreamResponse.status);

    // PIPE: Forward bytes from upstream → mobile app, zero disk usage
    upstreamResponse.data.pipe(res);

    // Handle client disconnects gracefully
    req.on('close', () => {
      upstreamResponse.data.destroy();
    });

  } catch (err) {
    console.error('[STREAM ERROR]', err.message);
    if (!res.headersSent) {
      res.status(502).json({ error: 'Failed to proxy stream. Source may be unavailable.' });
    }
  }
};

module.exports = { getStreamInfo, proxyStream };
