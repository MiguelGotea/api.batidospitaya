# 📦 Scripts de Deploy - API Batidos Pitaya

Esta carpeta contiene los scripts para realizar despliegues rápidos del repositorio API.

## 🚀 Uso desde la Terminal

### Desde la raíz del proyecto:
```powershell
.\.scripts\gitpush.ps1
```

### Qué hace el script:
- Realiza `git add .`
- Crea un commit con la fecha y hora actual.
- Sube los cambios a la rama `main` de GitHub.
- Activa el deploy automático vía GitHub Actions.

---

## 🏗️ Lógica del Deploy

El sistema de deploy está configurado para:
- ✅ Sincronizar **únicamente** la carpeta `api/`.
- ❌ Excluir la carpeta `api/uploads/` para preservar archivos subidos por usuarios.
- 🔧 Configurar permisos automáticos en el servidor (755 carpetas, 644 archivos).

---

## 🔄 Sincronización Manual (Reset)

Si necesitas forzar que el servidor se iguale a GitHub:

```bash
ssh -p 65002 u839374897@145.223.105.42
cd ~/domains/api.batidospitaya.com/public_html
git fetch origin main
git reset --hard origin/main
```

> [!CAUTION]
> El comando `git reset --hard` borrará cualquier cambio local no committeado en el servidor. Úsalo con precaución.

---

## 🔐 Configuración SSH

Este repositorio utiliza la clave estandarizada `batidospitaya-deploy`.

Ver documentación completa:  
[docs/DEPLOY_SETUP.md](docs/DEPLOY_SETUP.md)

---

**Última actualización:** 2026-02-17

