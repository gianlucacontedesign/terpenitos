<?php
require_once 'config/config.php';
require_once 'models/Category.php';

class CategoryController {
    
    public function getAll() {
        header('Content-Type: application/json');
        
        $category = new Category();
        $categories = $category->getAll();
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    }
    
    public function getById() {
        header('Content-Type: application/json');
        
        if(!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
            return;
        }
        
        $category = new Category();
        if($category->getById($_GET['id'])) {
            echo json_encode([
                'success' => true,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => $category->image
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
        }
    }
    
    public function create() {
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
        
        if(!isset($data['name']) || empty(trim($data['name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nombre de categoría es requerido']);
            return;
        }
        
        $category = new Category();
        $name = cleanInput($data['name']);
        
        // Verificar si el nombre ya existe
        if($category->nameExists($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya existe una categoría con este nombre']);
            return;
        }
        
        $category->name = $name;
        $category->image = isset($data['image']) ? cleanInput($data['image']) : '';
        
        if($category->create()) {
            echo json_encode([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'category_id' => $category->id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear categoría']);
        }
    }
    
    public function update() {
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
        
        if(!isset($data['id']) || !isset($data['name']) || empty(trim($data['name']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID y nombre de categoría son requeridos']);
            return;
        }
        
        $category = new Category();
        if(!$category->getById($data['id'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
            return;
        }
        
        $name = cleanInput($data['name']);
        
        // Verificar si el nombre ya existe (excluyendo la categoría actual)
        if($category->nameExists($name, $data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ya existe una categoría con este nombre']);
            return;
        }
        
        $category->name = $name;
        $category->image = isset($data['image']) ? cleanInput($data['image']) : '';
        
        if($category->update()) {
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar categoría']);
        }
    }
    
    // Verificar si se puede eliminar la categoría
    public function checkDelete() {
        header('Content-Type: application/json');
        
        if(!isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            return;
        }
        
        if(!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
            return;
        }
        
        $category = new Category();
        $category->id = $_GET['id'];
        $productCount = $category->countProducts();
        
        if($productCount > 0) {
            // Obtener otras categorías para la opción de mover
            $allCategories = $category->getAll();
            $otherCategories = array_filter($allCategories, function($cat) use ($category) {
                return $cat['id'] != $category->id;
            });
            
            echo json_encode([
                'success' => false,
                'has_products' => true,
                'product_count' => $productCount,
                'other_categories' => array_values($otherCategories),
                'message' => "Esta categoría tiene {$productCount} producto(s) asociado(s)"
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'has_products' => false,
                'product_count' => 0,
                'message' => 'La categoría puede eliminarse sin problemas'
            ]);
        }
    }
    
    public function delete() {
        // Activar reporte de errores para debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // No mostrar en pantalla, usar log
        
        header('Content-Type: application/json');
        
        try {
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
            
            if(!isset($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
                return;
            }
            
            $category = new Category();
            $category->id = $data['id'];
            
            // Eliminar categoría y sus productos
            if($category->delete()) {
                echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la categoría. Verifica los logs del servidor.']);
            }
        } catch (Exception $e) {
            error_log("Error en CategoryController::delete - " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Error interno del servidor',
                'debug' => $e->getMessage() // Solo para debugging, remover en producción
            ]);
        }
    }
}
?>