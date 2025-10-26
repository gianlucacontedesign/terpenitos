const express = require('express');
const router = express.Router();
const { getUserProfile, updateUserProfile, updateUserPassword, getUserAddresses, addAddress, updateAddress, deleteAddress } = require('../controllers/userController');
const { protect } = require('../middleware/authMiddleware');
router.route('/profile').get(protect, getUserProfile).put(protect, updateUserProfile);
router.put('/password', protect, updateUserPassword);
router.route('/addresses').get(protect, getUserAddresses).post(protect, addAddress);
router.route('/addresses/:id').put(protect, updateAddress).delete(protect, deleteAddress);
module.exports = router;
