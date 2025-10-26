<?php
require_once 'config/config.php';
require_once 'models/Product.php';
require_once 'models/Category.php';

class ProductController {
    
    public function getAll() {
        header('Content-Type: application/json');
        
        $product = new Product();
        $products = $product->getAll();
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    }
    
    public function getFeatured() {
        header('Content-Type: application/json');
        
        $product = new Product();
        $products = $product->getFeatured();
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    }
    
    public function getByCategory() {
        header('Content-Type: application/json');
        
        if(!isset($_GET['category_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
            return;
        }
        
        $product = new Product();
        $products = $product->getByCategory($_GET['category_id']);
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
    }
    
    public function getById() {
        header('Content-Type: application/json');
        
        if(!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
            return;
        }
        
        $product = new Product();
        if($product->getById($_GET['id'])) {
            echo json_encode([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category_name,
                    'price' => floatval($product->price),
                    'cost' => floatval($product->cost),
                    'stock' => intval($product->stock),
                    'image' => $product->image,
                    'is_featured' => boolval($product->is_featured)
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        }
    }
    
    public function search() {
        header('Content-Type: application/json');
        
        if(!isset($_GET['q'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Término de búsqueda requerido']);
            return;
        }
        
        $product = new Product();
        $products = $product->search($_GET['q']);
        
        echo json_encode([
            'success' => true,
            'products' => $products
        ]);
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
        
        // Validar datos requeridos
        $required_fields = ['name', 'category_id', 'price', 'cost', 'stock'];
        foreach($required_fields as $field) {
            if(!isset($data[$field]) || (is_string($data[$field]) && empty(trim($data[$field])))) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Todos los campos requeridos deben ser completados']);
                return;
            }
        }
        
        $product = new Product();
        $product->name = cleanInput($data['name']);
        $product->description = isset($data['description']) ? cleanInput($data['description']) : '';
        $product->category_id = intval($data['category_id']);
        $product->price = floatval($data['price']);
        $product->cost = floatval($data['cost']);
        $product->stock = intval($data['stock']);
        $product->image = isset($data['image']) ? cleanInput($data['image']) : '';
        $product->is_featured = isset($data['is_featured']) ? boolval($data['is_featured']) : false;
        
        if($product->create()) {
            echo json_encode([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'product_id' => $product->id
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al crear producto']);
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
        
        if(!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
            return;
        }
        
        $product = new Product();
        if(!$product->getById($data['id'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
            return;
        }
        
        // Actualizar campos
        $product->name = cleanInput($data['name']);
        $product->description = isset($data['description']) ? cleanInput($data['description']) : '';
        $product->category_id = intval($data['category_id']);
        $product->price = floatval($data['price']);
        $product->cost = floatval($data['cost']);
        $product->stock = intval($data['stock']);
        $product->image = isset($data['image']) ? cleanInput($data['image']) : '';
        $product->is_featured = isset($data['is_featured']) ? boolval($data['is_featured']) : false;
        
        if($product->update()) {
            echo json_encode(['success' => true, 'message' => 'Producto actualizado exitosamente']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar producto']);
        }
    }
    
    public function delete() {
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
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                return;
            }
            
            $product = new Product();
            $product->id = $data['id'];
            
            if($product->delete()) {
                echo json_encode(['success' => true, 'message' => 'Producto eliminado exitosamente']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error al eliminar producto']);
            }
        } catch (Exception $e) {
            error_log('Error al eliminar producto: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Error al eliminar producto: ' . $e->getMessage()
            ]);
        }
    }
}
?>