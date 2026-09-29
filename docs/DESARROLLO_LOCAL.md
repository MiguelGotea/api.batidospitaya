# 📖 Guía de Desarrollo Local — ERP Batidos Pitaya

> Este documento cubre todo lo necesario para trabajar con el ERP de forma local, sin tocar la base de datos de producción. Incluye el flujo completo para cada tipo de usuario del equipo.

---

## 🏗️ Arquitectura del sistema

```
REPOSITORIO GIT (código puro — cero credenciales)
│
├── core/database/conexion.php     ← sin credenciales, solo lee env.php
├── core/database/env.php          ← NO existe en el repo (.gitignore)
└── .scripts/
    ├── setup_php_portable.ps1     ← onboarding automático (correr 1 vez)
    └── inicio_local.ps1           ← arranque diario

PC SERVIDOR LOCAL (red interna)
└── XAMPP → MySQL con base 'erp_local', usuario restringido

LAPTOP DEVELOPER
└── env.php generado por setup.ps1 (credenciales locales ≠ producción)

HOST HOSTINGER (producción)
└── env.php con credenciales reales (solo el admin lo sube)
```

### Principio de seguridad
- **Ninguna credencial** vive en el repositorio
- Los developers solo pueden acceder a la BD espejo local
- La BD de producción solo es accesible desde el host y por el admin
- Si `env.php` no existe → el sistema falla de forma controlada, sin fallback

---

## 👑 ROL: Administrador

> El admin es la única persona con acceso al host, a las credenciales de producción y con permisos para aprobar cambios al sistema.

### Configurar el servidor local (una sola vez)

**Requisito:** Una PC fija en la red con XAMPP instalado.

**1. Activar solo MySQL en XAMPP**
- Abrir XAMPP Control Panel
- Iniciar **MySQL** (Apache no es necesario en la PC servidor)
- Verificar que el puerto 3306 esté accesible en la red

**2. Crear la base de datos espejo**
En `http://localhost/phpmyadmin` del servidor:
```sql
CREATE DATABASE erp_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**3. Importar los datos de producción**
- Exportar desde Hostinger phpMyAdmin → base `u839374897_erp` → formato SQL
- En el phpMyAdmin del servidor local: seleccionar `erp_local` → Importar → subir el `.sql`

> El archivo `.sql` se comparte fuera del repositorio (Drive, correo). Nunca se sube a Git.

> ⚠️ **Los dumps de Hostinger pueden pesar 300–400 MB** y además usan un collation (`utf8mb4_uca1400_ai_ci`) que no existe en MariaDB 10.4 (el que trae XAMPP). Antes de importar, sigue el proceso de preparación del SQL que se detalla en la sección [Preparación del dump de Hostinger para importar en XAMPP](#-preparación-del-dump-de-hostinger-para-importar-en-xampp) más abajo.

**4. Crear el usuario restringido para developers**
```sql
CREATE USER 'erp_dev'@'%' IDENTIFIED BY 'DevLocal2025!';
GRANT ALL PRIVILEGES ON erp_local.* TO 'erp_dev'@'%';
FLUSH PRIVILEGES;
```

> Este usuario (`erp_dev`) solo tiene acceso a `erp_local`. **No tiene ningún acceso a la BD de producción.** Es la única credencial que los developers conocerán.

**5. Habilitar conexiones remotas en MySQL**
En `C:\xampp\mysql\bin\my.ini` del servidor, verificar que NO exista:
```ini
bind-address = 127.0.0.1
```
Si existe, comentarla con `#` y reiniciar MySQL.

**6. Firewall de Windows**
Permitir el puerto 3306 entrante en la PC servidor:
```powershell
# Ejecutar como Administrador en la PC servidor
New-NetFirewallRule -DisplayName "MySQL XAMPP" -Direction Inbound -Protocol TCP -LocalPort 3306 -Action Allow
```

---

### Habilitar acceso a un nuevo developer

1. **GitHub** → Settings → Collaborators → invitar el usuario de GitHub del developer
2. Enviarle **únicamente**:
   - La **IP del servidor MySQL** de la red local (ej: `192.168.1.100`)
   - En caso remoto: un dump actualizado de la BD (`.sql`)

> La contraseña del usuario `erp_dev` **no necesitas enviarla**. El script `setup_php_portable.ps1` la incluye automáticamente al crear el `env.php`. El developer nunca la ve ni la ingresa manualmente.

> El developer nunca recibirá ni verá las credenciales de producción.

---

### Flujo diario del admin

```
1. Revisar Pull Requests en GitHub
2. Probar los cambios localmente si es necesario
3. Aprobar y hacer merge a main
4. El deploy corre automáticamente (GitHub Actions / rsync)
```

---

### Subir env.php al host (producción)

El archivo `core/database/env.production.php` lo mantienes solo tú. Para deployar:

**Opción A — Manual (FTP/SSH):**
Subir `env.production.php` al servidor Hostinger renombrándolo como:
```
/public_html/core/database/env.php
```

**Opción B — GitHub Actions (futuro):**
Crear un Secret en GitHub llamado `ENV_PRODUCTION` con el contenido del archivo.
El workflow lo crea automáticamente en el host durante el deploy.

---

### Mantener la BD espejo sincronizada

Hacer esto periódicamente (semanal o antes de desarrollos importantes):

1. Exportar desde Hostinger phpMyAdmin → SQL completo
2. En phpMyAdmin del servidor local → `erp_local` → Importar el nuevo SQL
3. Compartir el dump por Drive a developers remotos si los hay

---

## 💻 ROL: Developer en red local

> Developer con acceso a la misma red WiFi/LAN del servidor local.

### Onboarding (una sola vez)

**Requisitos previos:**
- Acceso de colaborador en GitHub (te lo da el admin)
- **IP del servidor MySQL** de la red (te la da el admin — es lo único que necesitas pedirle)
- Git instalado en tu PC

> La contraseña de la BD local la maneja el script automáticamente. No tienes que pedirla ni guardarla.

**Paso 1 — Clonar el repositorio**
```powershell
git clone https://github.com/[ORG]/erp.batidospitaya.com.git
cd erp.batidospitaya.com
```

**Paso 2 — Ejecutar el setup (una sola vez)**
```powershell
.\.scripts\setup_php_portable.ps1
```

El script hace automáticamente:
- ✅ Descarga PHP portable desde php.net (~30MB, sin instalador)
- ✅ Configura las extensiones necesarias: `pdo_mysql`, `curl`, `mbstring`, `gd`, `openssl`
- ✅ Pregunta la **IP del servidor MySQL** → ingresa la IP que te dio el admin
- ✅ Crea `core/database/env.php` automáticamente con usuario y contraseña locales ya configurados

Al finalizar verás:
```
[OK] PDO
[OK] pdo_mysql
[OK] curl
[OK] mbstring
[OK] gd
[OK] openssl

Setup completado exitosamente!
Para iniciar el ERP, ejecuta: .\.scripts\inicio_local.ps1
```

---

### Trabajo diario

**Iniciar el servidor local:**
```powershell
.\.scripts\inicio_local.ps1
```
- Abre `http://localhost:8000` en el navegador automáticamente
- Queda corriendo en la terminal → **Ctrl+C para detener**
- Conectado a la BD espejo local (nunca toca producción)

**Guardar cambios y enviar para revisión:**
```powershell
.\.scripts\guardar_erp.ps1
```
- Hace commit de tus cambios
- Sube a GitHub (rama `dev`)
- Crea un Pull Request automáticamente para que el admin lo revise

**Actualizar tu código local:**
```powershell
.\.scripts\actualizar_erp.ps1
```

---

### Si cambia la IP del servidor MySQL

Editar directamente `core/database/env.php`:
```php
define('DB_HOST', '192.168.1.X');  // ← nueva IP
```

> Este archivo está en `.gitignore` — nunca se sube al repo. Solo existe en tu PC.

---

## 🌐 ROL: Developer remoto

> Developer que trabaja desde otra red (casa, otra oficina) sin acceso directo al servidor local.

### Diferencia con developer en red

El developer remoto necesita su **propio MySQL local** (XAMPP en su PC) en lugar de conectarse al servidor de la red. Todo lo demás es idéntico.

### Onboarding (una sola vez)

**Requisitos previos:**
- Acceso de colaborador en GitHub (te lo da el admin)
- XAMPP instalado en tu PC (solo para MySQL, Apache no es necesario)
- Dump de la BD `.sql` que te envía el admin

**Paso 1 — Preparar MySQL local (XAMPP)**

1. Abrir XAMPP Control Panel → iniciar **MySQL**
2. Ir a `http://localhost/phpmyadmin`
3. Crear base de datos:
   - Click **Nueva** → nombre: `erp_local` → cotejamiento: `utf8mb4_unicode_ci` → **Crear**
4. Importar el dump:
   - Click en `erp_local` → tab **Importar** → seleccionar el `.sql` que te envió el admin

   > ⚠️ Si el `.sql` pesa más de 50 MB o falla al importar, sigue el proceso completo en la sección [Preparación del dump de Hostinger para importar en XAMPP](#-preparación-del-dump-de-hostinger-para-importar-en-xampp).
5. Crear el usuario restringido (ejecutar en phpMyAdmin → SQL):
```sql
CREATE USER 'erp_dev'@'localhost' IDENTIFIED BY 'DevLocal2025!';
GRANT ALL PRIVILEGES ON erp_local.* TO 'erp_dev'@'localhost';
FLUSH PRIVILEGES;
```

**Paso 2 — Clonar el repositorio**
```powershell
git clone https://github.com/[ORG]/erp.batidospitaya.com.git
cd erp.batidospitaya.com
```

**Paso 3 — Ejecutar el setup**
```powershell
.\.scripts\setup_php_portable.ps1
```
- Cuando pregunte la IP del servidor MySQL → escribe: `localhost`
- El script crea `env.php` apuntando a tu XAMPP local

**Paso 4 — Iniciar el ERP**
```powershell
.\.scripts\inicio_local.ps1
```

### Trabajo diario

Idéntico al developer en red local. La única diferencia es que debes tener **XAMPP con MySQL activo** antes de correr `inicio_local.ps1`.

> Cuando el admin actualice la BD, te enviará un nuevo `.sql`. Importarlo en phpMyAdmin reemplazando los datos de `erp_local`.

---

## 🔄 Flujo completo de desarrollo

```
Developer                     Admin                      Host (Hostinger)
    │                           │                              │
    ├─ inicio_local.ps1         │                              │
    ├─ trabaja en local ────────┤                              │
    ├─ testea contra BD espejo  │                              │
    ├─ guardar_erp.ps1 ─────────┤                              │
    │   └─ Push a rama dev      │                              │
    │   └─ Crea Pull Request ──►│                              │
    │                           ├─ Revisa el PR                │
    │                           ├─ Prueba si es necesario      │
    │                           ├─ Aprueba → merge a main ─────┤
    │                           │                              ├─ Deploy automático
    │                           │                              ├─ Lee env.php del host
    │                           │                              └─ Usa BD producción
```

---

## 🔐 Seguridad: quién tiene acceso a qué

| | Repo GitHub | BD local (erp_dev) | BD producción | Host Hostinger |
|--|:-----------:|:------------------:|:-------------:|:--------------:|
| **Admin** | ✅ Total | ✅ | ✅ | ✅ |
| **Dev en red** | ✅ Solo dev | ✅ | ❌ | ❌ |
| **Dev remoto** | ✅ Solo dev | ✅ (su XAMPP) | ❌ | ❌ |

---

## 🛠️ Solución de problemas

### ❌ "env.php no encontrado" al abrir el ERP
**Causa:** No se ejecutó el setup o se eliminó el archivo.  
**Solución:** Correr `.\.scripts\setup_php_portable.ps1` nuevamente.

### ❌ "Error de conexión LOCAL: could not find driver"
**Causa:** Extensión `pdo_mysql` no habilitada.  
**Solución:** Volver a correr `setup_php_portable.ps1` y verificar `[OK] pdo_mysql`.

### ❌ "Connection refused" al conectar a la BD
**Causa:** MySQL no está activo o la IP es incorrecta.  
**Soluciones:**
1. Verificar que XAMPP esté corriendo con MySQL en verde (PC servidor o tu propia PC)
2. Revisar la IP en `core/database/env.php` con `ipconfig` en la PC servidor

### ❌ Pantalla en blanco o error 500
**Causa:** Extensión PHP faltante o error en el código.  
**Solución:** Revisar la terminal donde corre `inicio_local.ps1` — ahí aparece el error real.

### ❌ El puerto 8000 ya está en uso
**Causa:** Otra aplicación usa ese puerto.  
**Solución:** El script detecta esto automáticamente y usa el puerto 8001.

### ❌ La sesión se cierra sola constantemente
**Causa:** La carpeta de sesiones no tiene permisos de escritura.  
**Solución:** Verificar que exista `.scripts\php-portable\sessions\` con permisos de escritura.

---

## 📦 Preparación del dump de Hostinger para importar en XAMPP

> **Contexto:** Los exports de Hostinger phpMyAdmin pueden pesar 300–400 MB y usan características de MariaDB 11.3+ que no existen en MariaDB 10.4 (XAMPP). Este proceso se aplica tanto al servidor local de la red como al XAMPP de un developer remoto.

### Paso A — Ampliar límites para archivos grandes

**1. Editar `C:\xampp\php\php.ini`** (en XAMPP: *Config → Apache → PHP (php.ini)*):
```ini
upload_max_filesize = 512M
post_max_size       = 512M
max_execution_time  = 1800
max_input_time      = 1800
memory_limit        = 1024M
```

**2. Editar `C:\xampp\mysql\bin\my.ini`**, bajo la sección `[mysqld]`:
```ini
[mysqld]
max_allowed_packet      = 256M
innodb_buffer_pool_size = 512M
innodb_flush_log_at_trx_commit = 2
```

**3. Editar `C:\xampp\phpMyAdmin\config.inc.php`**, al final del archivo:
```php
$cfg['ExecTimeLimit'] = 0;
```

**4. Reiniciar Apache y MySQL** desde el XAMPP Control Panel.

---

### Paso B — Corregir el collation incompatible (`uca1400`)

**Problema:** El dump usa `utf8mb4_uca1400_ai_ci`, que solo existe en MariaDB 11.3+. XAMPP trae MariaDB 10.4 y lo rechaza.

**Solución:** Reemplazar el collation en el archivo antes de importar. El siguiente script lee y escribe línea por línea para no cargar los 300+ MB en memoria:

```powershell
# PowerShell — reemplaza collation uca1400 → unicode_ci
$src = "C:\Users\migue\Downloads\u839374897_erp.sql"
$dst = "C:\Users\migue\Downloads\u839374897_erp_local.sql"
$enc = New-Object System.Text.UTF8Encoding($false)
$reader = New-Object System.IO.StreamReader($src, $enc)
$writer = New-Object System.IO.StreamWriter($dst, $false, $enc)
$writer.NewLine = "`n"
while (($line = $reader.ReadLine()) -ne $null) {
    if ($line.Contains('uca1400')) {
        $line = $line -replace 'utf8mb4_uca1400_\w+', 'utf8mb4_unicode_ci' `
                      -replace 'utf8mb3_uca1400_\w+', 'utf8mb3_unicode_ci'
    }
    $writer.WriteLine($line)
}
$reader.Close(); $writer.Close()
```

> Ajusta las rutas `$src` y `$dst` según donde hayas guardado el dump.

---

### Paso C — Eliminar DEFINER y referencias GTID

**Problema:** El dump incluye `DEFINER=usuario@host` y variables `SQL_LOG_BIN`/`GTID_PURGED` propias del servidor de Hostinger. En MariaDB 10.4 local producen errores de permisos.

```powershell
# PowerShell — elimina DEFINER y referencias GTID
$src = "C:\Users\migue\Downloads\u839374897_erp_local.sql"
$dst = "C:\Users\migue\Downloads\u839374897_erp_clean.sql"
$enc = New-Object System.Text.UTF8Encoding($false)
$reader = New-Object System.IO.StreamReader($src, $enc)
$writer = New-Object System.IO.StreamWriter($dst, $false, $enc)
$writer.NewLine = "`n"
while (($line = $reader.ReadLine()) -ne $null) {
    if ($line -match 'SQL_LOG_BIN|GTID_PURGED') { continue }
    if ($line.Contains('DEFINER')) {
        $line = $line -replace '/\*!\d+\s+DEFINER=`[^`]+`@`[^`]+`\s*\*/', '' `
                      -replace 'DEFINER=`[^`]+`@`[^`]+`\s*', ''
    }
    $writer.WriteLine($line)
}
$reader.Close(); $writer.Close()
```

---

### Paso D — Corregir sintaxis de VISTAs

**Problema:** Algunas VISTAs en el dump tienen `UNION ALL SELECT` sin espacio entre palabras, lo que falla en el parser local.

```powershell
# PowerShell — corrige espaciado en UNION ALL en todo el archivo
$src = "C:\Users\migue\Downloads\u839374897_erp_clean.sql"
$dst = "C:\Users\migue\Downloads\vistas_fix.sql"
$enc = New-Object System.Text.UTF8Encoding($false)
$writer = New-Object System.IO.StreamWriter($dst, $false, $enc)
$writer.NewLine = "`n"
foreach ($l in [System.IO.File]::ReadLines($src)) {
    $l = $l -replace '(?i)(\S)(union\s+all)(select)', '$1 $2 $3'
    $l = $l -replace '(?i)(\S)(union\s+all)\s',       '$1 $2 '
    $l = $l -replace '(?i)\s(union\s+all)(select)',   ' $1 $2'
    $writer.WriteLine($l)
}
$writer.Close()
```

> **Nota:** El archivo de salida `vistas_fix.sql` es una copia completa del archivo de entrada con las correcciones aplicadas. No omite ninguna línea.

---

### Paso E — Verificar que el archivo final está limpio

Antes de importar, confirma que no quedaron referencias problemáticas:

```powershell
# Ninguno de estos debe devolver resultados
Select-String -Path "C:\Users\migue\Downloads\vistas_fix.sql" -Pattern "uca1400"    -List
Select-String -Path "C:\Users\migue\Downloads\vistas_fix.sql" -Pattern "DEFINER="   -List
Select-String -Path "C:\Users\migue\Downloads\vistas_fix.sql" -Pattern "CREATE DATABASE|^USE " -List
```

Si no imprime nada, el archivo está listo para importar.


### Resumen del flujo de preparación

```
Hostinger export
  └─ u839374897_erp.sql  (puede pesar 400–900+ MB, collation uca1400)
        │
        ▼  Paso B: reemplazar collation
  u839374897_erp_local.sql
        │
        ▼  Paso C: eliminar DEFINER/GTID
  u839374897_erp_clean.sql
        │
        ▼  Paso D: corregir VISTAs
  vistas_fix.sql  ← IMPORTAR ESTE (ver método recomendado abajo)
```

> ⚠️ Los scripts PowerShell de conversión (Pasos B–D) **no tienen problemas con archivos grandes**: usan lectura línea por línea (`StreamReader` / `ReadLines`), por lo que el consumo de memoria es constante (~pocos MB) sin importar si el dump pesa 400 MB o 1 GB.

---

### 📥 Cómo importar el archivo final

**phpMyAdmin tiene un límite real:** aunque se configuren `upload_max_filesize = 512M` en `php.ini`, PHP necesita recibir el upload completo en memoria antes de procesarlo. Con archivos de 900 MB o más, esto falla de forma poco predecible (timeout del navegador, falta de RAM, corte mid-import).

#### ✅ Método recomendado: `mysql.exe` desde línea de comandos

```powershell
# Importar directamente — sin límites de tamaño ni de tiempo
& "C:\xampp\mysql\bin\mysql.exe" -u root -p erp_local < "C:\Users\migue\Downloads\vistas_fix.sql"
```

- **Sin límites de memoria ni de tiempo:** stream directo al motor MySQL
- **Funciona para cualquier tamaño:** 400 MB, 900 MB, 2 GB — igual
- **Más rápido** que phpMyAdmin para archivos grandes
- Si MySQL pide contraseña y el root local no tiene, omite `-p`

#### 🔶 Método alternativo: phpMyAdmin (solo para archivos < 200 MB)

Solo útil si el dump es pequeño. Con los cambios del Paso A aplicados:
- `erp_local` → tab **Importar** → seleccionar `vistas_fix.sql`

> Una vez que el dump supere 200–300 MB, usa siempre `mysql.exe`.


---

## 📁 Estructura de archivos relevantes

```
erp.batidospitaya.com\
├── core\
│   └── database\
│       ├── conexion.php           ← sin credenciales, lee env.php
│       ├── env.php                ← NO en Git, generado por setup.ps1
│       └── env.production.php     ← NO en Git, solo el admin lo tiene
├── .scripts\
│   ├── php-portable\              ← NO en Git, descargado por setup.ps1
│   │   ├── php.exe
│   │   ├── php.ini
│   │   └── sessions\
│   ├── setup_php_portable.ps1    ← onboarding (ejecutar 1 vez)
│   ├── inicio_local.ps1          ← arranque diario
│   ├── guardar_erp.ps1           ← commit + push + PR
│   └── actualizar_erp.ps1        ← sincronizar cambios
├── docs\
│   └── DESARROLLO_LOCAL.md       ← este archivo
└── .gitignore                    ← excluye env.php, php-portable, *.sql
```
