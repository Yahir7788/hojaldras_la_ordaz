# Manual de instalación

**Hojaldras “La Ordaz” — Sistema de Gestión de Almacén e Inventarios**

Este documento describe la instalación local en **XAMPP** (Windows), la importación de la base de datos, la verificación del sistema y la configuración para otros entornos.

---

## 1. Antes de comenzar

Necesitas:

- XAMPP instalado (Apache + MySQL/MariaDB + PHP 8.1 o superior).
- La carpeta del proyecto `hojaldras_la_ordaz`.
- Un navegador actualizado.
- Permisos de escritura en la carpeta de XAMPP (para copiar el proyecto).

Comprueba que PHP tenga las extensiones necesarias. En XAMPP normalmente vienen activas:

```
pdo_mysql
mbstring
openssl
session
json
```

Puedes revisarlas en <http://localhost/dashboard/phpinfo.php> buscando `pdo_mysql` y `mbstring`.

---

## 2. Copiar el proyecto

1. Copia la carpeta completa `hojaldras_la_ordaz` a:
   ```
   C:\xampp\htdocs\hojaldras_la_ordaz
   ```
2. La estructura debe quedar así:
   ```
   C:\xampp\htdocs\hojaldras_la_ordaz\index.php
   C:\xampp\htdocs\hojaldras_la_ordaz\config\conexion.php
   C:\xampp\htdocs\hojaldras_la_ordaz\database\hojaldras_la_ordaz.sql
   ...
   ```

> No cambies el nombre de la carpeta si seguirás la URL de ejemplo. Si lo cambias, la ruta base se detecta automáticamente y el sistema seguirá funcionando.

---

## 3. Iniciar XAMPP

1. Abre **XAMPP Control Panel**.
2. Pulsa **Start** en **Apache**.
3. Pulsa **Start** en **MySQL**.
4. Verifica que ambos módulos estén en verde.

Si el puerto 80 está ocupado, Apache puede usar otro puerto (por ejemplo 8080). En ese caso abre:
```
http://localhost:8080/hojaldras_la_ordaz/
```

---

## 4. Importar la base de datos

### Opción A: phpMyAdmin (recomendada)

1. Abre <http://localhost/phpmyadmin>.
2. En la barra superior, elige **Importar**.
3. Pulsa **Seleccionar archivo** y busca:
   ```
   C:\xampp\htdocs\hojaldras_la_ordaz\database\hojaldras_la_ordaz.sql
   ```
4. Verifica que el formato sea **SQL** y la codificación **utf-8**.
5. Pulsa **Continuar**.
6. Al terminar, en la lista izquierda debe aparecer la base **`hojaldras_la_ordaz`** con estas tablas:
   - `roles`
   - `usuarios`
   - `categorias`
   - `insumos`
   - `entradas`
   - `lotes`
   - `salidas`
   - `mermas`
   - `movimientos_inventario`
   - `productos`
   - `movimientos_producto`

### Opción B: línea de comandos

Desde `C:\xampp\mysql\bin`:

```bat
mysql -u root -p < "C:\xampp\htdocs\hojaldras_la_ordaz\database\hojaldras_la_ordaz.sql"
```

Si `root` no tiene contraseña, omite `-p`.

### Opción C: solo estructura

Si prefieres una base vacía (sin datos de ejemplo), importa:
```
database/esquema.sql
```
Ese archivo crea la estructura, roles, categorías, usuarios de prueba y catálogo inicial de productos.

> **Importante:** `hojaldras_la_ordaz.sql` incluye `DROP DATABASE IF EXISTS`. Si ya tienes datos reales, haz un respaldo antes de importarlo.

---

## 5. Configurar la conexión

La configuración por defecto funciona con una instalación estándar de XAMPP:

| Parámetro | Valor |
|---|---|
| Host | `127.0.0.1` |
| Puerto | `3306` |
| Base de datos | `hojaldras_la_ordaz` |
| Usuario | `root` |
| Contraseña | vacía |

Si tu instalación es diferente, edita `config/conexion.php`:

```php
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'hojaldras_la_ordaz');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '');
```

También puedes usar variables de entorno en Apache o en la línea de comandos:

```bat
set DB_PORT=3307
set DB_USER=root
set DB_PASS=mi_contrasena
```

---

## 6. Primer acceso

1. Abre:
   ```
   http://localhost/hojaldras_la_ordaz/
   ```
2. Inicia sesión con:

   | Rol | Usuario | Contraseña |
   |---|---|---|
   | Gerente | `gerente` | `Gerente123!` |
   | Cocinero | `cocinero` | `Cocinero123!` |
   | Ventas | `ventas` | `Ventas123!` |

3. El sistema redirige al dashboard según el rol.
4. Cambia las contraseñas de prueba desde **Usuarios → Restablecer**.

---

## 7. Verificación posterior a la instalación

Realiza esta comprobación mínima:

- [ ] La página de login carga con estilos e imágenes.
- [ ] El usuario `gerente` entra al dashboard y ve gráficas.
- [ ] **Insumos** muestra 20 registros de ejemplo.
- [ ] **Entradas**, **Salidas** y **Mermas** muestran historial.
- [ ] **Alertas** muestra stock bajo y caducidades.
- [ ] **Reportes** permite imprimir y descargar CSV.
- [ ] **Usuarios** lista las tres cuentas.
- [ ] El usuario `cocinero` no ve Reportes ni Usuarios.
- [ ] El usuario `ventas` solo ve el módulo de producto terminado.
- [ ] Cerrar sesión regresa al login y el dashboard queda protegido.

Si algo falla, revisa la sección de solución de problemas del `README.md`.

---

## 8. Configuración para producción

Para publicar el sistema en un servidor real:

1. Cambia las contraseñas de los usuarios de prueba.
2. Crea un usuario MySQL dedicado (no uses `root`):
   ```sql
   CREATE USER 'laordaz_app'@'localhost' IDENTIFIED BY 'ContrasenaSegura123!';
   GRANT SELECT, INSERT, UPDATE, DELETE ON hojaldras_la_ordaz.* TO 'laordaz_app'@'localhost';
   FLUSH PRIVILEGES;
   ```
3. Actualiza `DB_USER` y `DB_PASS`.
4. Configura HTTPS y activa `secure` en la cookie de sesión (se detecta automáticamente con `$_SERVER['HTTPS']`).
5. Mantén `APP_DEBUG` desactivado:
   ```
   APP_DEBUG=0
   ```
6. Restringe el acceso a `phpMyAdmin`.
7. Programa respaldos periódicos de la base de datos:
   ```bat
   mysqldump -u root -p hojaldras_la_ordaz > respaldo_laordaz.sql
   ```
8. No publiques la carpeta `database/` ni `config/`; los `.htaccess` incluidos las bloquean en Apache.

---

## 9. Actualización del sistema

1. Haz un respaldo de la base de datos.
2. Copia los archivos nuevos sobre la instalación anterior.
3. Si el nuevo SQL incluye cambios de estructura, importa solo las tablas nuevas o aplica el script de migración correspondiente.
4. Prueba el acceso con los tres roles.
5. Verifica que las alertas y los reportes sigan funcionando.

---

## 10. Desinstalación

1. Elimina la carpeta `C:\xampp\htdocs\hojaldras_la_ordaz`.
2. En phpMyAdmin, elimina la base `hojaldras_la_ordaz`.
3. Elimina los respaldos locales si ya no los necesitas.

---

## 11. Notas técnicas

- El sistema usa `BASE_PATH` para funcionar tanto en la raíz del dominio como en una subcarpeta.
- Las sesiones se llaman `LAORDAZ_SESSID` y expiran a los 30 minutos de inactividad.
- MySQL se configura con la zona horaria de PHP (`America/Mexico_City`) al abrir la conexión.
- Las operaciones de inventario usan transacciones y bloqueos `SELECT ... FOR UPDATE`.
- El consumo de lotes es **FEFO**: primero caduca, primero sale.

---

© 2026 Hojaldras “La Ordaz” · Versión 1.0.0
