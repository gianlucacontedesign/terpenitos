<?php
require_once 'config/config.php';
require_once 'models/User.php';

class AuthController {
    
    public function login() {
        header('Content-Type: application/json');
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos']);
            return;
        }
        
        // Verificar token CSRF
        if(!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }
        
        $email = cleanInput($data['email']);
        $password = $data['password'];
        
        // Verificar credenciales de administrador
        if($email === ADMIN_EMAIL && password_verify($password, ADMIN_PASSWORD)) {
            $_SESSION['user_id'] = 'admin';
            $_SESSION['user_name'] = 'Administrador';
            $_SESSION['user_email'] = $email;
            $_SESSION['is_admin'] = true;
            
            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => [
                    'id' => 'admin',
                    'name' => 'Administrador',
                    'email' => $email,
                    'is_admin' => true
                ]
            ]);
            return;
        }
        
        // Verificar usuario normal
        $user = new User();
        if($user->login($email, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['is_admin'] = false;
            
            echo json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_admin' => false
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Credenciales inválidas']);
        }
    }
    
    public function register() {
        header('Content-Type: application/json');
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos requeridos
        $required_fields = ['name', 'email', 'phone', 'password'];
        foreach($required_fields as $field) {
            if(!isset($data[$field]) || empty(trim($data[$field]))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
                return;
            }
        }
        
        // Verificar token CSRF
        if(!isset($data['csrf_token']) || !verifyCSRFToken($data['csrf_token'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }
        
        // Validar formato de email
        if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Formato de email inválido']);
            return;
        }
        
        // Validar longitud de contraseña
        if(strlen($data['password']) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres']);
            return;
        }
        
        $user = new User();
        $user->name = cleanInput($data['name']);
        $user->email = strtolower(cleanInput($data['email']));
        $user->phone = cleanInput($data['phone']);
        $user->password = $data['password'];
        
        // Verificar si el email ya existe
        if($user->emailExists($user->email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Este email ya está registrado']);
            return;
        }
        
        if($user->create()) {
            echo json_encode([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'user_id' => $user->id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al registrar usuario']);
        }
    }
    
    public function logout() {
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Sesión cerrada exitosamente']);
    }
    
    public function getUser() {
        header('Content-Type: application/json');
        
        if(!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'is_admin' => $_SESSION['is_admin'] ?? false
            ]
        ]);
    }
    
    public function updateProfile() {
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
        
        $user = new User();
        $user->id = $_SESSION['user_id'];
        $user->name = cleanInput($data['name']);
        $user->phone = cleanInput($data['phone']);
        
        if($user->update()) {
            $_SESSION['user_name'] = $user->name;
            echo json_encode(['success' => true, 'message' => 'Perfil actualizado exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar perfil']);
        }
    }
    
    public function changePassword() {
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
        
        $required_fields = ['current_password', 'new_password'];
        foreach($required_fields as $field) {
            if(!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
                return;
            }
        }
        
        if(strlen($data['new_password']) < 6) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres']);
            return;
        }
        
        $user = new User();
        $user->id = $_SESSION['user_id'];
        
        if($user->changePassword($data['current_password'], $data['new_password'])) {
            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada exitosamente']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Contraseña actual incorrecta']);
        }
    }
}
?>
