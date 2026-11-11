# Guía de Contribución y Flujo de Trabajo en Git · Funeraria Cristo Rey

Para mantener un desarrollo ordenado, trazable y evitar conflictos de integración en el equipo, todos los integrantes seguiremos las siguientes normas de trabajo colaborativo.

---

## 1. Principio Fundamental: Commits Atómicos (Por Unidades Lógicas)

> [!IMPORTANT]
> **No acumules todo el trabajo en un único commit gigante.**  
> Cada commit debe representar **una unidad lógica de trabajo** (un bloque funcional terminado y coherente).

- ❌ **Mal hábito:** Modificar 15 archivos dispares (migración, vistas, controladores, estilos y rutas) y hacer un único commit: `git commit -m "avances"`.
- ✅ **Buen hábito:** Guardar por partes.
  - ¿Terminaste la migración y el modelo? 👉 Un commit.
  - ¿Construiste la vista o formulario? 👉 Otro commit.
  - ¿Añadiste las pruebas automatizadas? 👉 Otro commit.

---

## 2. Prefijos Estándar para Mensajes de Commit (Conventional Commits)

Cada mensaje de commit debe comenzar con un prefijo que describa la naturaleza del cambio:

| Prefijo | Cuándo usarlo | Ejemplo |
| :--- | :--- | :--- |
| `feat:` | Nueva funcionalidad para el usuario o sistema | `feat: agrega estructura base del inventario de urnas` |
| `fix:` | Corrección de un error o cálculo defectuoso | `fix: corrige redondeo en calculo de copago afp` |
| `refactor:` | Reorganización o mejora interna sin alterar funcionalidad | `refactor: modulariza servicio de indicadores economicos` |
| `style:` | Cambios de formato, estilos visuales (Tailwind / CSS) | `style: ajusta espaciado y colores en tarjetas de kpi` |
| `test:` | Creación o actualización de pruebas unitarias o de integración | `test: agrega pruebas para emision de facturas exentas` |
| `docs:` | Cambios exclusivos en documentación | `docs: actualiza instrucciones de instalacion docker en readme` |
| `chore:` | Tareas de mantenimiento, dependencias o configuración | `chore: actualiza .gitignore y configuracion de docker` |

---

## 3. Flujo Diario de Trabajo Recomendado

Cada vez que te sientes a programar (inicio de jornada o cambio de turno):

### Paso 1: Actualizar tu rama local
Antes de empezar a escribir código, sincroniza lo que tu compañero haya subido:
```bash
git pull origin main
```

### Paso 2: Revisar el estado de los cambios
Verifica qué archivos has modificado (en rojo) o creado:
```bash
git status
```

### Paso 3: Seleccionar los cambios por unidad lógica
- **Para guardar solo archivos específicos de una tarea:**
  ```bash
  git add app/Models/Urna.php database/migrations/2026_11_10_000001_create_urnas_table.php
  ```
- **O para agregar todo el bloque terminado:**
  ```bash
  git add .
  ```

### Paso 4: Crear el commit con mensaje claro
```bash
git commit -m "feat: agrega inventario dual de urnas entre salon y taller"
```

### Paso 5: Enviar cambios al repositorio remoto (GitHub)
Sube tus cambios al terminar tu bloque funcional o al finalizar la jornada laboral, antes de que tu compañero comience:
```bash
git push origin main
```

---

## 4. Buenas Prácticas Generales
1. **Nunca modifiques ni subas credenciales:** Las variables sensibles deben permanecer en el `.env` local de cada desarrollador.
2. **Ejecuta las pruebas antes de hacer push:**
   ```bash
   docker compose exec laravel.test php artisan test
   ```
3. **Comunica cambios de base de datos:** Si agregas una migración nueva, avisa a tu compañero para que ejecute `docker compose exec laravel.test php artisan migrate`.
