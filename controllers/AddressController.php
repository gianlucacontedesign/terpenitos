<?php
require_once 'config/config.php';
require_once 'models/Address.php';

class AddressController {
    
    public function getUserAddresses() {
        header('Content-Type: application/json');
        
        if(!isLoggedIn() || $_SESSION['is_admin']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        
        $address = new Address();
        $addresses = $address->getByUser($_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'addresses' => $addresses
        ]);
    }
    
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
        $required_fields = ['alias', 'address_line1', 'city', 'postal_code'];
        foreach($required_fields as $field) {
            if(!isset($data[$field]) || empty(trim($data[$field]))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
                return;
            }
        }
        
        $address = new Address();
        $address->user_id = $_SESSION['user_id'];
        $address->alias = cleanInput($data['alias']);
        $address->address_line1 = cleanInput($data['address_line1']);
        $address->city = cleanInput($data['city']);
        $address->postal_code = cleanInput($data['postal_code']);
        $address->is_default = isset($data['is_default']) ? boolval($data['is_default']) : false;
        
        if($address->create()) {
            echo json_encode([
                'success' => true,
                'message' => 'Dirección creada exitosamente',
                'address_id' => $address->id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear dirección']);
        }
    }
    
    public function update() {
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
        
        if(!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de dirección requerido']);
            return;
        }
        
        $address = new Address();
        if(!$address->getById($data['id'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Dirección no encontrada']);
            return;
        }
        
        // Verificar que la dirección pertenece al usuario
        if($address->user_id != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            return;
        }
        
        // Actualizar campos
        $address->alias = cleanInput($data['alias']);
        $address->address_line1 = cleanInput($data['address_line1']);
        $address->city = cleanInput($data['city']);
        $address->postal_code = cleanInput($data['postal_code']);
        $address->is_default = isset($data['is_default']) ? boolval($data['is_default']) : false;
        
        if($address->update()) {
            echo json_encode(['success' => true, 'message' => 'Dirección actualizada exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar dirección']);
        }
    }
    
    public function delete() {
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
        
        if(!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de dirección requerido']);
            return;
        }
        
        $address = new Address();
        if(!$address->getById($data['id'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Dirección no encontrada']);
            return;
        }
        
        // Verificar que la dirección pertenece al usuario
        if($address->user_id != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            return;
        }
        
        if($address->delete()) {
            echo json_encode(['success' => true, 'message' => 'Dirección eliminada exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar dirección']);
        }
    }
    
    public function getDefault() {
        header('Content-Type: application/json');
        
        if(!isLoggedIn() || $_SESSION['is_admin']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        
        $address = new Address();
        $defaultAddress = $address->getDefaultByUser($_SESSION['user_id']);
        
        if($defaultAddress) {
            echo json_encode([
                'success' => true,
                'address' => $defaultAddress
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No hay dirección predeterminada'
            ]);
        }
    }
}
?>