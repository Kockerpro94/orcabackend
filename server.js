const express = require('express');
const cors = require('cors');
const apiRoutes = require('./src/routes/api.routes');
const { PORT } = require('./src/config/env');

const app = express();
app.use(cors());
app.use(express.json());

// Proxy all requests through our unified router
app.use('/', apiRoutes);

// Error Handling Middleware
app.use((err, req, res, next) => {
  console.error('API Error:', err.message);
  res.status(500).json({ error: 'Internal Server Error' });
});

app.listen(PORT, "0.0.0.0", () => {
  console.log(`Netflix Backend Proxy running on port ${PORT}`);
});
