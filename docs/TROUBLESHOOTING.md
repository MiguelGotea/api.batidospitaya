# 🔧 Troubleshooting - Deploy Automático

Guía de solución de problemas comunes del sistema de deploy automático.

---

## 🚨 Workflow No Se Ejecuta

### Síntoma
El workflow no aparece en GitHub Actions después de hacer push.

### Causas Posibles

#### 1. Los cambios no están en la ruta monitoreada
```yaml
# El workflow solo se activa si hay cambios en:
paths:
  - 'modulos/**'  # Para ERP
  - 'api/**'      # Para API
```

**Solución:**
- Verificar que modificaste archivos en la carpeta correcta
- Hacer cambios en la carpeta monitoreada y volver a hacer push

#### 2. El workflow tiene errores de sintaxis
**Solución:**
- Verificar la sintaxis YAML en: https://www.yamllint.com/
- Revisar logs de GitHub Actions

#### 3. El archivo no está en `.github/workflows/`
**Solución:**
- Verificar que el archivo `.yml` está en la carpeta correcta
- El nombre del archivo puede ser cualquiera, pero debe terminar en `.yml`

---

## 🚨 Workflow Falla con "Permission denied"

### Síntoma
```
Permission denied (publickey)
```

### Causas Posibles

#### 1. Secret `HOSTINGER_SSH_KEY` incorrecto o incompleto
**Solución:**
```bash
# Obtener la clave privada completa
ssh -p 65002 u839374897@145.223.105.42
cat ~/.ssh/batidospitaya-deploy

# Copiar TODO (incluyendo BEGIN y END)
# Actualizar en GitHub:
# Settings → Secrets → HOSTINGER_SSH_KEY
```

#### 2. `authorized_keys` no contiene la clave pública
**Solución:**
```bash
ssh -p 65002 u839374897@145.223.105.42

# Verificar authorized_keys
cat ~/.ssh/authorized_keys

# Si está vacío o incorrecto, regenerar:
cat ~/.ssh/batidospitaya-deploy.pub > ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

#### 3. Permisos incorrectos en archivos SSH
**Solución:**
```bash
ssh -p 65002 u839374897@145.223.105.42

# Corregir permisos
chmod 700 ~/.ssh
chmod 600 ~/.ssh/batidospitaya-deploy
chmod 644 ~/.ssh/batidospitaya-deploy.pub
chmod 600 ~/.ssh/authorized_keys
```

---

## 🚨 Archivos No Se Actualizan en el Servidor

### Síntoma
El workflow se ejecuta exitosamente pero los archivos en el servidor no cambian.

### Causas Posibles

#### 1. Ruta de destino incorrecta
**Solución:**
```bash
# Verificar el secret HOSTINGER_PATH
# Debe ser la ruta COMPLETA:
/home/u839374897/domains/DOMINIO.batidospitaya.com/public_html

# Verificar que la carpeta existe:
ssh -p 65002 u839374897@145.223.105.42
ls -la /home/u839374897/domains/
```

#### 2. Permisos de escritura en el servidor
**Solución:**
```bash
ssh -p 65002 u839374897@145.223.105.42

# Verificar permisos de la carpeta de destino
ls -la /home/u839374897/domains/DOMINIO.batidospitaya.com/

# Debe ser propiedad de u839374897
# Si no, contactar soporte de Hostinger
```

#### 3. rsync con `--delete` eliminando archivos
**Solución:**
- Verificar que la carpeta local tiene todos los archivos necesarios
- Revisar las exclusiones en el workflow
- Considerar quitar `--delete` temporalmente para debugging

---

## 🚨 Error "No such file or directory"

### Síntoma
```
rsync: mkdir "/path/to/folder" failed: No such file or directory
```

### Solución

El directorio padre no existe. Crear la estructura primero:

```yaml
# Agregar antes del rsync:
- name: 📤 Sincronizar archivos
  run: |
    # Crear estructura de carpetas
    ssh -o StrictHostKeyChecking=no -p 65002 \
      ${{ secrets.HOSTINGER_USER }}@${{ secrets.HOSTINGER_HOST }} \
      "mkdir -p ${{ secrets.HOSTINGER_PATH }}/carpeta/subcarpeta"
    
    # Luego hacer rsync
    rsync -avz ...
```

---

## 🚨 Workflow Muy Lento

### Síntoma
El deploy tarda más de 5 minutos.

### Causas Posibles

#### 1. Sincronizando demasiados archivos
**Solución:**
- Agregar más exclusiones:
```yaml
rsync -avz \
  --exclude='uploads/' \
  --exclude='node_modules/' \
  --exclude='.git/' \
  --exclude='*.log' \
  ...
```

#### 2. Usando `--delete` con muchos archivos
**Solución:**
- Considerar no usar `--delete` si no es necesario
- O hacer limpieza manual periódica del servidor

---

## 🚨 Permisos Incorrectos Después del Deploy

### Síntoma
Archivos con permisos 600 o carpetas con 700.

### Solución

Verificar el paso de permisos en el workflow:

```yaml
- name: 🔧 Configurar permisos
  run: |
    ssh ... << 'EOF'
      cd ${{ secrets.HOSTINGER_PATH }}
      find carpeta -type d -exec chmod 755 {} \;
      find carpeta -type f -exec chmod 644 {} \;
      chmod -R 777 carpeta/uploads/ || true
    EOF
```

---

## 🚨 Carpeta `uploads/` Se Borra

### Síntoma
Los archivos subidos por usuarios desaparecen después del deploy.

### Solución

Verificar que `uploads/` está excluida:

```yaml
rsync -avz \
  --delete \
  --exclude='uploads/' \  ← CRÍTICO
  ...
```

---

## 🔍 Debugging General

### Ver Logs de GitHub Actions

1. Ir a: `https://github.com/USUARIO/REPO/actions`
2. Click en el workflow que falló
3. Click en el job que falló
4. Expandir cada paso para ver logs detallados

### Verificar Configuración SSH

```bash
# En el servidor
ssh -p 65002 u839374897@145.223.105.42

# Verificar llaves
ls -la ~/.ssh/
cat ~/.ssh/authorized_keys
cat ~/.ssh/batidospitaya-deploy.pub

# Deben coincidir
```

### Probar rsync Manualmente

```bash
# Desde tu máquina local (si tienes la clave privada)
rsync -avz \
  --dry-run \  ← Solo simular, no hacer cambios
  -e "ssh -i ~/.ssh/batidospitaya-deploy -p 65002" \
  ./carpeta/ \
  u839374897@145.223.105.42:/ruta/destino/
```

### Verificar Secrets en GitHub

1. Ir a: `https://github.com/USUARIO/REPO/settings/secrets/actions`
2. Verificar que todos los secrets existen:
   - `HOSTINGER_SSH_KEY`
   - `HOSTINGER_USER`
   - `HOSTINGER_HOST`
   - `HOSTINGER_PATH` (o variante)

---

## 🆘 Contacto de Emergencia

Si ninguna solución funciona:

1. **Revisar documentación:** [DEPLOY_SETUP.md](./DEPLOY_SETUP.md)
2. **Verificar configuración:** Comparar con un dominio que funciona (ERP o API)
3. **Logs del servidor:** Revisar `/var/log/auth.log` en Hostinger (si tienes acceso)
4. **Soporte Hostinger:** Si es problema del servidor

---

**Última actualización:** 2026-02-17
