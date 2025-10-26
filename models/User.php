<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $name;
    public $email;
    public $phone;
    public $password;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    // Crear usuario
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (name, email, phone, password, created_at, updated_at)
                VALUES (:name, :email, :phone, :password, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Hash de la contraseña
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Vincular valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':password', $this->password);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    // Verificar login
    public function login($email, $password) {
        $query = "SELECT id, name, email, phone, password FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                return true;
            }
        }
        return false;
    }
    
    // Verificar si el email existe
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Obtener usuario por ID
    public function getById($id) {
        $query = "SELECT id, name, email, phone, created_at FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
    
    // Actualizar perfil
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET name = :name, phone = :phone, updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        
        // Vincular valores
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Cambiar contraseña
    public function changePassword($currentPassword, $newPassword) {
        // Verificar contraseña actual
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($currentPassword, $row['password'])) {
                // Actualizar contraseña
                $query = "UPDATE " . $this->table_name . " SET password = :password, updated_at = NOW() WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':id', $this->id);
                return $stmt->execute();
            }
        }
        return false;
    }
}
?>