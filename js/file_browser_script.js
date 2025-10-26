// ================================================
//           EXPLORADOR DE ARCHIVOS - JS SIMPLE
// ================================================

class FileBrowser {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.warn('File browser container not found:', containerId);
            return;
        }
        
        this.options = {
            uploadUrl: 'file-browser-manager.php',
            allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
            maxFileSize: 5 * 1024 * 1024, // 5MB
            onSelect: null,
            ...options
        };
        
        this.selectedFile = null;
        this.files = [];
        
        this.init();
    }
    
    init() {
        this.createHTML();
        this.attachEvents();
        this.loadFiles();
    }
    
    createHTML() {
        this.container.innerHTML = `
            <div class="file-browser-container">
                <div class="file-browser-header">
                    <h3 class="file-browser-title">📁 Explorador de Imágenes</h3>
                    <button type="button" class="file-browser-upload-btn">📤 Subir Nueva</button>
                </div>
                
                <div class="file-browser-grid" id="fileBrowserGrid">
                    <!-- Archivos se cargan dinámicamente -->
                </div>
                
                <div class="file-browser-drop-area">
                    <p>📎 Arrastra archivos aquí o usa el botón "Subir Nueva"<br>
                    <small>Formatos: JPG, PNG, GIF, WEBP (máx. 5MB)</small></p>
                </div>
                
                <div class="file-browser-upload-progress">
                    <div class="file-browser-progress-bar"></div>
                </div>
                
                <div class="file-browser-status"></div>
                
                <input type="file" class="file-browser-input" accept="image/*" multiple>
            </div>
        `;
    }
    
    attachEvents() {
        const container = this.container.querySelector('.file-browser-container');
        const uploadBtn = this.container.querySelector('.file-browser-upload-btn');
        const fileInput = this.container.querySelector('.file-browser-input');
        
        if (!container || !uploadBtn || !fileInput) return;
        
        // Botón de subida
        uploadBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Input de archivo
        fileInput.addEventListener('change', (e) => {
            this.handleFileUpload(e.target.files);
        });
        
        // Drag and drop
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.classList.add('dragover');
        });
        
        container.addEventListener('dragleave', (e) => {
            e.preventDefault();
            if (!container.contains(e.relatedTarget)) {
                container.classList.remove('dragover');
            }
        });
        
        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.classList.remove('dragover');
            this.handleFileUpload(e.dataTransfer.files);
        });
    }
    
    async loadFiles() {
        try {
            const response = await fetch(`${this.options.uploadUrl}?action=list`);
            const data = await response.json();
            
            if (data.success) {
                this.files = data.files || [];
                this.renderFiles();
            } else {
                this.showStatus('Error al cargar archivos: ' + (data.message || 'Error desconocido'), 'error');
            }
        } catch (error) {
            console.error('Error al cargar archivos:', error);
            this.showStatus('Error de conexión al cargar archivos', 'error');
        }
    }
    
    renderFiles() {
        const grid = this.container.querySelector('#fileBrowserGrid');
        if (!grid) return;
        
        if (this.files.length === 0) {
            grid.innerHTML = '<p style="color: rgba(255,255,255,0.6); text-align: center; grid-column: 1/-1; font-size: 12px;">No hay imágenes disponibles</p>';
            return;
        }
        
        grid.innerHTML = this.files.map(file => `
            <div class="file-browser-item" data-filename="${file.name}" data-url="${file.url}">
                <img src="${file.url}" alt="${file.name}" loading="lazy">
                <p class="file-browser-item-name">${file.name}</p>
                <button type="button" class="file-browser-delete-btn" data-filename="${file.name}">&times;</button>
            </div>
        `).join('');
        
        // Eventos de click en archivos
        grid.querySelectorAll('.file-browser-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (!e.target.classList.contains('file-browser-delete-btn')) {
                    this.selectFile(item);
                }
            });
        });
        
        // Eventos de eliminar
        grid.querySelectorAll('.file-browser-delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.deleteFile(btn.dataset.filename);
            });
        });
    }
    
    selectFile(itemElement) {
        // Remover selección previa
        this.container.querySelectorAll('.file-browser-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        // Agregar selección
        itemElement.classList.add('selected');
        
        // Guardar archivo seleccionado
        this.selectedFile = {
            name: itemElement.dataset.filename,
            url: itemElement.dataset.url
        };
        
        // Llamar callback
        if (this.options.onSelect) {
            this.options.onSelect(this.selectedFile);
        }
        
        this.showStatus(`✅ Imagen seleccionada: ${this.selectedFile.name}`, 'success');
    }
    
    async handleFileUpload(files) {
        if (!files || files.length === 0) return;
        
        for (let file of files) {
            if (!this.validateFile(file)) continue;
            await this.uploadFile(file);
        }
        
        // Recargar archivos después de subir
        await this.loadFiles();
    }
    
    validateFile(file) {
        if (!this.options.allowedTypes.includes(file.type)) {
            this.showStatus(`Tipo de archivo no permitido: ${file.name}`, 'error');
            return false;
        }
        
        if (file.size > this.options.maxFileSize) {
            this.showStatus(`Archivo muy grande: ${file.name} (máx. ${Math.round(this.options.maxFileSize / 1024 / 1024)}MB)`, 'error');
            return false;
        }
        
        return true;
    }
    
    async uploadFile(file) {
        const formData = new FormData();
        formData.append('action', 'upload');
        formData.append('file', file);
        
        const progressBar = this.container.querySelector('.file-browser-progress-bar');
        const progressContainer = this.container.querySelector('.file-browser-upload-progress');
        
        try {
            // Mostrar progreso
            if (progressContainer && progressBar) {
                progressContainer.style.display = 'block';
                progressBar.style.width = '0%';
            }
            
            const response = await fetch(this.options.uploadUrl, {
                method: 'POST',
                body: formData
            });
            
            if (progressBar) progressBar.style.width = '50%';
            
            const data = await response.json();
            if (progressBar) progressBar.style.width = '100%';
            
            if (data.success) {
                this.showStatus(`✅ Archivo subido: ${file.name}`, 'success');
            } else {
                this.showStatus(`❌ Error al subir ${file.name}: ${data.message}`, 'error');
            }
            
        } catch (error) {
            console.error('Error al subir archivo:', error);
            this.showStatus(`❌ Error de conexión al subir ${file.name}`, 'error');
        } finally {
            // Ocultar progreso después de 2 segundos
            if (progressContainer && progressBar) {
                setTimeout(() => {
                    progressContainer.style.display = 'none';
                    progressBar.style.width = '0%';
                }, 2000);
            }
        }
    }
    
    async deleteFile(filename) {
        if (!confirm(`¿Estás seguro de eliminar "${filename}"?`)) return;
        
        try {
            const response = await fetch(this.options.uploadUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    filename: filename
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showStatus(`🗑️ Archivo eliminado: ${filename}`, 'success');
                // Limpiar selección si se eliminó el archivo seleccionado
                if (this.selectedFile && this.selectedFile.name === filename) {
                    this.selectedFile = null;
                    if (this.options.onSelect) {
                        this.options.onSelect(null);
                    }
                }
                // Recargar archivos
                await this.loadFiles();
            } else {
                this.showStatus(`❌ Error al eliminar ${filename}: ${data.message}`, 'error');
            }
            
        } catch (error) {
            console.error('Error al eliminar archivo:', error);
            this.showStatus('❌ Error de conexión al eliminar archivo', 'error');
        }
    }
    
    showStatus(message, type = 'success') {
        const status = this.container.querySelector('.file-browser-status');
        if (!status) return;
        
        status.textContent = message;
        status.className = `file-browser-status ${type}`;
        status.style.display = 'block';
        
        // Ocultar después de 4 segundos
        setTimeout(() => {
            status.style.display = 'none';
        }, 4000);
    }
    
    // Obtener archivo seleccionado
    getSelectedFile() {
        return this.selectedFile;
    }
    
    // Limpiar selección
    clearSelection() {
        this.selectedFile = null;
        this.container.querySelectorAll('.file-browser-item').forEach(item => {
            item.classList.remove('selected');
        });
    }
}

// Variable global para el explorador del producto
let productFileBrowser = null;

// Función para inicializar el explorador en el modal de producto
function initProductFileBrowser() {
    if (productFileBrowser) {
        productFileBrowser.clearSelection();
        return;
    }
    
    productFileBrowser = new FileBrowser('product-file-browser', {
        onSelect: function(file) {
            const productImageInput = document.getElementById('productImage');
            if (productImageInput) {
                if (file) {
                    // Solo guardar el nombre del archivo
                    productImageInput.value = file.name;
                    console.log('✅ Imagen seleccionada:', file.name);
                } else {
                    productImageInput.value = '';
                }
            }
        }
    });
}

// Función helper para crear instancias del explorador
function createFileBrowser(containerId, options = {}) {
    return new FileBrowser(containerId, options);
}