const NodeCache = require('node-cache');
// Cache for 10 minutes to optimize mobile loading
module.exports = new NodeCache({ stdTTL: 600 });
