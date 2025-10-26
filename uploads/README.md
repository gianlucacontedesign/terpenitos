# Directorio de Uploads

Este directorio almacena archivos subidos por los usuarios.

## Estructura

- **temp/** - Archivos temporales
- **images/** - Imágenes subidas
- **documents/** - Documentos varios

## Seguridad

- Solo permitir tipos de archivo seguros
- Validar tamaño máximo
- Escanear por malware

## Permisos

```bash
chmod 755 uploads/
chown -R www-data:www-data uploads/
```

## Configuración

Los límites de upload se configuran en PHP:
- upload_max_filesize
- post_max_size
- max_file_uploads