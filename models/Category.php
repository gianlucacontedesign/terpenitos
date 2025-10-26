<?php
require_once 'config/database.php';

class Category {
    private $conn;
    private $table_name = "categories";
    
    public $id;
    public $name;
    public $image;
    public $is_active;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Crear categoría
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (name, image, created_at, updated_at)
                VALUES (:name, :image, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->image = htmlspecialchars(strip_tags($this->image));
        
        // Vincular valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':image', $this->image);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    // Obtener todas las categorías activas
    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener categoría por ID (solo activas)
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->image = $row['image'];
            $this->is_active = $row['is_active'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
    
    // Actualizar categoría
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET name = :name, image = :image, updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->image = htmlspecialchars(strip_tags($this->image));
        
        // Vincular valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Contar productos activos en una categoría
    public function countProducts() {
        $query = "SELECT COUNT(*) as count FROM products WHERE category_id = :id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }
    
    // Eliminar categoría (Soft Delete - marca como inactiva)
    public function delete() {
        try {
            // Verificar que tengamos un ID
            if (!isset($this->id) || empty($this->id)) {
                return false;
            }
            
            $this->conn->beginTransaction();
            
            // Marcar todos los productos de esta categoría como inactivos
            $query = "UPDATE products SET is_active = 0 WHERE category_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            // Marcar la categoría como inactiva
            $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $result = $stmt->execute();
            
            $this->conn->commit();
            return $result;
        } catch (Exception $e) {
            // Verificar si hay una transacción activa antes de hacer rollback
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            // Registrar el error para debugging
            error_log("Error al eliminar categoría (soft delete): " . $e->getMessage());
            return false;
        }
    }
    
    // Verificar si el nombre existe (solo en categorías activas)
    public function nameExists($name, $excludeId = null) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE name = :name AND is_active = 1";
        if($excludeId) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        if($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>