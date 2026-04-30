# 👨‍💻 Guía del Desarrollador — ERP Batidos Pitaya
> Instrucciones para trabajar en el repositorio de forma segura y ordenada

---

## Requisitos previos

Antes de empezar asegúrate de tener:

- [ ] Git instalado en tu máquina → https://git-scm.com/downloads
- [ ] Una cuenta de GitHub
- [ ] Haber **aceptado la invitación de colaborador** que llegó a tu email
- [ ] (Opcional pero recomendado) Visual Studio Code con la extensión GitLens

---

## Primera vez — Configuración inicial

### Paso 1: Clonar el repositorio

```bash
git clone https://github.com/MiguelGotea/NOMBRE-REPO.git
cd NOMBRE-REPO
```

### Paso 2: Pararte en la rama de trabajo

```bash
git checkout dev
```

> ⚠️ **Importante:** Siempre trabajas en la rama `dev`.
> La rama `main` está protegida — si intentas hacer push directo a ella recibirás un error.

### Paso 3: Confirmar que estás en `dev`

```bash
git branch
# Debe mostrar:
# * dev
#   main
```

---

## Flujo de trabajo diario

### 1. Antes de empezar — sincronizar cambios

Cada vez que vayas a trabajar, primero actualiza tu copia local:

```bash
git checkout dev
git pull origin dev
```

Esto evita conflictos con cambios que el administrador u otros colaboradores hayan mergeado.

---

### 2. Hacer cambios y guardarlos

Trabaja normalmente en tus archivos. Cuando tengas algo listo:

```bash
git add .
git commit -m "descripción clara de lo que hiciste"
```

**Ejemplos de buenos mensajes de commit:**
```
Agrega validación de fecha en módulo de facturación
Corrige error de cálculo en reporte de inventario
Actualiza estilos del dashboard principal
Agrega campo de descuento en formulario de ventas
```

**Ejemplos de malos mensajes (evítalos):**
```
cambios
fix
actualización
wip
```

---

### 3. Subir tu trabajo a GitHub

```bash
git push origin dev
```

Esto sube tus cambios a la rama `dev` en GitHub. **No dispara ningún deploy** — el sitio de producción no se toca.

---

### 4. Abrir un Pull Request cuando tengas algo listo

Un Pull Request (PR) es la forma de pedirle al administrador que revise y apruebe tu trabajo.

**Pasos:**

1. Ve al repositorio en GitHub en tu navegador
2. Verás un banner amarillo: **"dev had recent pushes"** → click en **"Compare & pull request"**
3. Verifica que diga: `base: main ← compare: dev`
4. Escribe un **título claro** y una **descripción** de qué cambiaste y por qué
5. Click en **"Create pull request"**
6. Avisa al administrador por WhatsApp o como acordaron

> El administrador revisará, podrá pedir correcciones o lo aprobará y mergeará a `main`.
> **No mergees tu propio PR** — eso lo hace solo el administrador.

---

### 5. Si el administrador pide correcciones

No necesitas abrir un PR nuevo. Solo haz los cambios en tu rama `dev` y vuelve a hacer push:

```bash
# Haz los cambios que te pidieron...
git add .
git commit -m "Corrige observaciones del PR: descripción"
git push origin dev
```

El PR se actualiza automáticamente con los nuevos commits.

---

## Manejo de conflictos

Si al hacer `git pull origin dev` Git te avisa de un conflicto:

```bash
git pull origin dev
# Git marca los archivos en conflicto

# Abre los archivos marcados, busca estas secciones y resuelve:
# <<<<<<< HEAD
# tu versión
# =======
# versión remota
# >>>>>>> origin/dev

# Después de resolver:
git add .
git commit -m "Resuelve conflicto en [nombre del archivo]"
git push origin dev
```

Si el conflicto es complejo, consulta con el administrador antes de resolverlo por tu cuenta.

---

## Reglas importantes

| ✅ Siempre debes | ❌ Nunca hagas |
|---|---|
| Trabajar en la rama `dev` | Push directo a `main` |
| Hacer `pull` antes de empezar | Borrar archivos sin avisar |
| Escribir commits descriptivos | Subir archivos de configuración local (`.env`, config personal) |
| Avisar cuando el PR esté listo | Mergear tu propio PR |
| Consultar antes de cambios grandes | Modificar el archivo `.github/workflows/` |
| Respetar la estructura de carpetas | Subir archivos de prueba o temporales |

---

## Archivos que NUNCA debes subir

Asegúrate de que tu `.gitignore` excluya:

```
.env
*.local
node_modules/
/uploads/
thumbs.db
.DS_Store
*.log
```

Si accidentalmente agregas algo que no debías, avisa al administrador **antes** de hacer push.

---

## Estructura del proyecto (referencia)

```
/
├── modulos/          ← Módulos del ERP (facturación, inventario, etc.)
├── core/             ← Archivos base del sistema
├── docs/             ← Documentación
├── .github/
│   └── workflows/    ← GitHub Actions (NO modificar)
└── README.md
```

> Las carpetas `uploads/` y carpetas de fotos de auditorías están excluidas del repo — no intentes subirlas.

---

## Comandos de referencia rápida

```bash
# Ver en qué rama estás
git branch

# Cambiar a rama dev
git checkout dev

# Actualizar antes de trabajar
git pull origin dev

# Ver qué archivos cambiaste
git status

# Ver diferencias antes de commitear
git diff

# Guardar cambios
git add .
git commit -m "tu mensaje"

# Subir a GitHub
git push origin dev

# Ver historial de commits
git log --oneline
```

---

## Preguntas frecuentes

**¿Por qué me da error al hacer push a `main`?**
La rama `main` está protegida. Siempre trabaja en `dev` y abre un PR.

**¿Mi push a `dev` afecta el sitio en producción?**
No. El deploy solo se activa cuando el administrador mergea a `main`.

**¿Puedo crear ramas adicionales?**
Sí. Si vas a trabajar en algo grande o experimental, puedes crear `feature/nombre-de-la-función` y luego hacer PR de esa rama hacia `dev`.

```bash
git checkout dev
git checkout -b feature/mi-nueva-funcion
# trabajas...
git push origin feature/mi-nueva-funcion
# Abres PR hacia dev, no hacia main
```

**¿Puedo ver los Secrets de GitHub (llaves SSH, contraseñas)?**
No. Los Secrets del repositorio nunca se exponen a colaboradores ni aparecen en ningún archivo.

---

## Contacto

Cualquier duda antes de hacer cambios grandes o si algo sale mal → consulta primero con el administrador.
