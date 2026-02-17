# 📦 Scripts de Deploy - Guía de Uso

Esta carpeta contiene los scripts de PowerShell para hacer deploy rápido de cada módulo del ERP.

## 🚀 Uso desde la Terminal

### Desde la raíz del proyecto:
```powershell
.\.scripts\gitpush-mantenimiento.ps1
.\.scripts\gitpush-sistemas.ps1
.\.scripts\gitpush-gerencia.ps1
# ... etc
```

### Desde cualquier ubicación:
```powershell
cd "c:\Users\migue\Desktop\Sistema\Pitaya Web\VisualCode\erp.batidospitaya.com"
.\.scripts\gitpush-[nombre-modulo].ps1
```

## 📋 Scripts Disponibles

- `gitpush-atencioncliente.ps1`
- `gitpush-auxiliaradministrativo.ps1`
- `gitpush-cds.ps1`
- `gitpush-desarrollo.ps1`
- `gitpush-diseno.ps1`
- `gitpush-experienciadigital.ps1`
- `gitpush-gerencia.ps1`
- `gitpush-infraestructura.ps1`
- `gitpush-legal.ps1`
- `gitpush-mantenimiento.ps1`
- `gitpush-marketing.ps1`
- `gitpush-produccion.ps1`
- `gitpush-rh.ps1`
- `gitpush-sistemas.ps1`
- `gitpush-sucursales.ps1`
- `gitpush-tecnicodesarrollohumano.ps1`
- `gitpush-ventas.ps1`
- `gitpush.ps1` (sube todos los cambios)

## 🔄 Sincronización en Hostinger (Reset)

Si necesitas forzar que el servidor de Hostinger se iguale exactamente a lo que hay en GitHub (útil si hay conflictos o archivos nuevos de configuración):

```bash
cd ~/domains/erp.batidospitaya.com/public_html
git fetch origin main
git reset --hard origin/main
```

## ⚡ Tip: Crear Alias (Opcional)

Para hacer los comandos más cortos, puedes agregar esto a tu perfil de PowerShell:

```powershell
# Abrir perfil
notepad $PROFILE

# Agregar estas líneas:
function gpm { .\.scripts\gitpush-mantenimiento.ps1 }
function gps { .\.scripts\gitpush-sistemas.ps1 }
function gpg { .\.scripts\gitpush-gerencia.ps1 }
# ... etc

# Guardar y recargar
. $PROFILE
```

Luego solo escribes `gpm` para subir mantenimiento, `gps` para sistemas, etc.
