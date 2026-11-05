# Funeraria Cristo Rey

Aplicacion Laravel preparada para ejecutarse con Docker Compose y MySQL.

## Requisitos

- Docker Desktop instalado y en ejecucion.
- Docker Compose v2 (`docker compose`). Si tu instalacion solo incluye el ejecutable independiente, usa `docker-compose` en lugar de `docker compose` en los comandos siguientes.

## Primer inicio en Windows (PowerShell)

Desde la carpeta del proyecto:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
docker compose up -d --build
if (Select-String -Path .env -Pattern '^APP_KEY=$' -Quiet) { docker compose exec laravel.test php artisan key:generate }
docker compose exec laravel.test php artisan migrate --force
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

Después, crea una cuenta con el comando de la sección [Primer acceso](#primer-acceso) y abre [http://localhost](http://localhost). Para iniciar los contenedores en otro momento basta con `docker compose up -d`; no vuelvas a generar la clave de la aplicación. La base de datos queda guardada en un volumen de Docker y se conserva al detener los servicios con `docker compose down`.

La aplicacion usa el nombre de servicio `mysql` como host de base de datos dentro de la red de Compose. No cambies `DB_HOST=mysql` por `localhost`: desde el contenedor de Laravel, `localhost` apunta al propio contenedor.

## Primer acceso

Tras iniciar los contenedores y migrar la base de datos, crea una cuenta de acceso (no se genera una contraseña predeterminada):

```powershell
docker compose exec laravel.test php artisan usuario:crear "Administrador" admin@tudominio.cl --rol=administrador
```

El comando solicita una contraseña de al menos 12 caracteres de forma interactiva. Roles disponibles: `administrador`, `gerencia`, `asistente`, `bodega`, `chofer` y `finanzas`. Administrador y gerencia acceden a todos los módulos; asistente a servicios, bodega y flota; bodega solo al inventario; chofer a su vista móvil; finanzas a informes financieros.

## Módulos y configuración

- `/dashboard`: alertas de documentos faltantes para cortejos dentro de 3 horas e indicadores operativos.
- `/servicios`: apertura y seguimiento de expedientes. Los documentos se cargan en un bucket S3 privado.
- `/bodega`: inventario por Salón (Calle Blanco) y Taller y traspasos auditados.
- `/flota/calendario`: agenda de traslados y asignación de vehículos y choferes.
- `/movil/chofer`: hoja de ruta sin precios ni datos contables.
- `/finanzas`: copagos de referencia y comprobantes PDF; el catálogo público `/catalogo` no expone precios.

Para cargar documentos, configura `AWS_BUCKET` y `AWS_DEFAULT_REGION`, además de credenciales AWS en `.env` o un rol IAM disponible para el contenedor. Los objetos se guardan con visibilidad privada. El convenio AFP se muestra como 15 UF; para calcular el equivalente y estimar copago en CLP, configura `UF_VALUE_CLP` con la cotización vigente. Los aportes de Dipreca y sindicatos se muestran como pendientes de confirmación, ya que no se proporcionaron montos.

En este entorno de desarrollo, PHP corre como `root` dentro del contenedor para poder escribir en `storage` y `bootstrap/cache` cuando el codigo se monta desde Windows. Esto no afecta al contenedor ni a la base de datos de otros proyectos.

Si el puerto 80 esta ocupado, agrega `APP_PORT=8080` al archivo `.env` y abre [http://localhost:8080](http://localhost:8080).
