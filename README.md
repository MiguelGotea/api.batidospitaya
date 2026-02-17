# 🚀 API Batidos Pitaya

Repositorio de la API central para los servicios de Batidos Pitaya.

## 📦 Estructura del Proyecto

- `api/`: Lógica central de la API.
- `core/`: Dependencias y archivos core (excluidos del deploy).
- `default.php`: Página de bienvenida técnica.
- `README.md`: Documentación del proyecto.
- `.github/workflows/`: Workflows de GitHub Actions.
- `.scripts/`: Scripts auxiliares de PowerShell.

### Gestión de Archivos (Estandarización)
Para mantener el repositorio limpio y seguro, se aplican las siguientes reglas:

| Carpeta/Archivo | Subir a GitHub | Subir al Host |
| :--- | :---: | :---: |
| `.agent/`, `core/`, `docs/`, `api/uploads/` | ❌ No | ❌ No |
| `.scripts/` | ✅ Sí | ❌ No |
| `.github/`, `.gitignore` | ✅ Sí | ❌ No |
| `api/` (lógica) | ✅ Sí | ✅ Sí |
| Raíz (`default.php`, `README.md`, `.htaccess`) | ✅ Sí | ✅ Sí |

- 🔧 Permisos automáticos aplicados en cada deploy: 755 para carpetas y 644 para archivos.
 Hostinger.

### Documentación de Deploy

- [**Guía de Configuración General**](docs/DEPLOY_SETUP.md)
- [**Implementar Nuevo Dominio**](docs/DEPLOY_NEW_DOMAIN.md)

---

## 🛠️ Desarrollo Local

### Configuración
Asegúrate de tener un entorno PHP local configurado para probar los endpoints.

### Scripts de Ayuda
Usa el script en `.scripts/` para realizar pushes rápidos:
- `.\.scripts\gitpush.ps1`: Sube todos los cambios en `api/` y activa el deploy.

---

**Última actualización:** 2026-02-17
