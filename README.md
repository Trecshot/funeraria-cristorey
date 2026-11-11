# Funeraria Cristo Rey

Sistema de gestión funeraria desarrollado en Laravel 11, preparado para ejecutarse de forma aislada y estandarizada mediante Docker y Docker Compose (Laravel Sail).

---

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado en tu equipo:

1. **Docker Desktop**:
   - [Descargar Docker Desktop](https://www.docker.com/products/docker-desktop/).
   - En Windows, asegúrate de tener activado el backend **WSL 2** (Windows Subsystem for Linux).
   - Asegúrate de que Docker Desktop esté iniciado y corriendo.
2. **Git**:
   - [Descargar Git](https://git-scm.com/).
3. *(Opcional)*: No es obligatorio tener instalados PHP, Composer ni MySQL en tu sistema operativo local; los contenedores de Docker proveen todo el entorno necesario para ejecutar la aplicación.

---

## 🚀 Guía Paso a Paso: Instalación y Conexión con Docker

Sigue estos pasos desde una terminal (PowerShell en Windows o Bash en Linux/macOS) en la carpeta donde deseas alojar el proyecto:

### Paso 1: Clonar el repositorio y entrar a la carpeta

```bash
git clone https://github.com/tu-usuario/funeraria-cristorey.git
cd funeraria-cristorey
```

*(Si ya tienes la carpeta del proyecto descargada, simplemente abre tu terminal dentro de ella).*

---

### Paso 2: Crear el archivo de variables de entorno (`.env`)

Duplica el archivo de plantilla `.env.example` para crear tu `.env`:

- **En Windows (PowerShell):**
  ```powershell
  Copy-Item .env.example .env
  ```
- **En Linux / macOS:**
  ```bash
  cp .env.example .env
  ```

---

### Paso 3: Configurar la conexión de Laravel con Docker (`.env`)

Abre el archivo `.env` recién creado y verifica la sección de base de datos. Para que Laravel se conecte correctamente con el contenedor de MySQL, debe estar configurado de la siguiente manera:

```ini
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=funeraria_cristorey
DB_USERNAME=sail
DB_PASSWORD=password
```

> ⚠️ **Importante sobre `DB_HOST=mysql`:**
> Dentro de la red de Docker Compose, el nombre del host no es `127.0.0.1` ni `localhost`, sino el nombre del servicio definido en `compose.yaml` (en este caso, `mysql`). El DNS interno de Docker se encarga de resolver este nombre hacia la IP del contenedor de base de datos.

Si el puerto `80` está ocupado en tu máquina (por ejemplo por IIS, Apache o Skype), puedes cambiar el puerto agregando al `.env`:
```ini
APP_PORT=8080
```

---

### Paso 4: Construir y levantar los contenedores con Docker Compose

Ejecuta el siguiente comando para descargar las imágenes, construir el entorno y arrancar los servicios en segundo plano (`-d`):

```bash
docker compose up -d --build
```

Para verificar que los contenedores estén levantados y saludables:
```bash
docker compose ps
```
Deberías ver dos servicios activos:
- `funeraria-cristorey-laravel.test-1` (Aplicación Laravel / PHP 8.4)
- `funeraria-cristorey-mysql-1` (Base de datos MySQL 8.4)

---

### Paso 5: Generar la clave de la aplicación (`APP_KEY`)

Ejecuta el comando `key:generate` de Artisan dentro del contenedor de Laravel:

```bash
docker compose exec laravel.test php artisan key:generate
```

---

### Paso 6: Ejecutar las migraciones y datos iniciales (Seeders)

Crea las tablas de la base de datos y carga el usuario administrador inicial:

```bash
docker compose exec laravel.test php artisan migrate --force
docker compose exec laravel.test php artisan db:seed --force
```

---

### Paso 7: Instalar dependencias de frontend y compilar assets

Compila los estilos de Tailwind CSS y scripts de la interfaz:

```bash
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

*(Si deseas trabajar en desarrollo con recarga en vivo de Vite, puedes dejar corriendo en otra terminal: `docker compose exec laravel.test npm run dev`).*

---

### Paso 8: Abrir la aplicación en el navegador

Una vez finalizados los pasos anteriores, abre tu navegador e ingresa a:
- 👉 **[http://localhost](http://localhost)** (o **[http://localhost:8080](http://localhost:8080)** si cambiaste `APP_PORT`).

---

## 🔑 Acceso Inicial y Usuarios

El seeder del sistema genera un usuario administrador por defecto para demostración:

- **Correo:** `renato.osses@cristorey.cl`
- **Contraseña:** `admin123`

### Crear un nuevo usuario por consola (CLI)
También puedes crear usuarios interactivos con contraseña segura (mínimo 12 caracteres):

```bash
docker compose exec laravel.test php artisan usuario:crear "Nombre Usuario" correo@cristorey.cl --rol=administrador
```

**Roles disponibles:**
- `administrador`: Acceso total al sistema y configuración.
- `gerencia`: Visualización global, reportes y finanzas.
- `asistente`: Apertura y edición de expedientes de servicios, bodega y flota.
- `bodega`: Control de inventario dual (Salón Calle Blanco vs. Taller) y traspasos.
- `chofer`: Hoja de ruta móvil sin acceso a datos financieros o cotizaciones.
- `finanzas`: Liquidación de convenios (IPS / AFP), desglose de copagos y comprobantes PDF.

---

## 🔌 ¿Cómo funciona la arquitectura Docker en este proyecto?

El archivo [`compose.yaml`](compose.yaml) orquesta dos contenedores interconectados mediante una red bridge (`sail`):

```
┌────────────────────────────────────────────────────────┐
│                      RED DOCKER                        │
│                                                        │
│   ┌──────────────────────┐    ┌────────────────────┐   │
│   │     laravel.test     │    │       mysql        │   │
│   │   (PHP 8.4 + Nginx/  │───>│   (MySQL 8.4)      │   │
│   │     Node.js / Vite)  │    │                    │   │
│   └──────────────────────┘    └────────────────────┘   │
│              │                          │              │
└──────────────┼──────────────────────────┼──────────────┘
               │                          │
       Puertos expuestos:         Puerto expuesto:
       :80 (Web)                  :3306 (Acceso externo a BD)
       :5173 (Vite HMR)
```

1. **Volumen de datos persistente (`sail-mysql`):**
   La base de datos reside en un volumen administrado por Docker. Cuando apagues los contenedores (`docker compose down`), los datos permanecerán intactos para el siguiente inicio.
2. **Volumen de código (`.:/var/www/html`):**
   Cualquier cambio que realices en el código desde tu editor (VS Code, Antigravity, etc.) se refleja inmediatamente dentro del contenedor sin necesidad de reconstruir la imagen.
3. **Conexión a MySQL desde herramientas externas (DBeaver, TablePlus, Navicat):**
   - **Host:** `localhost` o `127.0.0.1`
   - **Puerto:** `3306`
   - **Base de datos:** `funeraria_cristorey`
   - **Usuario:** `sail` (o `root`)
   - **Contraseña:** `password`

---

## 🛠️ Comandos Frecuentes del Día a Día

| Tarea | Comando |
| :--- | :--- |
| **Iniciar contenedores** | `docker compose up -d` |
| **Detener contenedores** | `docker compose down` |
| **Ver estado de los servicios** | `docker compose ps` |
| **Ver logs en tiempo real** | `docker compose logs -f` |
| **Entrar a la terminal del contenedor** | `docker compose exec laravel.test bash` |
| **Ejecutar pruebas unitarias / feature** | `docker compose exec laravel.test php artisan test` |
| **Limpiar caché de configuración y rutas**| `docker compose exec laravel.test php artisan optimize:clear` |
| **Formatear código con Laravel Pint** | `docker compose exec laravel.test ./vendor/bin/pint` |

---

## ⚙️ Módulos Principales del Sistema

- **`/dashboard`**: Indicadores gerenciales, estadísticas operativas y **Semáforo Documental Crítico (< 3 hrs)** para cortejos próximos que requieran Resolución Seremi u Oficio SML.
- **`/servicios`**: Apertura de expedientes, cálculo automático de copago y carga de respaldos en bucket privado Amazon S3.
- **`/bodega`**: Gestión de stock dual entre Salón Calle Blanco (frente al hospital) y Taller de ensamblado, con auditoría de traspasos.
- **`/flota/calendario`**: Programación de carrozas y furgones de instalación, asignación de choferes y prevención de solapamientos.
- **`/movil/chofer`**: Vista móvil simplificada con hoja de ruta, implementos de traslado y actualización de estados en ruta.
- **`/finanzas`**: Cálculo de aportes previsionales (IPS Asignación por Muerte de \$1.070.000, AFP 15 UF con cotización configurable en `UF_VALUE_CLP`) y emisión de comprobantes en PDF.
- **`/catalogo`**: Catálogo web público optimizado para consultas vía WhatsApp sin exposición de precios internos.

---

## ❓ Preguntas Frecuentes y Solución de Problemas

### 1. Error: `Ports are not available: exposing port TCP 0.0.0.0:80`
Indica que otra aplicación en tu computadora está usando el puerto 80.
- **Solución:** Abre el archivo `.env`, añade `APP_PORT=8080` y vuelve a ejecutar `docker compose up -d`. Luego accede desde `http://localhost:8080`.

### 2. Error: `SQLSTATE[HY000] [2002] Connection refused`
Ocurre si Laravel intenta conectar antes de que MySQL termine de inicializar, o si `DB_HOST` está mal configurado.
- **Solución:** Confirma que en tu `.env` tengas `DB_HOST=mysql` (no `localhost`) y ejecuta `docker compose ps` para verificar que el contenedor de MySQL esté en estado `healthy`.

### 3. Los cambios en los estilos CSS no se ven reflejados
- **Solución:** Vuelve a compilar los assets ejecutando:
  ```bash
  docker compose exec laravel.test npm run build
  ```

---

## 🤝 Flujo de Trabajo y Convenciones de Commits
Para conocer las normas de trabajo en equipo, prefijos de commits (`feat:`, `fix:`, `style:`, etc.) y buenas prácticas colaborativas, consulta la [Guía de Contribución](CONTRIBUTING.md).
