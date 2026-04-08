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
bash wireguard-install.sh
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

| # | Nombre del cliente | IP asignada | Archivo |
|---|---|---|---|
| 1 | `pitaya` | `10.66.66.2` | `/root/wg0-client-pitaya.conf` |
| 2 | `tienda-altamira` | `10.66.66.3` | `/root/tienda-altamira.conf` |
| 3 | `tienda-villafontana` | `10.66.66.4` | `/root/tienda-villafontana.conf` |
| 4 | `tienda-natura` | `10.66.66.5` | `/root/tienda-natura.conf` |
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

## PARTE 2 — Configuración en cada PC de sucursal (Windows)

> Repetir estos pasos en cada computadora de sucursal.

### 2.1 Instalar WireGuard para Windows

1. Ir a: **https://www.wireguard.com/install/**
2. Descargar el instalador para Windows
3. Instalar normalmente (siguiente → siguiente → finalizar)

---

### 2.2 Obtener el archivo .conf de la sucursal

En el VPS, ejecutar el siguiente comando según la sucursal que se va a configurar:

```bash
cat /root/tienda-altamira.conf
```

Aparecerá algo similar a esto (cada sucursal tiene sus propias claves únicas):

```ini
[Interface]
PrivateKey = <clave privada única de esta sucursal>
Address = 10.66.66.3/32,fd42:42:42::3/128
DNS = 1.1.1.1,1.0.0.1

[Peer]
PublicKey = 13V3BxymzYdB2hH7vV0mv052YpPiT7hbydJZBtLFJFY=
PresharedKey = <clave compartida única de esta sucursal>
Endpoint = 198.211.97.243:49966
AllowedIPs = 0.0.0.0/0,::/0
```

> ⚠️ Nunca compartir el mismo `.conf` entre dos PCs — cada sucursal debe tener el suyo propio.

**Pasos:**
1. Seleccionar y copiar todo el contenido
2. En la PC de la sucursal, abrir el **Bloc de notas**
3. Pegar el contenido
4. Guardar el archivo como `tienda-altamira.conf`  
   *(importante: extensión `.conf`, no `.txt`)*

---

### 2.3 Importar el perfil en WireGuard

1. Abrir la app **WireGuard** en Windows
2. Clic en **"Import tunnel(s) from file"**
3. Seleccionar el archivo `.conf` guardado
4. El túnel aparece en la lista con el nombre de la sucursal

---

### 2.4 Activar la conexión

- Clic en **"Activate"**
- En 2-3 segundos el túnel se pone en **verde** ✅
- Desde ese momento todo el tráfico sale por la IP del VPS

**Verificar que funciona:**  
Abrir el navegador e ir a **https://whatismyip.com**  
Debe mostrar la IP del VPS de DigitalOcean — no la IP de Claro.

---

### 2.5 Inicio automático con Windows (recomendado)

Para que el VPN siempre esté activo al encender la PC:

1. En la app WireGuard, clic derecho sobre el túnel
2. Seleccionar **"Start on Login"**

Así las sucursales nunca tienen que recordar activarlo manualmente.

---

### 2.6 Desactivar cuando no se necesite

- Clic en **"Deactivate"** en la app WireGuard
- La conexión vuelve a usar la IP normal de Claro

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

1. **¿La app WireGuard está instalada?** → Verificar en la PC
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
