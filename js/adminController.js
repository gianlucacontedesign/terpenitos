const Product = require('../models/Product');
const Category = require('../models/Category');
const Order = require('../models/Order');
const User = require('../models/User');

// @desc    Obtener estadísticas para el dashboard
exports.getDashboardStats = async (req, res) => {
    try {
        const totalProducts = await Product.countDocuments();
        const totalCategories = await Category.countDocuments();
        const totalOrders = await Order.countDocuments();
        const totalUsers = await User.countDocuments({ isAdmin: false });
        res.json({ totalProducts, totalCategories, totalOrders, totalUsers });
    } catch (error) {
        res.status(500).json({ message: 'Error al obtener estadísticas' });
    }
};

// --- PRODUCTOS ---
exports.getAllProducts = async (req, res) => {
    try {
        const products = await Product.find({}).sort({ createdAt: -1 });
        res.json(products);
    } catch (error) { res.status(500).json({ message: 'Error al obtener productos' }); }
};

exports.createProduct = async (req, res) => {
    try {
        const newProduct = new Product(req.body);
        const savedProduct = await newProduct.save();
        res.status(201).json(savedProduct);
    } catch (error) { res.status(400).json({ message: 'Error al crear el producto', error: error.message }); }
};

exports.updateProduct = async (req, res) => {
    try {
        const updatedProduct = await Product.findByIdAndUpdate(req.params.id, { $set: req.body }, { new: true });
        if (!updatedProduct) return res.status(404).json({ message: 'Producto no encontrado' });
        res.json(updatedProduct);
    } catch (error) { res.status(400).json({ message: 'Error al actualizar el producto' }); }
};

exports.deleteProduct = async (req, res) => {
    try {
        const deletedProduct = await Product.findByIdAndDelete(req.params.id);
        if (!deletedProduct) return res.status(404).json({ message: 'Producto no encontrado' });
        res.json({ message: 'Producto eliminado con éxito' });
    } catch (error) { res.status(500).json({ message: 'Error al eliminar el producto' }); }
};

// --- CATEGORÍAS ---
exports.getAllCategories = async (req, res) => {
    try {
        const categories = await Category.find({}).sort({ name: 1 });
        res.json(categories);
    } catch (error) { res.status(500).json({ message: 'Error al obtener categorías' }); }
};

exports.createCategory = async (req, res) => {
    try {
        const newCategory = new Category(req.body);
        const savedCategory = await newCategory.save();
        res.status(201).json(savedCategory);
    } catch (error) { res.status(400).json({ message: 'Error al crear la categoría' }); }
};

exports.updateCategory = async (req, res) => {
    try {
        const updatedCategory = await Category.findByIdAndUpdate(req.params.id, { $set: req.body }, { new: true });
        if (!updatedCategory) return res.status(404).json({ message: 'Categoría no encontrada' });
        res.json(updatedCategory);
    } catch (error) { res.status(400).json({ message: 'Error al actualizar la categoría' }); }
};

exports.deleteCategory = async (req, res) => {
    try {
        const deletedCategory = await Category.findByIdAndDelete(req.params.id);
        if (!deletedCategory) return res.status(404).json({ message: 'Categoría no encontrada' });
        res.json({ message: 'Categoría eliminada con éxito' });
    } catch (error) { res.status(500).json({ message: 'Error al eliminar la categoría' }); }
};

// --- PEDIDOS ---
exports.getAllOrders = async (req, res) => {
    try {
        const orders = await Order.find({}).populate('user', 'name email').sort({ createdAt: -1 });
        res.json(orders);
    } catch (error) { res.status(500).json({ message: 'Error al obtener los pedidos' }); }
};

exports.updateOrderStatus = async (req, res) => {
    try {
        const order = await Order.findById(req.params.id);
        if (order) {
            order.status = req.body.status || order.status;
            const updatedOrder = await order.save();
            res.json(updatedOrder);
        } else {
            res.status(404).json({ message: 'Pedido no encontrado' });
        }
    } catch (error) { res.status(400).json({ message: 'Error al actualizar el estado del pedido' }); }
};
