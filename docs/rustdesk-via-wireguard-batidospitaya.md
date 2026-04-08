# RustDesk Servidor Propio — Batidos Pitaya
**VPS:** DigitalOcean — Ubuntu 24.04 — `198.211.97.243`  
**Red WireGuard:** `10.66.66.0/24`  
**Fecha de instalación:** Abril 2026

---

## Arquitectura

```
Tu PC (administrador)
        │
        │ RustDesk cliente → apunta a tu VPS
        ▼
VPS 198.211.97.243
├── WireGuard  (red 10.66.66.x)         puerto 49966 UDP
├── RustDesk hbbs (señalización)         puertos 21115-21117 TCP/UDP
└── RustDesk hbbr (relay de tráfico)     puerto 21119 TCP
        │
        │ Túnel WireGuard ya activo
        ▼
PC Sucursal (colaborador)
└── RustDesk cliente instalado como servicio
    └── Apunta a tu VPS como servidor
```

---

## Registro de IDs por sucursal

> Completar esta tabla conforme se configura cada sucursal.

| Sucursal | IP WireGuard | ID RustDesk | Contraseña RustDesk |
|---|---|---|---|
| pitaya | 10.66.66.2 | | |
| tienda-altamira | 10.66.66.3 | | |
| tienda-villafontana | 10.66.66.4 | | |
| tienda-natura | 10.66.66.5 | | |
| _(siguiente)_ | 10.66.66.6 | | |
| _(siguiente)_ | 10.66.66.7 | | |
| _(siguiente)_ | 10.66.66.8 | | |
| _(siguiente)_ | 10.66.66.9 | | |
| _(siguiente)_ | 10.66.66.10 | | |
| _(siguiente)_ | 10.66.66.11 | | |
| _(siguiente)_ | 10.66.66.12 | | |
| _(siguiente)_ | 10.66.66.13 | | |
| _(siguiente)_ | 10.66.66.14 | | |
| _(siguiente)_ | 10.66.66.15 | | |
| _(siguiente)_ | 10.66.66.16 | | |

---

## Clave pública del servidor RustDesk

> Completar después de la instalación (Parte 1 — Paso 6).

```
Clave pública: _________________________________
```

---

## PARTE 1 — Instalar RustDesk Server en el VPS

### Paso 1 — Conectarse al VPS

```bash
ssh root@198.211.97.243
```

---

### Paso 2 — Descargar RustDesk Server

```bash
cd /opt
wget https://github.com/rustdesk/rustdesk-server/releases/latest/download/rustdesk-server-linux-amd64.zip
apt install unzip -y
unzip rustdesk-server-linux-amd64.zip
chmod +x hbbs hbbr
```

---

### Paso 3 — Crear usuario dedicado para RustDesk

```bash
useradd -m -r rustdesk
mv /opt/hbbs /opt/hbbr /home/rustdesk/
chown rustdesk:rustdesk /home/rustdesk/hbbs /home/rustdesk/hbbr
```

---

### Paso 4 — Crear servicio hbbs (señalización)

```bash
nano /etc/systemd/system/rustdesk-hbbs.service
```

Pegar el siguiente contenido:

```ini
[Unit]
Description=RustDesk Signal Server
After=network.target

[Service]
User=rustdesk
WorkingDirectory=/home/rustdesk
ExecStart=/home/rustdesk/hbbs -r 198.211.97.243
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

---

### Paso 5 — Crear servicio hbbr (relay)

```bash
nano /etc/systemd/system/rustdesk-hbbr.service
```

Pegar el siguiente contenido:

```ini
[Unit]
Description=RustDesk Relay Server
After=network.target

[Service]
User=rustdesk
WorkingDirectory=/home/rustdesk
ExecStart=/home/rustdesk/hbbr
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

---

### Paso 6 — Iniciar los servicios

```bash
systemctl daemon-reload
systemctl enable rustdesk-hbbs rustdesk-hbbr
systemctl start rustdesk-hbbs rustdesk-hbbr

# Verificar que están corriendo
systemctl status rustdesk-hbbs
systemctl status rustdesk-hbbr
```

---

### Paso 7 — Obtener y guardar la clave pública

```bash
cat /home/rustdesk/*.pub
```

Copiar la clave que aparece y guardarla en la tabla de **"Clave pública del servidor"** al inicio de este documento. Se necesita para configurar todos los clientes.

---

### Paso 8 — Configurar firewall (solo acceso desde red WireGuard)

```bash
ufw allow from 10.66.66.0/24 to any port 21115 proto tcp
ufw allow from 10.66.66.0/24 to any port 21116 proto tcp
ufw allow from 10.66.66.0/24 to any port 21116 proto udp
ufw allow from 10.66.66.0/24 to any port 21117 proto tcp
ufw allow from 10.66.66.0/24 to any port 21119 proto tcp
ufw reload
```

> ⚠️ Al restringir los puertos solo a la red WireGuard (`10.66.66.0/24`), nadie fuera del VPN puede intentar conectarse al servidor RustDesk.

---

## PARTE 2 — Configurar RustDesk en cada PC (sucursales y PC administrador)

> Repetir estos pasos en cada PC. Empezar siempre con la PC administrador para verificar que el servidor funciona antes de desplegar en sucursales.

### Paso 1 — Descargar RustDesk cliente

Ir a: **https://rustdesk.com/download**  
Descargar la versión **Windows 64-bit** e instalar normalmente.

---

### Paso 2 — Apuntar al servidor propio

1. Abrir RustDesk
2. Clic en el ícono **⚙️** (esquina superior derecha)
3. Ir a **"Red"**
4. Activar **"Usar servidor propio"**
5. Llenar los campos:

```
Servidor ID (hbbs):    198.211.97.243
Servidor Relay (hbbr): 198.211.97.243
Clave:                 (la clave pública guardada en Paso 7 de Parte 1)
```

6. Clic en **Aplicar y reiniciar**

---

### Paso 3 — Instalar como servicio del sistema (en sucursales)

Para que RustDesk corra aunque nadie haya iniciado sesión en Windows:

```
RustDesk → Configuración → General →
Activar "Iniciar al arrancar Windows (todos los usuarios)"
```

Esto instala RustDesk como servicio del sistema — siempre disponible e invisible para el colaborador.

---

### Paso 4 — Configurar contraseña fija (en sucursales)

Para no depender de contraseñas temporales que cambian:

```
RustDesk → Configuración → Seguridad →
"Contraseña permanente" → Ingresar una contraseña fija
```

> Usar una contraseña diferente por cada sucursal y registrarla en la tabla al inicio de este documento.

---

### Paso 5 — Registrar el ID de la sucursal

En la pantalla principal de RustDesk aparece el **ID numérico** de esa PC (ej: `123 456 789`).  
Anotarlo en la tabla de la sección **"Registro de IDs por sucursal"** al inicio de este documento.

---

## PARTE 3 — Conectarse a una sucursal (uso diario)

Desde **tu PC administrador** con RustDesk abierto y WireGuard activo:

1. Escribir el **ID** de la sucursal en el campo **"ID remoto"**
2. Clic en **Conectar**
3. Ingresar la contraseña de esa sucursal
4. Control remoto completo del escritorio

> ⚠️ Tu PC debe tener WireGuard activo para poder conectarse — los puertos de RustDesk solo aceptan conexiones desde la red `10.66.66.0/24`.

---

## PARTE 4 — Mantenimiento del servidor

### Ver estado de los servicios

```bash
systemctl status rustdesk-hbbs
systemctl status rustdesk-hbbr
```

### Ver logs en tiempo real

```bash
journalctl -u rustdesk-hbbs -f
journalctl -u rustdesk-hbbr -f
```

### Reiniciar si hay problemas

```bash
systemctl restart rustdesk-hbbs rustdesk-hbbr
```

### Si el VPS se reinicia

Los servicios arrancan automáticamente — no se requiere intervención manual.

### Si una sucursal no aparece disponible

Verificar en orden:
1. ¿WireGuard está activo en esa PC? → debe estar verde en la app
2. ¿RustDesk está corriendo en esa PC? → verificar en el administrador de tareas
3. ¿El servicio hbbs está corriendo en el VPS? → `systemctl status rustdesk-hbbs`
4. Reiniciar RustDesk en la PC de la sucursal

---

## Resumen de puertos del VPS

| Puerto | Protocolo | Servicio | Acceso |
|---|---|---|---|
| 49966 | UDP | WireGuard | Público |
| 21115 | TCP | RustDesk hbbs | Solo red WireGuard |
| 21116 | TCP/UDP | RustDesk hbbs | Solo red WireGuard |
| 21117 | TCP | RustDesk hbbs | Solo red WireGuard |
| 21119 | TCP | RustDesk hbbr | Solo red WireGuard |
| 80/443 | TCP | Nginx | Público |

---

## Orden de implementación

- [x] WireGuard instalado y funcionando
- [ ] RustDesk Server instalado en el VPS (Parte 1)
- [ ] RustDesk configurado en PC administrador y verificado
- [ ] RustDesk desplegado en sucursales conforme se configuran con WireGuard
- [ ] Tabla de IDs y contraseñas completa

---

*Documento generado: Abril 2026 — Batidos Pitaya*
