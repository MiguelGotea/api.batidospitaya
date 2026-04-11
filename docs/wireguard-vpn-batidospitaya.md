# WireGuard VPN — Batidos Pitaya
**VPS:** DigitalOcean — Ubuntu 24.04 — 2GB RAM  
**Puerto WireGuard:** `49966/UDP`  
**Cliente principal:** `wg0-client-pitaya`  
**Fecha de instalación:** Abril 2026

---

## PARTE 1 — Servidor VPS (Historial de instalación)

> ⚠️ Guardar este documento ante cualquier reinicio o reinstalación del VPS.

### 1.1 Instalación de WireGuard

```bash
wget https://raw.githubusercontent.com/angristan/wireguard-install/master/wireguard-install.sh
chmod +x wireguard-install.sh
bash wireguard-install.sh (repetir para crear nuevo usuario)
sed -i 's|AllowedIPs = 0.0.0.0/0,::/0|AllowedIPs = 0.0.0.0/1, 128.0.0.0/1, ::/1, 8000::/1|g' /root/wg0-client-contabilidad.conf && echo 'Listo:' && grep AllowedIPs /root/wg0-client-contabilidad.conf
```

**Powershell (descargar archivo .conf)**
```bash
scp root@198.211.97.243:/root/wg0-client-contabilidad.conf "C:\Users\migue\Google Drive BP\Sistema Ultima Version\Llaves WireGuard\wg0-client-contabilidad.conf"
```

**Respuestas usadas durante la instalación:**
| Pregunta | Valor usado |
|---|---|
| IPv4 or IPv6 public address | `198.211.97.243` (automática) |
| Public interface | `eth0` |
| WireGuard interface name | `wg0` |
| Server WireGuard IPv4 | `10.66.66.1` |
| Server WireGuard IPv6 | `fd42:42:42::1` |
| Server WireGuard port [1-65535] | `49966` |
| First DNS resolver | `1.1.1.1` |
| Second DNS resolver (optional) | `1.0.0.1` |
| AllowedIPs for clients | `0.0.0.0/0,::/0` (default, Enter) |
| Primer cliente — Client name | `pitaya` |
| Client WireGuard IPv4 | `10.66.66.2` (automática) |
| Client WireGuard IPv6 | `fd42:42:42::2` (automática) |

> ⚠️ El nombre del cliente tiene límite de **15 caracteres**, solo letras, números, guiones y guiones bajos.

---

### 1.2 Firewall

```bash
ufw allow 49966/udp
ufw reload
```

---

### 1.3 Verificar que WireGuard está activo

```bash
systemctl status wg-quick@wg0
```
Debe mostrar `active (running)`.

---

### 1.4 Clientes creados

```bash
bash wireguard-install.sh
opcion 2
```
 
| # | Nombre del cliente | IP asignada | Archivo |
|---|---|---|---|
| 1 | `main` | `10.66.66.2` | `/root/wg0-client-pitaya.conf` |
| 2 | `altamira` | `10.66.66.3` | `/root/wg0-client-altamira.conf` |
| 3 | `villafontana` | `10.66.66.4` | `/root/wg0-client-villafontana.conf` |
| 4 | `natura` | `10.66.66.5` | `/root/wg0-client-natura.conf` |
| 5 | _(siguiente)_ | `10.66.66.6` | `/root/nombre.conf` |
| 6 | _(siguiente)_ | `10.66.66.7` | `/root/nombre.conf` |
| 7 | _(siguiente)_ | `10.66.66.8` | `/root/nombre.conf` |
| 8 | _(siguiente)_ | `10.66.66.9` | `/root/nombre.conf` |
| 9 | _(siguiente)_ | `10.66.66.10` | `/root/nombre.conf` |
| 10 | _(siguiente)_ | `10.66.66.11` | `/root/nombre.conf` |
| 11 | _(siguiente)_ | `10.66.66.12` | `/root/nombre.conf` |
| 12 | _(siguiente)_ | `10.66.66.13` | `/root/nombre.conf` |
| 13 | _(siguiente)_ | `10.66.66.14` | `/root/nombre.conf` |
| 14 | _(siguiente)_ | `10.66.66.15` | `/root/nombre.conf` |
| 15 | _(siguiente)_ | `10.66.66.16` | `/root/nombre.conf` |

> Actualizar esta tabla cada vez que se agregue una sucursal nueva.

---

### 1.5 Comandos útiles del servidor

```bash
# Ver estado y sucursales conectadas en tiempo real
wg show

# Ver configuración completa
cat /etc/wireguard/wg0.conf

# Ver archivo de un cliente específico
cat /root/tienda-altamira.conf

# Reiniciar WireGuard manualmente
systemctl restart wg-quick@wg0

# Ver si WireGuard arranca automático con el sistema
systemctl is-enabled wg-quick@wg0
```

---

### 1.6 Si el VPS se reinstala desde cero

Repetir desde el **Paso 1.1**. WireGuard arranca automáticamente tras la instalación y después de cada reinicio del VPS — no requiere intervención manual.

---

## PARTE 2 — Configuración en cada PC de sucursal (Windows) con WireSock

> Repetir estos pasos en cada computadora de sucursal.  
> Se usa **WireSock Secure Connect** en lugar de la app oficial de WireGuard porque permite excluir programas específicos (como Google Drive) del túnel VPN, evitando consumo excesivo del plan de DigitalOcean.

---

### 2.1 Instalar WireSock Secure Connect

1. Ir a: **https://www.wiresock.net/wiresock-secure-connect/download**
2. Descargar la versión correspondiente a la arquitectura del equipo:
   - **Windows 64-bit** → opción más común (sistemas modernos)
   - **Windows 32-bit** → sistemas más antiguos
   - **Windows ARM64** → equipos con procesador ARM
3. Alternativamente, instalar con **Winget** desde PowerShell:
   ```powershell
   winget install NTKERNEL.WireSockVPNClient
   ```
4. Ejecutar el instalador y seguir los pasos (siguiente → siguiente → finalizar)

---

### 2.2 Obtener el archivo .conf de la sucursal

El archivo `.conf` ya fue modificado en el servidor para usar `AllowedIPs` divididos, lo que permite mantener la comunicación con impresoras y dispositivos en la misma red local.

**Descargar el archivo .conf directamente desde el VPS (PowerShell):**

```powershell
scp root@198.211.97.243:/root/wg0-client-contabilidad.conf "C:\Users\migue\Google Drive BP\Sistema Ultima Version\Llaves WireGuard\wg0-client-contabilidad.conf"
```

> ⚠️ Ajustar el nombre del archivo y la ruta de destino según la sucursal que se esté configurando.

El archivo `.conf` se ve similar a esto:

```ini
[Interface]
PrivateKey = <clave privada única de esta sucursal>
Address = 10.66.66.3/32,fd42:42:42::3/128
DNS = 1.1.1.1,1.0.0.1

[Peer]
PublicKey = 13V3BxymzYdB2hH7vV0mv052YpPiT7hbydJZBtLFJFY=
PresharedKey = <clave compartida única de esta sucursal>
Endpoint = 198.211.97.243:49966
AllowedIPs = 0.0.0.0/1, 128.0.0.0/1, ::/1, 8000::/1
```

> ℹ️ El `AllowedIPs` dividido (`0.0.0.0/1, 128.0.0.0/1, ::/1, 8000::/1`) es equivalente a enrutar todo el tráfico por el VPN pero sin bloquear la conectividad con dispositivos locales como impresoras en la misma red.

> ⚠️ Nunca compartir el mismo `.conf` entre dos PCs — cada sucursal debe tener el suyo propio.

---

### 2.3 Importar el perfil en WireSock

1. Abrir la app **WireSock Secure Connect** en Windows
2. Ir a **File → Import** (o usar el botón de importar perfil en la interfaz)
3. Seleccionar el archivo `.conf` descargado
4. El perfil aparece en la lista con el nombre del archivo

---

### 2.4 Configurar procesos excluidos del VPN (Split Tunneling)

Este es el paso clave que diferencia WireSock de la app oficial. Aquí se configura **qué programas NO pasan por el VPN**, para evitar que Google Drive consuma el ancho de banda del plan de DigitalOcean.

1. En WireSock, ir al menú **Edit**
2. Seleccionar **Not-tunneled** (procesos que no usarán el VPN)
3. Clic en **Select Process**
4. Buscar y seleccionar **`GoogleDriveFS`** (o el ejecutable de Google Drive For Desktop)
5. Confirmar la selección

> ✅ A partir de este momento, Google Drive usará la conexión directa a internet (Claro) y **no** consumirá el plan del VPS de DigitalOcean.

> ℹ️ Se pueden agregar otros programas que consuman mucho ancho de banda y no necesiten pasar por el VPN, como actualizaciones de Windows o Dropbox.

---

### 2.5 Activar la conexión

1. En WireSock, seleccionar el perfil importado
2. Clic en **Connect** (o el botón de activar)
3. En pocos segundos el túnel se activa ✅
4. Los procesos configurados en "Not-tunneled" seguirán usando la IP normal de Claro

**Verificar que funciona:**  
Abrir el navegador e ir a **https://whatismyip.com**  
Debe mostrar la IP del VPS de DigitalOcean — no la IP de Claro.

---

### 2.6 Inicio automático con Windows (recomendado)

WireSock soporta inicio de la conexión antes incluso del inicio de sesión del usuario:

1. En la app WireSock, ir a las opciones del perfil
2. Activar **"Connect on startup"** o **"Start on login"**

Así las sucursales nunca tienen que recordar activarlo manualmente.

---

### 2.7 Desactivar cuando no se necesite

- En WireSock, clic en **Disconnect**
- La conexión vuelve a usar la IP normal de Claro para todo el tráfico

---

## PARTE 3 — Mantenimiento y gestión

### 3.1 Agregar una sucursal nueva

```bash
bash wireguard-install.sh
# Seleccionar: "Add a new client"
# Escribir el nombre: tienda-nueva
```

Luego actualizar la tabla de clientes en la **Sección 1.4** de este documento.

---

### 3.2 Eliminar una sucursal

```bash
bash wireguard-install.sh
# Seleccionar: "Revoke a client"
# Seleccionar el nombre de la sucursal a eliminar
```

---

### 3.3 Ver sucursales conectadas ahora mismo

```bash
wg show
```

Muestra cada cliente con su IP, cuándo se conectó por última vez y cuánto tráfico ha generado.

---

### 3.4 Si una sucursal no puede conectarse

Verificar en orden:

1. **¿La app WireSock está instalada?** → Verificar en la PC
2. **¿El túnel está activado?** → Debe estar verde en la app
3. **¿El VPS está corriendo?** → Verificar en panel de DigitalOcean
4. **¿WireGuard está activo en el VPS?**
   ```bash
   systemctl status wg-quick@wg0
   ```
5. **¿El puerto está abierto?**
   ```bash
   ufw status | grep 49966
   ```
6. **Reiniciar WireGuard en el VPS:**
   ```bash
   systemctl restart wg-quick@wg0
   ```

---

### 3.5 Monitorear consumo de bandwidth en DigitalOcean

- Ingresar al panel: **https://cloud.digitalocean.com**
- Seleccionar el Droplet
- Tab **"Graphs"** → ver transferencia del mes
- Límite incluido: **2TB/mes** — con 15 sucursales de uso normal se usa aproximadamente **400GB/mes**

---

### 3.6 Backup de los archivos .conf

Se recomienda guardar todos los archivos `.conf` en un lugar seguro fuera del VPS (Google Drive, etc.) por si el VPS se reinstala:

```bash
# Listar todos los archivos de clientes
ls /root/*.conf

# Ver contenido para copiar y guardar
cat /root/wg0-client-pitaya.conf
cat /root/tienda-altamira.conf
cat /root/tienda-villafontana.conf
cat /root/tienda-natura.conf
```

---

*Documento generado: Abril 2026 — Batidos Pitaya*
