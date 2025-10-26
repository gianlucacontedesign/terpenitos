<?php
require_once 'config/database.php';

class Product {
    private $conn;
    private $table_name = "products";
    
    public $id;
    public $name;
    public $description;
    public $category_id;
    public $category_name;
    public $price;
    public $cost;
    public $stock;
    public $image;
    public $is_featured;
    public $is_active;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Crear producto
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (name, description, category_id, price, cost, stock, image, is_featured, created_at, updated_at)
                VALUES (:name, :description, :category_id, :price, :cost, :stock, :image, :is_featured, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->image = htmlspecialchars(strip_tags($this->image));
        
        // Vincular valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':cost', $this->cost);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':is_featured', $this->is_featured, PDO::PARAM_BOOL);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    // Obtener todos los productos activos
    public function getAll() {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 AND c.is_active = 1
                ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener productos destacados activos
    public function getFeatured() {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_featured = 1 AND p.is_active = 1 AND c.is_active = 1
                ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener productos activos por categoría
    public function getByCategory($category_id) {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.category_id = :category_id AND p.is_active = 1 AND c.is_active = 1
                ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener producto por ID (solo activos)
    public function getById($id) {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = :id AND p.is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->category_id = $row['category_id'];
            $this->category_name = $row['category_name'];
            $this->price = $row['price'];
            $this->cost = $row['cost'];
            $this->stock = $row['stock'];
            $this->image = $row['image'];
            $this->is_featured = $row['is_featured'];
            $this->is_active = $row['is_active'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
    
    // Actualizar producto
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET name = :name, description = :description, category_id = :category_id,
                    price = :price, cost = :cost, stock = :stock, image = :image,
                    is_featured = :is_featured, updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->image = htmlspecialchars(strip_tags($this->image));
        
        // Vincular valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':cost', $this->cost);
        $stmt->bindParam(':stock', $this->stock);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':is_featured', $this->is_featured, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Eliminar producto (Soft Delete - marca como inactivo)
    public function delete() {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Actualizar stock
    public function updateStock($quantity) {
        $query = "UPDATE " . $this->table_name . " SET stock = stock - :quantity WHERE id = :id AND stock >= :quantity";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
    
    // Buscar productos activos
    public function search($searchTerm) {
        $query = "SELECT p.*, c.name as category_name 
                FROM " . $this->table_name . " p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE (p.name LIKE :search OR p.description LIKE :search)
                AND p.is_active = 1 AND c.is_active = 1
                ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $searchTerm . "%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>