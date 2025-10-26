const Product = require('../models/Product');
const Category = require('../models/Category');
const Coupon = require('../models/Coupon');

// @desc    Obtener todos los datos públicos para la tienda
// @route   GET /api/data/all-data
exports.getInitialData = async (req, res) => {
    try {
        const products = await Product.find({});
        const categories = await Category.find({});
        const coupons = await Coupon.find({});
        res.json({ products, categories, coupons });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: 'Error del servidor al obtener datos' });
    }
};
