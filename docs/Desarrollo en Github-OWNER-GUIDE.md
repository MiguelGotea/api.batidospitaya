# 👑 Guía del Administrador — ERP Batidos Pitaya
> Repositorio GitHub + GitHub Actions + Deploy a Hostinger

---

## 1. Configuración inicial (una sola vez)

### 1.1 Proteger la rama `main`

Ve a tu repositorio en GitHub:

```
Settings → Branches → Add branch protection rule
```

| Campo | Valor |
|---|---|
| Branch name pattern | `main` |
| Require a pull request before merging | ✅ ON |
| Required approvals | `1` |
| Dismiss stale reviews when new commits are pushed | ✅ ON |
| **Do not allow bypassing the above settings** | ❌ **OFF** (Desmarcado) ← Crítico para que tú puedas hacer push directo |
| Restrict who can push to matching branches | ✅ ON (Si está disponible) |

> [!NOTE]
> Dejar **"Do not allow bypassing..."** desmarcado es lo que permite que seas "administrador" y puedas subir cambios sin pasar por Pull Request. Si lo marcas, incluso tú quedarás bloqueado.

Guarda con **"Create"**.

---

### 1.2 Invitar al desarrollador

```
Settings → Collaborators → Add people
```

- Busca su usuario de GitHub
- Rol: **Write**
- El desarrollador recibirá un email de invitación que debe aceptar antes de poder clonar o hacer push

---

### 1.3 Crear la rama `dev`

Desde tu máquina:

```bash
git checkout -b dev
git push origin dev
```

O desde GitHub: en el selector de ramas escribe `dev` → **"Create branch dev from main"**

---

### 1.4 Verificar el workflow

Tu archivo `.github/workflows/deploy.yml` ya está correcto. Confirma que diga:

```yaml
on:
  push:
    branches: [ main ]
```

El desarrollador **nunca** podrá disparar el deploy — solo ocurre cuando tú mergeas a `main`.

---

### 1.5 Revisar scripts sensibles en el repo

Antes de que el desarrollador clone, verifica que ningún script `.ps1` u otro archivo tenga credenciales hardcodeadas:

```powershell
# Busca esto en tus archivos:
$password = "..."
$host = "IP-real"
$connectionString = "..."
```

Si existen, agrégalos al `.gitignore` y elimínalos del tracking:

```bash
git rm -r --cached .scripts/
git commit -m "Elimina scripts internos del tracking"
git push origin main
```

---

## 2. Tu flujo diario de trabajo (no cambia nada)

Tu script PowerShell sigue funcionando exactamente igual. Como administrador estás exento de la protección de rama.

```powershell
# Tu script actual — sin cambios
git add .
git commit -m "..."
git pull origin main --rebase
git push origin main   # ← dispara Actions → deploy al host
```

---

## 3. Cómo revisar y aprobar el trabajo del desarrollador

### 3.1 Dónde ver los cambios

Cuando el desarrollador abra un Pull Request verás:

```
Tu repo en GitHub → pestaña "Pull Requests" → el PR abierto
```

Dentro del PR, ve a la pestaña **"Files changed"** para ver:
- 🟢 Líneas agregadas (verde)
- 🔴 Líneas eliminadas (rojo)
- Cada archivo modificado con su diff completo

---

### 3.2 Cómo revisar correctamente

**Presta especial atención a archivos en rojo completo** (archivos eliminados). Con el `--delete` en tu rsync, si el desarrollador borra un archivo y tú lo mergeas → **se borra en producción**.

Puedes:
- **Comentar línea por línea** — haz clic en el `+` que aparece al pasar el cursor sobre cualquier línea
- **Pedir cambios** — botón "Request changes" para que el desarrollador corrija antes de mergear
- **Aprobar** — botón "Approve" cuando todo esté correcto

---

### 3.3 Hacer el merge (deploy a producción)

Una vez aprobado:

1. Click en **"Merge pull request"**
2. Click en **"Confirm merge"**
3. GitHub Actions se dispara automáticamente
4. El rsync sincroniza los cambios al host de Hostinger
5. Listo — cambios en producción en ~1-2 minutos

---

## 4. Resumen visual del flujo

```
Desarrollador              GitHub                    Tú                Hostinger
     │                        │                       │                    │
     │── push a dev ─────────►│                       │                    │
     │                        │── notificación ───────►│                    │
     │                        │     (PR abierto)       │                    │
     │                        │                       │ revisas en         │
     │                        │                       │ "Files changed"    │
     │                        │◄── merge a main ──────│                    │
     │                        │                       │                    │
     │                        │──── Actions rsync ────────────────────────►│
     │                        │                       │              deploy listo
```

---

## 5. Qué puede y qué NO puede hacer el desarrollador

| Acción | ¿Puede? |
|---|---|
| Clonar el repo | ✅ Sí |
| Hacer push a la rama `dev` | ✅ Sí |
| Crear ramas nuevas | ✅ Sí |
| Abrir Pull Requests | ✅ Sí |
| Hacer push directo a `main` | ❌ Bloqueado por protección |
| Mergear su propio PR | ❌ Requiere tu aprobación |
| Disparar el deploy (Actions) | ❌ Imposible sin merge a `main` |
| Ver los Secrets de GitHub | ❌ Nunca se exponen |

---

## 6. Secretos de GitHub Actions (no tocar)

Los secrets están en:
```
Settings → Secrets and variables → Actions
```

| Secret | Descripción |
|---|---|
| `HOSTINGER_SSH_KEY` | Llave privada SSH para conectar al host |
| `HOSTINGER_USER` | Usuario SSH de Hostinger |
| `HOSTINGER_HOST` | IP o dominio del servidor |
| `HOSTINGER_PATH` | Ruta absoluta en el servidor |

> ⚠️ Nunca compartas estos valores ni los pongas en archivos del repo.
