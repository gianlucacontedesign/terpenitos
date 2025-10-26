<?php
require_once 'config/config.php';

class ImageController {
    
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    // Subir imagen de producto
    public function uploadProduct() {
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
        
        try {
            $result = $this->handleUpload('img/productos/');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
    }
    
    // Subir imagen de categoría
    public function uploadCategory() {
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
        
        try {
            $result = $this->handleUpload('img/categorias/');
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
    }
    
    // Procesar la carga de archivo
    private function handleUpload($targetDir) {
        // Verificar que se envió un archivo
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception('No se seleccionó ningún archivo');
        }
        
        $file = $_FILES['image'];
        
        // Verificar errores de carga
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo: ' . $this->getUploadErrorMessage($file['error']));
        }
        
        // Verificar tipo de archivo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido. Solo se aceptan imágenes JPG, PNG, GIF y WEBP.');
        }
        
        // Verificar tamaño
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('El archivo es demasiado grande. Tamaño máximo: 5MB.');
        }
        
        // Crear directorio si no existe
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generar nombre único para evitar sobrescrituras
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        
        // Limpiar el nombre del archivo
        $cleanName = $this->cleanFileName($originalName);
        $fileName = $cleanName . '.' . strtolower($extension);
        $targetPath = $targetDir . $fileName;
        
        // Si el archivo ya existe, agregar un número
        $counter = 1;
        while (file_exists($targetPath)) {
            $fileName = $cleanName . '_' . $counter . '.' . strtolower($extension);
            $targetPath = $targetDir . $fileName;
            $counter++;
        }
        
        // Mover el archivo
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Error al guardar el archivo en el servidor');
        }
        
        // Optimizar imagen (opcional, requiere GD)
        $this->optimizeImage($targetPath, $mimeType);
        
        return [
            'success' => true,
            'message' => 'Imagen subida exitosamente',
            'filename' => $fileName,
            'path' => $targetPath
        ];
    }
    
    // Limpiar nombre de archivo
    private function cleanFileName($name) {
        // Reemplazar espacios y caracteres especiales
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9\-_]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');
        return $name;
    }
    
    // Optimizar imagen para reducir tamaño
    private function optimizeImage($filePath, $mimeType) {
        if (!extension_loaded('gd')) {
            return; // GD no disponible
        }
        
        try {
            $image = null;
            
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = imagecreatefromjpeg($filePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($filePath);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($filePath);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $image = imagecreatefromwebp($filePath);
                    }
                    break;
            }
            
            if ($image) {
                // Guardar con compresión
                switch ($mimeType) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        imagejpeg($image, $filePath, 85); // Calidad 85%
                        break;
                    case 'image/png':
                        imagepng($image, $filePath, 8); // Compresión nivel 8
                        break;
                }
                
                imagedestroy($image);
            }
        } catch (Exception $e) {
            // Si falla la optimización, continuar con la imagen original
            error_log('Error al optimizar imagen: ' . $e->getMessage());
        }
    }
    
    // Obtener mensaje de error de carga
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la carga'
        ];
        
        return $errors[$errorCode] ?? 'Error desconocido al subir el archivo';
    }
}
?>