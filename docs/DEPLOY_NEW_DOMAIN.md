# 🚀 Implementar Deploy en Nuevo Dominio

Esta guía te ayudará a configurar el deploy automático para un nuevo dominio de Batidos Pitaya.

---

## ✅ Checklist de Implementación

- [ ] 1. Verificar que el dominio existe en Hostinger
- [ ] 2. Crear repositorio en GitHub
- [ ] 3. Configurar secrets en GitHub
- [ ] 4. Crear workflow de GitHub Actions
- [ ] 5. Probar deploy
- [ ] 6. Documentar configuración

---

## 📋 Paso 1: Verificar Dominio en Hostinger

```bash
# Conectarse al servidor
ssh -p 65002 u839374897@145.223.105.42

# Verificar que existe la carpeta del dominio
ls -la ~/domains/

# Ejemplo de salida:
# drwxr-xr-x  api.batidospitaya.com
# drwxr-xr-x  erp.batidospitaya.com
# drwxr-xr-x  tudominio.batidospitaya.com  ← Tu nuevo dominio
```

**Ruta típica del dominio:**
```
/home/u839374897/domains/TUDOMINIO.batidospitaya.com/public_html
```

---

## 📋 Paso 2: Crear Repositorio en GitHub

1. Ir a: https://github.com/new
2. **Repository name:** `tudominio.batidospitaya`
3. **Description:** "Sistema de [descripción] - Batidos Pitaya"
4. **Visibility:** Private
5. Click "Create repository"

---

## 📋 Paso 3: Configurar Secrets en GitHub

Ir a: `https://github.com/MiguelGotea/tudominio.batidospitaya/settings/secrets/actions`

### Secrets Requeridos

| Secret Name | Valor | Descripción |
|-------------|-------|-------------|
| `HOSTINGER_SSH_KEY` | Ver abajo | Clave privada SSH compartida |
| `HOSTINGER_USER` | `u839374897` | Usuario SSH |
| `HOSTINGER_HOST` | `145.223.105.42` | IP del servidor |
| `HOSTINGER_PATH_TUDOMINIO` | `/home/u839374897/domains/tudominio.batidospitaya.com/public_html` | Ruta del dominio |

### Obtener `HOSTINGER_SSH_KEY`

```bash
# Conectarse al servidor
ssh -p 65002 u839374897@145.223.105.42

# Mostrar clave privada
cat ~/.ssh/batidospitaya-deploy

# Copiar TODO el contenido (incluyendo BEGIN y END)
```

> [!IMPORTANT]
> La clave SSH `batidospitaya-deploy` es **compartida** entre todos los dominios. No necesitas crear una nueva.

---

## 📋 Paso 4: Crear Workflow de GitHub Actions

### Template Básico

Crear archivo: `.github/workflows/deploy-tudominio.yml`

```yaml
name: 🚀 Deploy TuDominio - Batidos Pitaya

on:
  push:
    branches: [ main ]
    paths:
      - 'carpeta-a-sincronizar/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - name: 📥 Checkout código
      uses: actions/checkout@v4
      
    - name: 🔑 Configurar SSH
      uses: webfactory/ssh-agent@v0.8.0
      with:
        ssh-private-key: ${{ secrets.HOSTINGER_SSH_KEY }}
        
    - name: 📤 Sincronizar archivos
      run: |
        echo "🔄 Sincronizando carpeta-a-sincronizar/..."
        
        rsync -avz \
          --delete \
          --exclude='uploads/' \
          -e "ssh -o StrictHostKeyChecking=no -p 65002" \
          ./carpeta-a-sincronizar/ \
          ${{ secrets.HOSTINGER_USER }}@${{ secrets.HOSTINGER_HOST }}:${{ secrets.HOSTINGER_PATH_TUDOMINIO }}/carpeta-a-sincronizar/
          
    - name: 🔧 Configurar permisos
      run: |
        echo "🔧 Configurando permisos en el servidor..."
        
        ssh -o StrictHostKeyChecking=no -p 65002 ${{ secrets.HOSTINGER_USER }}@${{ secrets.HOSTINGER_HOST }} << 'EOF'
          cd ${{ secrets.HOSTINGER_PATH_TUDOMINIO }}
          find carpeta-a-sincronizar -type d -exec chmod 755 {} \;
          find carpeta-a-sincronizar -type f -exec chmod 644 {} \;
          chmod -R 777 carpeta-a-sincronizar/uploads/ || true
        EOF
        
    - name: ✅ Verificar deploy
      run: |
        echo "✅ Deploy completado exitosamente"
        echo "📁 Carpeta sincronizada: carpeta-a-sincronizar/"
        echo "🌐 Sitio: https://tudominio.batidospitaya.com"
        echo "🖥️ Servidor: ${{ secrets.HOSTINGER_HOST }}:65002"
```

### Personalizar el Workflow

**Cambiar:**
1. `TuDominio` → Nombre descriptivo
2. `carpeta-a-sincronizar` → Carpeta real a sincronizar
3. `HOSTINGER_PATH_TUDOMINIO` → Nombre del secret correcto
4. `tudominio.batidospitaya.com` → URL real del dominio

### Ejemplos de Configuración

#### Ejemplo 1: Una Carpeta (como API)
```yaml
paths:
  - 'api/**'

# Sincronizar solo api/
rsync ... ./api/ ... /api/
```

#### Ejemplo 2: Múltiples Carpetas (como ERP)
```yaml
paths:
  - 'modulos/**'

# Loop por cada módulo
for MODULE in "${MODULES[@]}"; do
  rsync ... ./modulos/$MODULE/ ... /modulos/$MODULE/
done
```

---

## 📋 Paso 5: Probar Deploy

### 5.1 Crear Archivo de Prueba

```bash
# En tu repositorio local
cd tudominio.batidospitaya.com
mkdir -p carpeta-a-sincronizar
echo "<?php echo 'Deploy funcionando!'; ?>" > carpeta-a-sincronizar/test.php
```

### 5.2 Commit y Push

```bash
git add .
git commit -m "Test: verificar deploy automático"
git push origin main
```

### 5.3 Verificar en GitHub Actions

1. Ir a: `https://github.com/MiguelGotea/tudominio.batidospitaya/actions`
2. Ver el workflow más reciente
3. Verificar que esté en estado "Success" (verde)

### 5.4 Verificar en el Servidor

```bash
# Conectarse al servidor
ssh -p 65002 u839374897@145.223.105.42

# Verificar archivo
cat /home/u839374897/domains/tudominio.batidospitaya.com/public_html/carpeta-a-sincronizar/test.php

# Debería mostrar: <?php echo 'Deploy funcionando!'; ?>
```

### 5.5 Verificar en Navegador

Ir a: `https://tudominio.batidospitaya.com/carpeta-a-sincronizar/test.php`

Debería mostrar: `Deploy funcionando!`

---

## 📋 Paso 6: Documentar Configuración

Actualizar [DEPLOY_SETUP.md](./DEPLOY_SETUP.md) agregando el nuevo dominio en la sección "Dominios Configurados":

```markdown
### TuDominio - `tudominio.batidospitaya.com`

**Repository:** https://github.com/MiguelGotea/tudominio.batidospitaya  
**Workflow:** `.github/workflows/deploy-tudominio.yml`  
**Trigger:** `carpeta-a-sincronizar/**`  
**Sincroniza:** Carpeta `carpeta-a-sincronizar/`  
**Excluye:** `uploads/`

**Secrets:**
- `HOSTINGER_SSH_KEY`: Clave privada compartida
- `HOSTINGER_USER`: `u839374897`
- `HOSTINGER_HOST`: `145.223.105.42`
- `HOSTINGER_PATH_TUDOMINIO`: `/home/u839374897/domains/tudominio.batidospitaya.com/public_html`
```

---

## 🎯 Resultado Final

Después de completar estos pasos, tendrás:

- ✅ Repositorio en GitHub
- ✅ Secrets configurados
- ✅ Workflow de GitHub Actions funcionando
- ✅ Deploy automático en cada push a `main`
- ✅ Documentación actualizada

---

## 🆘 Problemas Comunes

### Workflow no se ejecuta
**Causa:** Los cambios no están en la ruta especificada en `paths:`  
**Solución:** Verificar que modificaste archivos en la carpeta correcta

### Error "Permission denied"
**Causa:** Problema con la clave SSH  
**Solución:** Verificar que `HOSTINGER_SSH_KEY` contiene la clave completa

### Archivos no se actualizan
**Causa:** Ruta incorrecta en el secret  
**Solución:** Verificar que `HOSTINGER_PATH_TUDOMINIO` es correcto

Ver más: [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)

---

**Última actualización:** 2026-02-17
