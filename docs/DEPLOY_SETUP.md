# 📋 Sistema de Deploy Automático - Batidos Pitaya

## 🎯 Resumen

Sistema de deploy automático **unidireccional** desde GitHub hacia servidores Hostinger usando GitHub Actions. Cada vez que se hace push a la rama `main`, el código se despliega automáticamente al servidor de producción.

---

## 🏗️ Arquitectura del Sistema

### Flujo de Trabajo

```
┌─────────────────┐
│ Desarrollador   │
│ (VS Code local) │
└────────┬────────┘
         │
         │ git push
         ▼
┌─────────────────┐
│ GitHub          │
│ (Repositorio)   │
└────────┬────────┘
         │
         │ GitHub Actions
         │ (automático)
         ▼
┌─────────────────┐
│ Hostinger       │
│ (Producción)    │
└─────────────────┘
```

### Componentes

- **Servidor:** Hostinger (145.223.105.42)
- **Autenticación:** SSH con clave Ed25519 (`batidospitaya-deploy`)
- **Método:** rsync sobre SSH
- **Trigger:** Push a rama `main`

---

## 🔧 Configuración del Servidor

### Llaves SSH

El servidor tiene configuradas las siguientes llaves SSH **compartidas para todos los dominios**:

```bash
~/.ssh/
├── authorized_keys          # Contiene la clave pública
├── batidospitaya-deploy     # Clave privada (guardada en GitHub Secrets)
├── batidospitaya-deploy.pub # Clave pública (Ed25519)
└── known_hosts              # Hosts conocidos
```

**Clave pública:**
```bash
ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIAHWWJu9du9uzZKDP5ChDrpCef8QB4uvJMXZ58SkH2XZ batidospitaya-deploy@batidospitaya.com
```

> [!IMPORTANT]
> Esta clave SSH se usa para **TODOS** los dominios de Batidos Pitaya (ERP, API, Portal, etc.)

---

## 🔑 Gestión de Credenciales

### Obtener Clave Privada (HOSTINGER_SSH_KEY)

La clave privada se encuentra configurada en el servidor Hostinger. Para obtenerla y configurarla en un nuevo dominio:

1. Conectarse al servidor: `ssh -p 65002 u839374897@145.223.105.42`
2. Ejecutar: `cat ~/.ssh/batidospitaya-deploy`
3. Copiar el contenido **completo** (desde `-----BEGIN OPENSSH PRIVATE KEY-----` hasta `-----END OPENSSH PRIVATE KEY-----`).
4. Pegar en el Secret `HOSTINGER_SSH_KEY` de GitHub Actions.

> [!TIP]
> Si la clave parece cortarse al copiarla de la terminal, intenta aumentar el buffer de la terminal o usar `cat ~/.ssh/batidospitaya-deploy | base64` y decodificarla.

---

## 📦 Dominios Configurados

### ERP - `erp.batidospitaya.com`

**Repository:** https://github.com/MiguelGotea/erp.batidospitaya  
**Workflow:** `.github/workflows/deploy-erp.yml`  
**Trigger:** `modulos/**`  
**Sincroniza:** 17 módulos individuales  
**Excluye:** `uploads/` por módulo

**Secrets:**
- `HOSTINGER_SSH_KEY`: Clave privada compartida
- `HOSTINGER_USER`: `u839374897`
- `HOSTINGER_HOST`: `145.223.105.42`
- `HOSTINGER_PATH`: `/home/u839374897/domains/erp.batidospitaya.com/public_html`

### API - `api.batidospitaya.com`

**Repository:** https://github.com/MiguelGotea/api.batidospitaya  
**Workflow:** `.github/workflows/deploy-api.yml`  
**Trigger:** `api/**`  
**Sincroniza:** Carpeta `api/`  
**Excluye:** `api/uploads/`

**Secrets:**
- `HOSTINGER_SSH_KEY`: Clave privada compartida
- `HOSTINGER_USER`: `u839374897`
- `HOSTINGER_HOST`: `145.223.105.42`
- `HOSTINGER_PATH_API`: `/home/u839374897/domains/api.batidospitaya.com/public_html`

---

## 🚀 Uso Diario

### Hacer Cambios y Desplegar

1. **Editar archivos localmente** en VS Code

2. **Commit y push:**
   ```bash
   git add .
   git commit -m "Descripción del cambio"
   git push origin main
   ```

3. **GitHub Actions se activa automáticamente**

4. **Deploy completo en ~1-2 minutos**

### Monitorear Deploy

- **ERP:** https://github.com/MiguelGotea/erp.batidospitaya/actions
- **API:** https://github.com/MiguelGotea/api.batidospitaya/actions

---

## 🔍 Verificación y Troubleshooting

### Verificar Archivos en el Servidor

```bash
# Conectarse al servidor
ssh -p 65002 u839374897@145.223.105.42
# Password: ERpPitHay2025$

# Verificar ERP
cd /home/u839374897/domains/erp.batidospitaya.com/public_html
ls -la modulos/

# Verificar API
cd /home/u839374897/domains/api.batidospitaya.com/public_html
ls -la api/
```

### Problemas Comunes

Ver: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

---

## 🔐 Seguridad

### Buenas Prácticas Implementadas

- ✅ **Autenticación SSH:** Usando claves Ed25519 (más seguras que RSA)
- ✅ **Secrets protegidos:** Claves privadas nunca en el código
- ✅ **Sin Git en producción:** El servidor NO tiene `.git/`, `.github/`, ni `.gitignore`
- ✅ **Permisos correctos:** Archivos 644, directorios 755, uploads 777
- ✅ **Conexión segura:** Puerto SSH no estándar (65002)
- ✅ **Una sola llave:** Compartida entre todos los dominios para facilitar mantenimiento

### Acceso al Servidor

**Solo para emergencias o verificación manual:**

```bash
ssh -p 65002 u839374897@145.223.105.42
# Password: ERpPitHay2025$
```

> [!WARNING]
> **NO hacer cambios directamente en el servidor.** Todos los cambios deben hacerse en el repositorio local y desplegarse vía GitHub Actions.

---

## 📊 Estadísticas del Sistema

- **Dominios activos:** 2 (ERP, API)
- **Tiempo promedio de deploy:** 1-2 minutos
- **Frecuencia de deploys:** Cada push a `main`
- **Método de sincronización:** rsync sobre SSH
- **Llave SSH compartida:** `batidospitaya-deploy`

---

## 🆘 Soporte

### Recursos

- **Implementar nuevo dominio:** [DEPLOY_NEW_DOMAIN.md](./DEPLOY_NEW_DOMAIN.md)
- **Solución de problemas:** [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

---

**Última actualización:** 2026-02-17
