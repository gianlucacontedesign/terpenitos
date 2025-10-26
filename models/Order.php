<?php
require_once 'config/database.php';

class Order {
    private $conn;
    private $table_name = "orders";
    private $items_table = "order_items";
    
    public $id;
    public $user_id;
    public $status;
    public $total;
    public $shipping_address;
    public $phone;
    public $notes;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Crear pedido
    public function create($cartItems) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();
            
            // Crear el pedido
            $query = "INSERT INTO " . $this->table_name . "
                    (user_id, status, total, shipping_address, phone, notes, created_at, updated_at)
                    VALUES (:user_id, :status, :total, :shipping_address, :phone, :notes, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($query);
            
            // Limpiar datos
            $this->shipping_address = htmlspecialchars(strip_tags($this->shipping_address));
            $this->phone = htmlspecialchars(strip_tags($this->phone));
            $this->notes = htmlspecialchars(strip_tags($this->notes));
            
            // Vincular valores
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':total', $this->total);
            $stmt->bindParam(':shipping_address', $this->shipping_address);
            $stmt->bindParam(':phone', $this->phone);
            $stmt->bindParam(':notes', $this->notes);
            
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Crear los items del pedido
                $itemQuery = "INSERT INTO " . $this->items_table . "
                            (order_id, product_id, quantity, price, product_name)
                            VALUES (:order_id, :product_id, :quantity, :price, :product_name)";
                
                $itemStmt = $this->conn->prepare($itemQuery);
                
                foreach($cartItems as $item) {
                    $itemStmt->bindParam(':order_id', $this->id);
                    $itemStmt->bindParam(':product_id', $item['product_id']);
                    $itemStmt->bindParam(':quantity', $item['quantity']);
                    $itemStmt->bindParam(':price', $item['price']);
                    $itemStmt->bindParam(':product_name', $item['product_name']);
                    
                    if(!$itemStmt->execute()) {
                        $this->conn->rollBack();
                        return false;
                    }
                    
                    // Actualizar stock del producto
                    $updateStock = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id AND stock >= :quantity";
                    $stockStmt = $this->conn->prepare($updateStock);
                    $stockStmt->bindParam(':quantity', $item['quantity']);
                    $stockStmt->bindParam(':product_id', $item['product_id']);
                    
                    if(!$stockStmt->execute() || $stockStmt->rowCount() == 0) {
                        $this->conn->rollBack();
                        return false; // Stock insuficiente
                    }
                }
                
                $this->conn->commit();
                return true;
            }
            
            $this->conn->rollBack();
            return false;
            
        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    // Obtener pedidos por usuario
    public function getByUser($user_id) {
        $query = "SELECT o.*, u.name as user_name 
                FROM " . $this->table_name . " o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.user_id = :user_id
                ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener todos los pedidos (admin)
    public function getAll() {
        $query = "SELECT o.*, u.name as user_name, u.email as user_email 
                FROM " . $this->table_name . " o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener pedido por ID
    public function getById($id) {
        $query = "SELECT o.*, u.name as user_name, u.email as user_email 
                FROM " . $this->table_name . " o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->status = $row['status'];
            $this->total = $row['total'];
            $this->shipping_address = $row['shipping_address'];
            $this->phone = $row['phone'];
            $this->notes = $row['notes'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return $row;
        }
        return false;
    }
    
    // Obtener items de un pedido
    public function getOrderItems($order_id) {
        $query = "SELECT oi.*, p.image as product_image 
                FROM " . $this->items_table . " oi
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Actualizar estado del pedido
    public function updateStatus($status) {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status, updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Obtener estadísticas
    public function getStats() {
        $stats = [];
        
        // Total de pedidos
        $query = "SELECT COUNT(*) as total_orders FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];
        
        // Pedidos pendientes
        $query = "SELECT COUNT(*) as pending_orders FROM " . $this->table_name . " WHERE status = 'Procesando'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];
        
        // Pedidos entregados
        $query = "SELECT COUNT(*) as shipped_orders FROM " . $this->table_name . " WHERE status = 'Entregado'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['shipped_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['shipped_orders'];
        
        // Ingresos totales
        $query = "SELECT COALESCE(SUM(total), 0) as total_revenue FROM " . $this->table_name . " WHERE status = 'Entregado'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
        
        return $stats;
    }
}
?>