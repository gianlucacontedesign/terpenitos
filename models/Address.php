<?php
require_once 'config/database.php';

class Address {
    private $conn;
    private $table_name = "user_addresses";
    
    public $id;
    public $user_id;
    public $alias;
    public $address_line1;
    public $city;
    public $postal_code;
    public $is_default;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Crear dirección
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, alias, address_line1, city, postal_code, is_default, created_at, updated_at)
                VALUES (:user_id, :alias, :address_line1, :city, :postal_code, :is_default, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->alias = htmlspecialchars(strip_tags($this->alias));
        $this->address_line1 = htmlspecialchars(strip_tags($this->address_line1));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
        
        // Si es la dirección predeterminada, desmarcar las demás
        if($this->is_default) {
            $this->unsetDefaultAddresses($this->user_id);
        }
        
        // Vincular valores
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':alias', $this->alias);
        $stmt->bindParam(':address_line1', $this->address_line1);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':postal_code', $this->postal_code);
        $stmt->bindParam(':is_default', $this->is_default, PDO::PARAM_BOOL);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    // Obtener direcciones por usuario
    public function getByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener dirección por ID
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->alias = $row['alias'];
            $this->address_line1 = $row['address_line1'];
            $this->city = $row['city'];
            $this->postal_code = $row['postal_code'];
            $this->is_default = $row['is_default'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }
    
    // Actualizar dirección
    public function update() {
        // Si es la dirección predeterminada, desmarcar las demás
        if($this->is_default) {
            $this->unsetDefaultAddresses($this->user_id, $this->id);
        }
        
        $query = "UPDATE " . $this->table_name . "
                SET alias = :alias, address_line1 = :address_line1, city = :city,
                    postal_code = :postal_code, is_default = :is_default, updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->alias = htmlspecialchars(strip_tags($this->alias));
        $this->address_line1 = htmlspecialchars(strip_tags($this->address_line1));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
        
        // Vincular valores
        $stmt->bindParam(':alias', $this->alias);
        $stmt->bindParam(':address_line1', $this->address_line1);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':postal_code', $this->postal_code);
        $stmt->bindParam(':is_default', $this->is_default, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Eliminar dirección
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Desmarcar direcciones predeterminadas
    private function unsetDefaultAddresses($user_id, $exclude_id = null) {
        $query = "UPDATE " . $this->table_name . " SET is_default = 0 WHERE user_id = :user_id";
        if($exclude_id) {
            $query .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        if($exclude_id) {
            $stmt->bindParam(':exclude_id', $exclude_id);
        }
        
        return $stmt->execute();
    }
    
    // Obtener dirección predeterminada
    public function getDefaultByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND is_default = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
}
?>