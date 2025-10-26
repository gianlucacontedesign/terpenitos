const express = require('express');
const router = express.Router();
const { getInitialData } = require('../controllers/datacontroller.js');
router.get('/all-data', getInitialData);
module.exports = router;
