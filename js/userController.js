const User = require('../models/User');

exports.getUserProfile = async (req, res) => {
    const user = await User.findById(req.user._id);
    if (user) res.json({ _id: user._id, name: user.name, email: user.email, phone: user.phone });
    else res.status(404).json({ message: 'Usuario no encontrado' });
};

exports.updateUserProfile = async (req, res) => {
    const user = await User.findById(req.user._id);
    if (user) {
        user.name = req.body.name || user.name;
        user.phone = req.body.phone || user.phone;
        const updatedUser = await user.save();
        res.json({ _id: updatedUser._id, name: updatedUser.name, email: updatedUser.email, phone: updatedUser.phone });
    } else res.status(404).json({ message: 'Usuario no encontrado' });
};

exports.updateUserPassword = async (req, res) => {
    const { currentPassword, newPassword } = req.body;
    const user = await User.findById(req.user._id);
    if (user && (await user.matchPassword(currentPassword))) {
        user.password = newPassword;
        await user.save();
        res.json({ message: 'Contraseña actualizada' });
    } else res.status(401).json({ message: 'Contraseña actual incorrecta' });
};

exports.getUserAddresses = async (req, res) => {
    const user = await User.findById(req.user._id);
    if (user) res.json(user.addresses);
    else res.status(404).json({ message: 'Usuario no encontrado' });
};

exports.addAddress = async (req, res) => {
    const { alias, line1, city, zip } = req.body;
    const user = await User.findById(req.user._id);
    if (user) {
        user.addresses.push({ alias, line1, city, zip });
        await user.save();
        res.status(201).json(user.addresses);
    } else res.status(404).json({ message: 'Usuario no encontrado' });
};

exports.updateAddress = async (req, res) => {
    const user = await User.findById(req.user._id);
    if (user) {
        const address = user.addresses.id(req.params.id);
        if (address) {
            address.set(req.body);
            await user.save();
            res.json(user.addresses);
        } else res.status(404).json({ message: 'Dirección no encontrada' });
    } else res.status(404).json({ message: 'Usuario no encontrado' });
};

exports.deleteAddress = async (req, res) => {
    const user = await User.findById(req.user._id);
    if (user) {
        user.addresses.pull({ _id: req.params.id });
        await user.save();
        res.json({ message: 'Dirección eliminada' });
    } else res.status(404).json({ message: 'Usuario no encontrado' });
};
