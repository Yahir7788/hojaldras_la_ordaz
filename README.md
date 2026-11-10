# HOJALDRAS "LA ORDAZ"

**Sistema de Gestión de Almacén e Inventarios** para la administración de materias primas, producción y venta de hojaldres, volovanes y pizzas.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B%20%2F%20MariaDB-4479a1)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952b3)
![Licencia](https://img.shields.io/badge/Uso-Interno-c8963e)

---

## 1. Descripción

Aplicación web desarrollada en **PHP, MySQL, HTML5, CSS3, JavaScript y Bootstrap 5**, pensada para ejecutarse localmente en **XAMPP**. No requiere frameworks ni conexión a internet: Bootstrap, Bootstrap Icons y Chart.js están incluidos en `assets/vendor`.

### Funciones principales

- Inicio de sesión con contraseñas cifradas (`password_hash`) y control de intentos fallidos.
- Tres roles: **gerente**, **cocinero** y **personal de ventas**.
- Dashboard con indicadores y gráficas (Chart.js).
- Catálogo de insumos con categorías, unidades, stock mínimo y estado.
- Entradas de almacén con lotes y fecha de caducidad obligatoria para perecederos.
- Salidas de almacén con validación de existencias y consumo **FEFO** de lotes.
- Mermas con motivo, descripción y descuento automático del inventario.
- Ajustes de inventario auditados (solo gerente).
- Alertas automáticas de stock bajo, agotados y caducidades.
- Control de **producto terminado**: producción, venta y ajuste de existencias.
- Administración de usuarios, roles, contraseñas y estatus.
- Reportes con filtros, impresión y exportación **CSV** compatible con Excel.
- Historial completo de movimientos de inventario y de producto terminado.

---

## 2. Requisitos

| Componente | Versión mínima |
|---|---|
| PHP | 8.1 o superior |
| MySQL / MariaDB | MySQL 8.0 / MariaDB 10.4 |
| Apache | Incluido en XAMPP |
| Extensiones PHP | `pdo_mysql`, `mbstring`, `openssl`, `session`, `json` |
| Navegador | Chrome, Edge, Firefox o Safari actualizado |

> El sistema fue probado con PHP 8.3 y MySQL 8. Los archivos usan UTF-8 y la zona horaria `America/Mexico_City`.

---

## 3. Instalación rápida en XAMPP

1. Copia la carpeta `hojaldras_la_ordaz` dentro de:
   ```
   C:\xampp\htdocs\hojaldras_la_ordaz
   ```
2. Abre el **Panel de Control de XAMPP** e inicia **Apache** y **MySQL**.
3. Entra a **phpMyAdmin**: <http://localhost/phpmyadmin>.
4. Ve a la pestaña **Importar**, selecciona el archivo:
   ```
   database/hojaldras_la_ordaz.sql
   ```
   y pulsa **Continuar**. El archivo crea la base `hojaldras_la_ordaz`, las tablas, los roles, las categorías, los usuarios de prueba y los datos de ejemplo.
5. Abre el sistema en el navegador:
   ```
   http://localhost/hojaldras_la_ordaz/
   ```

Si tu MySQL usa contraseña para `root`, edita `config/conexion.php` o define las variables de entorno `DB_USER` y `DB_PASS` antes de iniciar Apache.

### Instalación con otro puerto o base de datos

`config/conexion.php` acepta variables de entorno:

| Variable | Valor por defecto |
|---|---|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `hojaldras_la_ordaz` |
| `DB_USER` | `root` |
| `DB_PASS` | vacío |

---

## 4. Usuarios de prueba

| Rol | Usuario | Contraseña | Acceso |
|---|---|---|---|
| Gerente | `gerente` | `Gerente123!` | Todo el sistema |
| Cocinero | `cocinero` | `Cocinero123!` | Consulta, salidas, mermas y alertas |
| Ventas | `ventas` | `Ventas123!` | Producto terminado |

> Cambia estas contraseñas desde **Usuarios → Restablecer** antes de usar el sistema en producción.

---

## 5. Matriz de permisos

| Módulo / acción | Gerente | Cocinero | Ventas |
|---|:---:|:---:|:---:|
| Dashboard | ✅ | ✅ | ✅ (vista de producto terminado) |
| Consultar insumos y existencias | ✅ | ✅ | ❌ |
| Crear / editar / eliminar insumos | ✅ | ❌ | ❌ |
| Ajustar inventario | ✅ | ❌ | ❌ |
| Registrar entradas | ✅ | ❌ | ❌ |
| Registrar salidas | ✅ | ✅ | ❌ |
| Registrar mermas | ✅ | ✅ | ❌ |
| Ver alertas | ✅ | ✅ | ❌ |
| Producto terminado: consultar | ✅ | ❌ | ✅ |
| Producto terminado: producción y venta | ✅ | ❌ | ✅ |
| Producto terminado: ajustar existencia | ✅ | ❌ | ❌ |
| Crear / editar / desactivar productos | ✅ | ❌ | ❌ |
| Reportes e impresión / CSV | ✅ | ❌ | ❌ |
| Administrar usuarios | ✅ | ❌ | ❌ |

Las restricciones se validan en el servidor con `requerir_roles()`, no solo ocultando opciones del menú.

---

## 6. Estructura del proyecto

```
hojaldras_la_ordaz/
├── index.php                 Redirección de entrada
├── .htaccess                 Seguridad de Apache
├── README.md
├── auth/
│   ├── login.php             Inicio de sesión
│   └── logout.php            Cierre de sesión (POST + CSRF)
├── config/
│   ├── config.php            Constantes, zona horaria, sesión y BASE_PATH
│   └── conexion.php          Conexión PDO y variables de entorno
├── includes/
│   ├── auth.php              Protección de sesión y roles
│   ├── funciones.php         Utilidades, validaciones y reglas de inventario
│   ├── alertas.php           Consultas de alertas
│   ├── header.php            Cabecera y layout superior
│   ├── sidebar.php           Menú lateral por rol
│   └── footer.php            Pie, scripts y notificaciones
├── dashboard/index.php       Panel con KPIs y gráficas
├── insumos/                  CRUD, ajuste y baja lógica
├── entradas/                 Recepciones y lotes
├── salidas/                  Consumo de materia prima
├── mermas/                   Pérdidas y desperdicios
├── alertas/index.php         Centro de alertas
├── productos/                Producto terminado
├── reportes/                 Reportes, impresión y CSV
├── usuarios/                 Administración de cuentas
├── database/
│   ├── hojaldras_la_ordaz.sql  Base completa con datos de ejemplo
│   ├── esquema.sql             Solo estructura y datos base
│   └── seed.php                Generador opcional de datos de demostración
├── assets/
│   ├── css/estilos.css
│   ├── js/app.js
│   ├── img/logo.svg
│   └── vendor/               Bootstrap, Bootstrap Icons y Chart.js locales
└── docs/
    ├── MANUAL_INSTALACION.md
    ├── MANUAL_USUARIO.md
    ├── GUIA_PRUEBAS.md
    └── REQUERIMIENTOS.md
```

---

## 7. Reglas de negocio implementadas

1. Las cantidades deben ser **mayores que cero** en entradas, salidas y mermas.
2. No se permite una salida o merma superior a la existencia disponible.
3. Toda entrada perecedera exige **fecha de caducidad** y genera un lote.
4. Cada movimiento guarda fecha, insumo, cantidad, usuario y observaciones.
5. Solo el gerente puede ajustar la existencia inicial o corregir el inventario.
6. El stock se actualiza automáticamente dentro de una **transacción MySQL**.
7. Las salidas y mermas consumen lotes con **FEFO** (primero en caducar, primero en salir).
8. Las alertas se calculan en tiempo real con el stock y las fechas de caducidad.
9. El historial es permanente: los insumos y productos se desactivan lógicamente, no se borran.
10. Un insumo con existencia no puede eliminarse; un producto con stock no puede desactivarse.
11. No se puede desactivar la propia cuenta ni dejar al sistema sin un gerente activo.
12. Las contraseñas requieren al menos 8 caracteres, una letra y un número.

---

## 8. Seguridad

- Consultas preparadas con **PDO** y `ATTR_EMULATE_PREPARES = false`.
- Escapes de salida con `htmlspecialchars` mediante `e()`.
- Tokens **CSRF** en todos los formularios POST.
- Sesiones con `HttpOnly`, `SameSite=Lax`, `use_strict_mode` y regeneración de ID al iniciar sesión.
- Expiración por inactividad de 30 minutos.
- Contraseñas con `password_hash()` y verificación con `password_verify()`.
- Bloqueo temporal tras 5 intentos fallidos en 5 minutos.
- Validación de permisos en el servidor para cada módulo.
- Archivos `.htaccess` que bloquean `config/`, `includes/` y `database/`.
- Cierre de sesión exclusivamente por POST con CSRF.

---

## 9. Reportes disponibles

| Reporte | Filtros principales |
|---|---|
| Inventario actual | Estado, categoría, insumo |
| Movimientos de inventario | Fechas, tipo, categoría, insumo |
| Entradas de almacén | Fechas, categoría, insumo |
| Salidas de almacén | Fechas, categoría, insumo |
| Mermas y pérdidas | Fechas, motivo, categoría, insumo |
| Stock bajo y agotados | Categoría, insumo |
| Control de caducidades | Vencimiento, categoría, insumo |
| Producto terminado | Fechas, tipo de movimiento, producto |

Todos incluyen resumen, impresión con formato institucional y descarga CSV (UTF-8 con BOM, separador `;`).

---

## 10. Solución de problemas

| Problema | Causa probable | Solución |
|---|---|---|
| “No fue posible conectar con la base de datos” | MySQL apagado o base no importada | Inicia MySQL en XAMPP e importa el SQL |
| Error 1045 al conectar | Contraseña de `root` distinta | Ajusta `DB_PASS` en `config/conexion.php` |
| Página en blanco | `APP_DEBUG` apagado y error PHP | Define `APP_DEBUG=1` o revisa el log de Apache |
| Los estilos no cargan | Ruta base incorrecta | Verifica que la carpeta esté en `htdocs/hojaldras_la_ordaz` |
| El CSV muestra caracteres raros | Excel no detecta UTF-8 | El archivo ya incluye BOM; ábrelo con “Datos → Desde texto” si es necesario |
| No aparece el módulo de reportes | El usuario no es gerente | Inicia sesión con el usuario `gerente` |
| “Sesión expirada” al guardar | Token CSRF vencido | Recarga la página e inténtalo de nuevo |

---

## 11. Documentación adicional

- [Manual de instalación](docs/MANUAL_INSTALACION.md)
- [Manual de usuario](docs/MANUAL_USUARIO.md)
- [Guía de pruebas](docs/GUIA_PRUEBAS.md)
- [Requerimientos y arquitectura](docs/REQUERIMIENTOS.md)

---

© 2026 Hojaldras “La Ordaz” · Sistema de Gestión de Almacén e Inventarios · Versión 1.0.0
