# 🧁 Dulce Rinconcito

Sistema integral para administrar recetas de repostería con **costeo automático** de producción.  
_Pastelería y repostería artesanal._

![Logo](public/assets/img/logo.jpg)

## ✨ Características

- **📦 Inventario**: registra tus insumos (harina, mantequilla, huevos…) con su costo por unidad (kilo, litro, pieza, gramo, mililitro).
- **✨ Recetas**: crea recetas agregando ingredientes y cantidades; el costo total se calcula **en tiempo real** mientras escribes.
- **🍰 Recetario visual**: las recetas se muestran en tarjetas bonitas con imagen, descripción y costo desglosado.
- **📸 Imágenes**: sube una foto de cada platillo (JPG, PNG, WebP, GIF hasta 5 MB).
- **🧮 Costeo**: automáticamente convierte gramos → kilos y mililitros → litros al multiplicar.
- **🎨 Tema repostero**: paleta pastel rosa/crema, dulces flotantes animados, fuentes *Pacifico* y *Quicksand*.
- **🔐 Seguridad**: PDO preparado, tokens CSRF, escape HTML, cabeceras seguras, protección de `/uploads`.

---

## 🧱 Stack tecnológico

| Componente       | Tecnología                          |
|------------------|-------------------------------------|
| Frontend         | HTML + **Tailwind CSS** (CDN)       |
| Interactividad   | **Alpine.js** (CDN)                 |
| Backend          | **PHP 7.4+** (sin frameworks)       |
| Base de datos    | **MySQL/MariaDB** (recomendado) o **SQLite** |
| Servidor         | Apache (XAMPP, MAMP, Hostinger, etc.) |

No requiere Node, npm ni procesos de build. **Copia y listo**.

---

## 🚀 Instalación

### 1. Variables de entorno

Todas las credenciales y datos sensibles se leen desde **variables de entorno**. Nunca las pongas directo en el código.

Copia `.env.example` a `.env` y rellena:

```bash
cp .env.example .env
```

Variables mínimas para MySQL (las que usarás en Hostinger):

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_NAME=u123456789_dulce
DB_USER=u123456789_roy
DB_PASS=TuPasswordSeguro
```

### 2. Opción A — MySQL (recomendado para Hostinger)

1. Crea una base de datos MySQL desde el panel de tu hosting (Hostinger → Bases de datos MySQL).
2. Abre **phpMyAdmin** desde el panel y selecciona tu base de datos.
3. Importa el archivo `database/schema.mysql.sql` (incluye 10 ingredientes de ejemplo).
4. Sube los archivos al servidor (vía FTP, Git o el Administrador de archivos).
5. Configura las variables de entorno en el panel de Hostinger (ver sección _Despliegue en Hostinger_).
6. Abre tu dominio en el navegador.

### 3. Opción B — SQLite (local, sin MySQL)

1. Cambia `DB_DRIVER=sqlite` en `.env`.
2. Abre la app en `http://localhost/reposteria/public/`.
3. La base de datos se crea **automáticamente** la primera vez, con 10 ingredientes de ejemplo.

> Permisos recomendados para `database/` y `public/uploads/`: **0775**.

---

## 🌐 Despliegue en Hostinger

### A. Crear la base de datos

1. Entra al panel de Hostinger → **Bases de datos MySQL**.
2. Crea una base nueva (anota: nombre, usuario y contraseña).
3. Abre **phpMyAdmin** desde el panel.
4. Selecciona tu base de datos en el panel izquierdo.
5. Click en **Importar** → sube `database/schema.mysql.sql` → **Continuar**.

### B. Subir el código (vía Git)

1. En el panel de Hostinger ve a **Archivos → Acceso GIT** o usa SSH.
2. Como aún no tienes el repo, sube los archivos con **Administrador de archivos** o **FTP**:
   - Sube **todo el contenido** de esta carpeta al `public_html/` (o a una subcarpeta).
   - Asegúrate de que el `public/.htaccess` esté accesible.

### C. Configurar variables de entorno

Tienes **dos opciones**:

#### Opción 1 — Panel de Hostinger (recomendado)

Ve a **Sitio web → Configuración → Variables de entorno** (o **Avanzado → Variables de entorno** según tu panel) y crea:

| Variable     | Ejemplo                              |
|-------------|--------------------------------------|
| `DB_DRIVER` | `mysql`                              |
| `DB_HOST`   | `localhost`                          |
| `DB_NAME`   | el nombre de tu BD                   |
| `DB_USER`   | tu usuario MySQL                     |
| `DB_PASS`   | tu contraseña MySQL                  |
| `APP_ENV`   | `production`                         |
| `APP_DEBUG` | `false`                              |

> En Hostinger Business/Cloud el panel está en **Sitio web → Configuración → Variables de entorno**.
> En planes compartidos más antiguos edita un archivo `.env` (ver opción 2).

#### Opción 2 — Archivo `.env` en el servidor

Si tu plan no permite variables desde el panel:

1. Conéctate por FTP o Administrador de archivos.
2. En la **raíz del proyecto** (junto a `index.php`) crea un archivo `.env`.
3. Pega el contenido de `.env.example` con tus datos reales.
4. El archivo `.env` está en `.gitignore`, así que nunca se sube al repo.

### D. Apuntar el dominio a `/public/`

Si subes todo a `public_html/` ya queda apuntando bien (porque el `.htaccess` raíz redirige a `/public/`).

Si prefieres subir solo el contenido de `public/`, configura el **document root** del dominio a esa carpeta desde el panel.

---

## 📂 Estructura

```
reposteria/
├── config/
│   └── config.php            # Config (lee variables de entorno)
├── database/
│   ├── schema.sqlite.sql     # Esquema SQLite + datos de ejemplo
│   ├── schema.mysql.sql      # Esquema MySQL (sin CREATE DATABASE)
│   └── reposteria.sqlite     # (solo si usas SQLite)
├── public/                   # ← Apuntar el servidor web aquí
│   ├── index.php             # Recetario (home)
│   ├── inventario.php        # CRUD de insumos
│   ├── receta.php            # Formulario de receta (calculadora)
│   ├── ver-receta.php        # Detalle de receta
│   ├── acciones.php          # Endpoints POST
│   ├── api/
│   │   └── ingredientes.php
│   ├── uploads/              # Imágenes subidas
│   ├── assets/
│   │   ├── css/styles.css
│   │   ├── js/app.js
│   │   └── img/
│   │       ├── favicon.svg
│   │       ├── logo.jpg      # Logo "Dulce Rinconcito"
│   │       └── placeholder.svg
│   └── .htaccess
├── src/
│   ├── bootstrap.php
│   ├── Database.php          # Capa PDO (soporta sqlite y mysql)
│   ├── env.php               # Lector de .env + helper env()
│   ├── helpers.php
│   ├── Models/
│   │   ├── Ingrediente.php
│   │   └── Receta.php
│   └── layout/
│       ├── header.php
│       └── footer.php
├── .env.example              # Plantilla de variables de entorno
├── .gitignore
├── .htaccess                 # Redirige al directorio /public
├── install.php               # Instalador automático (solo SQLite)
├── index.php                 # Redirección de respaldo
└── README.md
```

---

## 🔐 Seguridad

- **PDO con sentencias preparadas** → sin inyección SQL.
- **Tokens CSRF** en todos los formularios.
- **Escape HTML** automático con la función `e()`.
- **Validación MIME y tamaño** de archivos subidos.
- **Cabeceras HTTP seguras** (`X-Content-Type-Options`, `X-Frame-Options`).
- **`.htaccess`** impide ejecutar PHP dentro de `uploads/`.
- **Variables de entorno** separadas del código fuente.

---

## 🧮 Cómo funciona el cálculo

```
subtotal = costo_base_ingrediente × cantidad × factor_unidad
```

| Unidad ingresada   | factor |
|--------------------|--------|
| kilo, litro, pieza | 1.0    |
| gramo, mililitro   | 0.001  |

**Ejemplo**: una receta usa `200 g` de harina que cuesta `$28.50` por kilo:
`$28.50 × 200 × 0.001 = $5.70`

---

## 🎨 Personalizar el tema

- **Marca y logo**: `APP_NAME`, `APP_TAGLINE` en `.env` y `public/assets/img/logo.jpg`.
- **Colores y animaciones**: `src/layout/header.php` (config de Tailwind) y `public/assets/css/styles.css`.

---

## 📜 Licencia

Uso libre para proyectos personales y comerciales.