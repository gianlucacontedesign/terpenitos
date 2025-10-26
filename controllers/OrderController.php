<?php
require_once 'config/config.php';
require_once 'models/Order.php';
require_once 'models/Product.php';
require_once 'models/Address.php';

class OrderController {
    
    public function create() {
        header('Content-Type: application/json');
        
        if(!isLoggedIn() || $_SESSION['is_admin']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Verificar token CSRF
        if(!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }
        
        // Validar datos requeridos
        if(!isset($data['cart_items']) || !isset($data['shipping_address']) || !isset($data['phone'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos de pedido incompletos']);
            return;
        }
        
        $cartItems = $data['cart_items'];
        if(empty($cartItems)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
            return;
        }
        
        // Validar y preparar items del carrito
        $orderItems = [];
        $total = 0;
        $product = new Product();
        
        foreach($cartItems as $item) {
            if(!isset($item['product_id']) || !isset($item['quantity'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Datos de item inválidos']);
                return;
            }
            
            if($product->getById($item['product_id'])) {
                if($product->stock < $item['quantity']) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Stock insuficiente para el producto: {$product->name}"
                    ]);
                    return;
                }
                
                $itemTotal = $product->price * $item['quantity'];
                $total += $itemTotal;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $product->price
                ];
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                return;
            }
        }
        
        // Crear el pedido
        $order = new Order();
        $order->user_id = $_SESSION['user_id'];
        $order->status = 'Procesando';
        $order->total = $total;
        $order->shipping_address = cleanInput($data['shipping_address']);
        $order->phone = cleanInput($data['phone']);
        $order->notes = isset($data['notes']) ? cleanInput($data['notes']) : '';
        
        if($order->create($orderItems)) {
            echo json_encode([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'order_id' => $order->id,
                'total' => $total
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear el pedido o stock insuficiente']);
        }
    }
    
    public function getUserOrders() {
        header('Content-Type: application/json');
        
        if(!isLoggedIn() || $_SESSION['is_admin']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        
        $order = new Order();
        $orders = $order->getByUser($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'orders' => $orders
        ]);
    }
    
    public function getAllOrders() {
        header('Content-Type: application/json');
        
        if(!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            return;
        }
        
        $order = new Order();
        $orders = $order->getAll();
        
        echo json_encode([
            'success' => true,
            'orders' => $orders
        ]);
    }
    
    public function getOrderDetails() {
        header('Content-Type: application/json');
        
        if(!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        
        if(!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de pedido requerido']);
            return;
        }
        
        $order = new Order();
        $orderData = $order->getById($_GET['id']);
        
        if(!$orderData) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
            return;
        }
        
        // Verificar que el usuario puede ver este pedido
        if(!$_SESSION['is_admin'] && $orderData['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            return;
        }
        
        $orderItems = $order->getOrderItems($_GET['id']);
        
        echo json_encode([
            'success' => true,
            'order' => $orderData,
            'items' => $orderItems
        ]);
    }
    
    public function updateStatus() {
        header('Content-Type: application/json');
        
        if(!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            return;
        }
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Verificar token CSRF
        if(!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }
        
        if(!isset($data['order_id']) || !isset($data['status'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de pedido y estado son requeridos']);
            return;
        }
        
        $allowedStatuses = ['Procesando', 'Enviado', 'Entregado', 'Cancelado'];
        if(!in_array($data['status'], $allowedStatuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Estado inválido']);
            return;
        }
        
        $order = new Order();
        $order->id = $data['order_id'];
        
        if($order->updateStatus($data['status'])) {
            echo json_encode(['success' => true, 'message' => 'Estado del pedido actualizado exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado del pedido']);
        }
    }
    
    public function getStats() {
        header('Content-Type: application/json');
        
        if(!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            return;
        }
        
        $order = new Order();
        $stats = $order->getStats();
        
        // Obtener estadísticas adicionales de productos
        $product = new Product();
        $products = $product->getAll();
        
        $totalCapital = 0;
        $totalStock = 0;
        $totalCost = 0;
        
        foreach($products as $prod) {
            $totalCapital += $prod['price'] * $prod['stock'];
            $totalStock += $prod['stock'];
            $totalCost += ($prod['cost'] ?? 0) * $prod['stock'];
        }
        
        $estimatedProfit = $stats['total_revenue'] - $totalCost;
        
        echo json_encode([
            'success' => true,
            'stats' => array_merge($stats, [
                'total_capital' => $totalCapital,
                'total_stock' => $totalStock,
                'estimated_profit' => $estimatedProfit
            ])
        ]);
    }
}
?>