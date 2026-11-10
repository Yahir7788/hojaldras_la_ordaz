# Requerimientos y arquitectura

**Hojaldras “La Ordaz” — Sistema de Gestión de Almacén e Inventarios**

Documento de especificación basado en el estándar **IEEE 830** (Especificación de Requerimientos de Software). Describe el alcance, los actores, los requerimientos funcionales y no funcionales, la arquitectura y el modelo de datos del sistema.

---

## 1. Introducción

### 1.1 Propósito

Definir el comportamiento, las restricciones y los criterios de aceptación del sistema web **Hojaldras “La Ordaz”**, destinado al control de materias primas, inventario, mermas, alertas y producto terminado de una panadería artesanal.

### 1.2 Alcance

El sistema cubre:

- Autenticación y autorización por roles.
- Administración de insumos y categorías.
- Registro de entradas, salidas y mermas.
- Control de lotes y caducidades.
- Alertas automáticas de inventario.
- Control de producción y venta de producto terminado.
- Reportes, impresión y exportación CSV.
- Administración de usuarios.

No incluye facturación fiscal, pasarela de pagos, compras a proveedores, nómina ni integración con dispositivos físicos.

### 1.3 Definiciones

| Término | Significado |
|---|---|
| Insumo | Materia prima o material utilizado en la producción. |
| Lote | Cantidad de un insumo recibida en una entrada, con fecha de caducidad. |
| FEFO | *First Expired, First Out*: primero en caducar, primero en salir. |
| Merma | Producto perdido por caducidad, deterioro, desperdicio o daño. |
| Stock mínimo | Existencia a partir de la cual se genera una alerta. |
| Producto terminado | Artículo listo para venta (hojaldre, volován, pizza). |
| Movimiento | Registro auditado de un cambio de inventario. |

### 1.4 Actores

| Actor | Descripción |
|---|---|
| Gerente | Administra el sistema, insumos, entradas, reportes y usuarios. |
| Cocinero | Consulta existencias, registra salidas y mermas. |
| Personal de ventas | Controla producción y venta de producto terminado. |
| MySQL | Motor de base de datos. |
| Apache/PHP | Servidor web y capa de aplicación. |

---

## 2. Requerimientos funcionales

### 2.1 Autenticación y seguridad

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-01 | El sistema debe permitir iniciar sesión con usuario y contraseña. | Alta |
| RF-02 | Las contraseñas deben almacenarse con `password_hash()`. | Alta |
| RF-03 | El sistema debe bloquear temporalmente tras 5 intentos fallidos en 5 minutos. | Media |
| RF-04 | La sesión debe expirar tras 30 minutos de inactividad. | Media |
| RF-05 | El cierre de sesión debe ser por POST y validar CSRF. | Alta |
| RF-06 | El sistema debe validar permisos en el servidor para cada módulo. | Alta |

### 2.2 Dashboard

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-07 | El dashboard debe mostrar indicadores de inventario y mermas. | Alta |
| RF-08 | Debe incluir gráficas de entradas/salidas, categorías y stock bajo. | Media |
| RF-09 | Debe mostrar movimientos recientes y alertas. | Media |
| RF-10 | El rol ventas debe ver un dashboard enfocado en producto terminado. | Media |

### 2.3 Insumos

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-11 | El gerente debe poder crear, editar, ajustar y desactivar insumos. | Alta |
| RF-12 | El cocinero debe poder consultar insumos y existencias. | Alta |
| RF-13 | El nombre del insumo debe ser único. | Media |
| RF-14 | La baja debe ser lógica y conservar el historial. | Alta |
| RF-15 | No se debe eliminar un insumo con existencia. | Alta |
| RF-16 | El ajuste de inventario debe ser exclusivo del gerente y quedar auditado. | Alta |

### 2.4 Entradas

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-17 | El gerente debe registrar entradas con cantidad, fecha, lote y usuario. | Alta |
| RF-18 | Toda entrada debe aumentar el stock automáticamente. | Alta |
| RF-19 | Toda entrada debe generar un lote. | Alta |
| RF-20 | Los insumos perecederos deben exigir fecha de caducidad. | Alta |
| RF-21 | No se permiten cantidades menores o iguales a cero. | Alta |
| RF-22 | No se permiten fechas de ingreso futuras. | Media |

### 2.5 Salidas

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-23 | Gerente y cocinero deben registrar salidas. | Alta |
| RF-24 | El sistema debe impedir salidas mayores al stock. | Alta |
| RF-25 | El stock debe disminuir automáticamente. | Alta |
| RF-26 | El consumo de lotes debe ser FEFO. | Alta |
| RF-27 | Cada salida debe guardar fecha, insumo, cantidad, destino y usuario. | Alta |

### 2.6 Mermas

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-28 | Gerente y cocinero deben registrar mermas con motivo y descripción. | Alta |
| RF-29 | El sistema debe impedir mermas mayores al stock. | Alta |
| RF-30 | El stock debe disminuir automáticamente. | Alta |
| RF-31 | El formulario debe mostrar lotes caducados como apoyo. | Media |

### 2.7 Alertas

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-32 | El sistema debe mostrar insumos en stock mínimo o agotados. | Alta |
| RF-33 | Debe mostrar lotes caducados y próximos a caducar. | Alta |
| RF-34 | Debe sugerir cantidades de reabastecimiento. | Media |
| RF-35 | Las alertas deben actualizarse en tiempo real. | Alta |
| RF-36 | El menú debe mostrar el total de alertas. | Baja |

### 2.8 Producto terminado

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-37 | El gerente debe administrar el catálogo de productos. | Alta |
| RF-38 | Gerente y ventas deben registrar producción y venta. | Alta |
| RF-39 | El sistema debe impedir vender más de lo disponible. | Alta |
| RF-40 | Solo el gerente debe ajustar la existencia de producto. | Alta |
| RF-41 | La baja de producto debe ser lógica y bloquearse con stock. | Media |

### 2.9 Reportes

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-42 | El gerente debe consultar 8 reportes con filtros. | Alta |
| RF-43 | Los reportes deben incluir resumen y detalle. | Media |
| RF-44 | Debe existir impresión con encabezado institucional. | Media |
| RF-45 | Debe existir exportación CSV compatible con Excel. | Alta |
| RF-46 | Los reportes deben respetar los filtros aplicados. | Alta |

### 2.10 Usuarios

| ID | Requerimiento | Prioridad |
|---|---|---|
| RF-47 | El gerente debe crear, editar y desactivar usuarios. | Alta |
| RF-48 | El gerente debe restablecer contraseñas. | Alta |
| RF-49 | Las contraseñas deben tener 8+ caracteres, una letra y un número. | Media |
| RF-50 | No se debe desactivar la propia cuenta. | Alta |
| RF-51 | No se debe dejar el sistema sin un gerente activo. | Alta |

---

## 3. Requerimientos no funcionales

| ID | Requerimiento | Criterio |
|---|---|---|
| RNF-01 | Usabilidad | Interfaz responsiva, menús claros, mensajes de éxito/error y confirmaciones. |
| RNF-02 | Rendimiento | Respuesta menor a 2 segundos en consultas normales con 1,000 registros. |
| RNF-03 | Compatibilidad | PHP 8.1+, MySQL 8.0+/MariaDB 10.4+, navegadores modernos. |
| RNF-04 | Seguridad | PDO preparado, CSRF, sesiones seguras, hash de contraseñas, control de roles. |
| RNF-05 | Integridad | Transacciones y bloqueos en operaciones de inventario. |
| RNF-06 | Mantenibilidad | Código modular, funciones compartidas y documentación. |
| RNF-07 | Disponibilidad | Ejecución local en XAMPP sin dependencias externas. |
| RNF-08 | Portabilidad | Ruta base autodetectada y configuración por variables de entorno. |
| RNF-09 | Accesibilidad | Etiquetas de formulario, contraste y navegación por teclado. |
| RNF-10 | Respaldo | Base de datos exportable con `mysqldump`. |

---

## 4. Arquitectura

### 4.1 Patrón

Arquitectura **cliente-servidor en capas**:

```
Navegador (HTML5, CSS3, Bootstrap 5, JS, Chart.js)
        │
        ▼
Apache + PHP (controladores y vistas)
        │
        ▼
PDO (consultas preparadas)
        │
        ▼
MySQL / MariaDB (datos, restricciones y transacciones)
```

### 4.2 Componentes

| Capa | Archivos | Responsabilidad |
|---|---|---|
| Configuración | `config/config.php`, `config/conexion.php` | Constantes, sesión, PDO, zona horaria. |
| Seguridad | `includes/auth.php`, `includes/funciones.php` | Autenticación, roles, CSRF, validaciones. |
| Presentación | `includes/header.php`, `sidebar.php`, `footer.php`, `assets/` | Layout, tema visual, scripts. |
| Dominio | `insumos/`, `entradas/`, `salidas/`, `mermas/`, `productos/` | Reglas de negocio y formularios. |
| Consulta | `alertas/`, `reportes/`, `dashboard/` | Indicadores, alertas y reportes. |
| Administración | `usuarios/` | Cuentas y roles. |
| Datos | `database/` | Esquema, datos de ejemplo y semilla. |

### 4.3 Flujo de una operación de inventario

1. El usuario envía el formulario por POST.
2. Se valida el token CSRF.
3. Se validan los datos del lado del servidor.
4. Se abre una transacción.
5. Se bloquea la fila del insumo con `SELECT ... FOR UPDATE`.
6. Se verifica el stock y se calcula el nuevo valor.
7. Se inserta el registro de negocio (entrada, salida o merma).
8. Se actualizan lotes (FEFO) y stock.
9. Se inserta el movimiento de auditoría.
10. Se confirma la transacción y se redirige con un mensaje flash.

---

## 5. Modelo de datos

### 5.1 Tablas

| Tabla | Propósito | Claves |
|---|---|---|
| `roles` | Catálogo de roles. | PK `id_rol`, UQ `nombre` |
| `usuarios` | Cuentas del sistema. | PK `id_usuario`, FK `id_rol`, UQ `usuario`, UQ `correo` |
| `categorias` | Categorías de insumos y bandera de perecedero. | PK `id_categoria`, UQ `nombre` |
| `insumos` | Materias primas y stock actual. | PK `id_insumo`, FK `id_categoria`, UQ `nombre` |
| `entradas` | Recepciones de mercancía. | PK `id_entrada`, FK `id_insumo`, FK `id_usuario` |
| `lotes` | Trazabilidad y caducidad. | PK `id_lote`, FK `id_insumo`, FK `id_entrada` |
| `salidas` | Consumo de materia prima. | PK `id_salida`, FK `id_insumo`, FK `id_usuario` |
| `mermas` | Pérdidas y desperdicios. | PK `id_merma`, FK `id_insumo`, FK `id_usuario` |
| `movimientos_inventario` | Auditoría de cambios de stock. | PK `id_movimiento`, FK `id_insumo`, FK `id_usuario` |
| `productos` | Catálogo de producto terminado. | PK `id_producto`, UQ `nombre` |
| `movimientos_producto` | Auditoría de producción/venta/ajuste. | PK `id_movimiento_producto`, FK `id_producto`, FK `id_usuario` |

### 5.2 Normalización

El modelo está en **tercera forma normal (3FN)**:

- Cada tabla tiene una clave primaria simple.
- Los atributos dependen de la clave completa.
- No hay dependencias transitivas: categorías, roles y usuarios se referencian por clave foránea.
- Los datos históricos se conservan en tablas de movimientos.

### 5.3 Restricciones

- `CHECK` para cantidades positivas y stocks no negativos.
- Claves foráneas con `ON UPDATE CASCADE` y `ON DELETE RESTRICT`.
- `UNIQUE` en nombres de insumos, productos, usuarios y correos.
- `ENUM` para unidades, tipos de movimiento y motivos de merma.
- InnoDB para transacciones y bloqueos.

---

## 6. Reglas de negocio

1. Toda cantidad de entrada, salida o merma debe ser mayor que cero.
2. El stock nunca puede ser negativo.
3. Una salida o merma no puede superar la existencia.
4. Los insumos perecederos requieren caducidad en cada entrada.
5. Cada entrada genera un lote; cada salida o merma consume lotes FEFO.
6. Todo movimiento guarda fecha, insumo, cantidad, usuario y observaciones.
7. Solo el gerente ajusta existencias.
8. Las bajas son lógicas para preservar la auditoría.
9. Un insumo con stock no se elimina; un producto con stock no se desactiva.
10. Las alertas se calculan con el stock y las fechas actuales.
11. No se puede desactivar la propia cuenta ni el último gerente activo.
12. Las contraseñas deben cumplir la política mínima de seguridad.

---

## 7. Criterios de aceptación

El sistema se considera aceptado cuando:

- [ ] Instala en XAMPP siguiendo el manual.
- [ ] Importa la base sin errores.
- [ ] Los tres roles inician sesión y ven solo sus módulos.
- [ ] Se pueden crear, editar y desactivar insumos y productos.
- [ ] Las entradas aumentan el stock y crean lotes.
- [ ] Las salidas y mermas descuentan el stock y respetan FEFO.
- [ ] Se bloquean cantidades inválidas y stock insuficiente.
- [ ] Las alertas reflejan el estado real del inventario.
- [ ] Los 8 reportes funcionan con filtros, impresión y CSV.
- [ ] La administración de usuarios protege al último gerente.
- [ ] Las pruebas de seguridad (CSRF, XSS, SQLi, archivos internos) pasan.
- [ ] La integridad stock/lotes se mantiene sin diferencias.

---

## 8. Restricciones y supuestos

- El sistema se ejecuta en un entorno local XAMPP.
- No se integra con lectores de código de barras ni básculas.
- La moneda es el peso mexicano (MXN), solo para precios de producto.
- La zona horaria es `America/Mexico_City`.
- El navegador debe permitir cookies de sesión.
- Los datos de ejemplo son ficticios y deben reemplazarse en producción.
- El usuario administrador de MySQL debe tener permisos sobre la base.

---

## 9. Trazabilidad

| Objetivo | Requerimientos |
|---|---|
| Control de acceso | RF-01 a RF-06, RF-47 a RF-51 |
| Inventario de materias primas | RF-11 a RF-22 |
| Consumo y pérdidas | RF-23 a RF-31 |
| Alertas | RF-32 a RF-36 |
| Producto terminado | RF-37 a RF-41 |
| Información gerencial | RF-42 a RF-46 |
| Calidad y seguridad | RNF-01 a RNF-10 |

---

© 2026 Hojaldras “La Ordaz” · Versión 1.0.0
