# 🚀 API Batidos Pitaya

Repositorio de la API central para los servicios de Batidos Pitaya.

## 📦 Estructura del Proyecto

- `api/`: Lógica central de la API (sincronizada con producción).
- `core/`: Dependencias y archivos core (gestionados manualmente o vía composer).
- `.github/workflows/`: Workflows de GitHub Actions para deploy automático.
- `.scripts/`: Scripts auxiliares de PowerShell.

## 🚀 Deploy Automático

Este repositorio utiliza **GitHub Actions** para desplegar automáticamente la carpeta `api/` en el servidor de producción Hostinger.

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
