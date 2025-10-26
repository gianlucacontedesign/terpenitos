const express = require('express');
const router = express.Router();
const { protect, admin } = require('../middleware/authMiddleware');
const {
    getDashboardStats,
    getAllProducts,
    createProduct,
    updateProduct,
    deleteProduct,
    getAllCategories,
    createCategory,
    updateCategory,
    deleteCategory,
    getAllOrders,
    updateOrderStatus
} = require('../controllers/adminController');

router.use(protect, admin);
router.get('/stats', getDashboardStats);
router.route('/products').get(getAllProducts).post(createProduct);
router.route('/products/:id').put(updateProduct).delete(deleteProduct);
router.route('/categories').get(getAllCategories).post(createCategory);
router.route('/categories/:id').put(updateCategory).delete(deleteCategory);
router.get('/orders', getAllOrders);
router.put('/orders/:id/status', updateOrderStatus);

module.exports = router;
