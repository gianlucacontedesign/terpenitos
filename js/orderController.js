const Order = require('../models/Order');
const Product = require('../models/Product');

exports.createOrder = async (req, res) => {
    const { orderItems, shippingAddress, subtotal, discount, couponUsed, total } = req.body;
    if (!orderItems || orderItems.length === 0) return res.status(400).json({ message: 'No hay artículos en el pedido' });
    
    try {
        const order = new Order({
            user: req.user._id, orderItems, shippingAddress,
            subtotal, discount, couponUsed, total
        });
        const createdOrder = await order.save();

        // Actualizar stock
        for (const item of orderItems) {
            await Product.findByIdAndUpdate(item.product, { $inc: { stock: -item.quantity } });
        }
        res.status(201).json(createdOrder);
    } catch (error) { res.status(500).json({ message: 'Error al crear el pedido', error: error.message }); }
};

exports.getUserOrders = async (req, res) => {
    try {
        const orders = await Order.find({ user: req.user._id }).sort({ createdAt: -1 });
        res.json(orders);
    } catch (error) { res.status(500).json({ message: 'Error al obtener los pedidos' }); }
};
