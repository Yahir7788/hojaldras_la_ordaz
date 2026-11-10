# Guía de pruebas

**Hojaldras “La Ordaz” — Sistema de Gestión de Almacén e Inventarios**

Esta guía sirve para validar la instalación y verificar que las funciones principales operan correctamente. Puede usarse como lista de aceptación antes de entregar el sistema o después de una actualización.

---

## 1. Preparación

1. Importa `database/hojaldras_la_ordaz.sql` en una base limpia.
2. Inicia Apache y MySQL en XAMPP.
3. Abre el sistema en el navegador.
4. Ten a mano los tres usuarios de prueba.
5. Abre la consola del navegador (F12) para detectar errores de JavaScript.

---

## 2. Pruebas de acceso y roles

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| A1 | Login gerente | Entrar con `gerente` / `Gerente123!` | Llega al dashboard con menú completo |
| A2 | Login cocinero | Entrar con `cocinero` / `Cocinero123!` | Dashboard sin Reportes, Usuarios, Entradas ni Producto terminado |
| A3 | Login ventas | Entrar con `ventas` / `Ventas123!` | Dashboard de producto terminado; solo módulo Producto terminado |
| A4 | Contraseña incorrecta | Intentar con contraseña errónea | Mensaje de error; no inicia sesión |
| A5 | Bloqueo por intentos | Fallar 5 veces seguidas | Mensaje de bloqueo temporal |
| A6 | Acceso directo sin sesión | Abrir `/dashboard/index.php` sin login | Redirige al login |
| A7 | Acceso directo por rol | Cocinero abre `/reportes/index.php` | Redirige al dashboard con aviso de permisos |
| A8 | Cierre de sesión | Menú de usuario → Cerrar sesión | Regresa al login; el dashboard vuelve a pedir sesión |
| A9 | Expiración | Esperar 30 minutos inactivo y recargar | Redirige al login con aviso de sesión expirada |

---

## 3. Pruebas de insumos

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| B1 | Listar insumos | Abrir Insumos | Tabla con 20 insumos de ejemplo y filtros |
| B2 | Crear insumo | Nuevo insumo con datos válidos | Se guarda, aparece en la lista y muestra notificación |
| B3 | Nombre duplicado | Crear insumo con nombre existente | Error “Ya existe un insumo con ese nombre” |
| B4 | Editar insumo | Cambiar nombre, categoría o mínimo | Cambios visibles y persistidos |
| B5 | Ajustar stock | Fijar, sumar o restar cantidad | Stock cambia y se registra movimiento de ajuste |
| B6 | Ajuste negativo | Restar más de lo disponible | Error; no se modifica el inventario |
| B7 | Eliminar con stock | Intentar eliminar insumo con existencia | Error; no se elimina |
| B8 | Eliminar sin stock | Ajustar a cero y eliminar | Baja lógica; desaparece de activos |
| B9 | Reactivar | Filtrar inactivos y reactivar | El insumo vuelve a estar activo |
| B10 | Validación de cantidad | Capturar cantidad 0 o negativa | Error de validación |

---

## 4. Pruebas de entradas

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| C1 | Entrada válida | Insumo seco, cantidad 10, lote nuevo | Stock +10; lote creado; movimiento registrado |
| C2 | Entrada perecedera sin caducidad | Insumo refrigerado sin fecha | Error; no se guarda |
| C3 | Fecha futura | Fecha de ingreso posterior a hoy | Error de validación |
| C4 | Cantidad cero | Cantidad 0 | Error de validación |
| C5 | Lote obligatorio | Dejar lote vacío | Error de validación |
| C6 | Suma de lotes | Comparar stock con suma de lotes | Deben coincidir |
| C7 | Filtros | Filtrar por fecha, insumo y categoría | Resultados coherentes |

---

## 5. Pruebas de salidas

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| D1 | Salida válida | Retirar 3 kg de un insumo con stock | Stock -3; movimiento de salida |
| D2 | Salida mayor al stock | Solicitar más de lo disponible | Error; no se modifica el inventario |
| D3 | Cantidad cero | Capturar 0 | Error de validación |
| D4 | Fecha futura | Fecha posterior a hoy | Error de validación |
| D5 | Consumo FEFO | Dos lotes con distinta caducidad | Se consume primero el que caduca antes |
| D6 | Usuario | Registrar como cocinero | El movimiento guarda al cocinero |
| D7 | Permiso | Ventas intenta abrir salidas | Acceso denegado |

---

## 6. Pruebas de mermas

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| E1 | Merma válida | 1.5 kg, motivo Deterioro, descripción | Stock -1.5; movimiento de merma |
| E2 | Merma mayor al stock | Cantidad superior a la existencia | Error; no se guarda |
| E3 | Motivo inválido | Enviar motivo manipulado | Error de validación |
| E4 | Descripción corta | Menos de 5 caracteres | Error de validación |
| E5 | Lotes caducados | Abrir formulario | Panel con lotes vencidos |
| E6 | Actualización de alertas | Registrar merma y revisar alertas | El stock y las alertas se actualizan |

---

## 7. Pruebas de alertas

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| F1 | Stock bajo | Abrir Alertas | Insumos con existencia ≤ mínimo |
| F2 | Agotados | Revisar insumo con stock 0 | Aparece como agotado |
| F3 | Caducidades | Revisar lotes próximos | Agrupados por 7, 15 y 30 días |
| F4 | Caducados | Revisar lote vencido | Aparece como caducado |
| F5 | Contador del menú | Comparar badge con total de alertas | Deben coincidir |
| F6 | Acciones | Pulsar acción de una alerta | Lleva al formulario correspondiente |

---

## 8. Pruebas de producto terminado

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| G1 | Crear producto | Nombre, precio, stock inicial y mínimo | Se guarda y aparece en el catálogo |
| G2 | Producción | Registrar 12 unidades | Stock +12; movimiento de producción |
| G3 | Venta | Registrar 5 unidades | Stock -5; movimiento de venta |
| G4 | Venta sin stock | Vender más de lo disponible | Error; no se descuenta |
| G5 | Ajuste gerente | Fijar existencia en 10 | Stock = 10; movimiento de ajuste |
| G6 | Ajuste ventas | Ventas intenta ajustar | Acceso denegado |
| G7 | Desactivar con stock | Intentar desactivar producto con unidades | Error |
| G8 | Desactivar sin stock | Ajustar a cero y desactivar | Baja lógica |
| G9 | Reactivar | Reactivar producto | Vuelve al catálogo activo |
| G10 | Permiso cocinero | Cocinero abre Producto terminado | Acceso denegado |

---

## 9. Pruebas de reportes

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| H1 | Inventario | Abrir reporte Inventario | Tabla con stock, mínimo, estado y caducidad |
| H2 | Movimientos | Filtrar por fecha y tipo | Solo movimientos del periodo y tipo |
| H3 | Entradas | Filtrar por insumo y categoría | Resultados coherentes |
| H4 | Salidas | Filtrar por fecha | Resultados coherentes |
| H5 | Mermas | Filtrar por motivo | Solo mermas del motivo |
| H6 | Stock bajo | Abrir reporte | Solo insumos en mínimo o agotados |
| H7 | Caducidades | Filtrar 7 días | Lotes que vencen en 7 días o ya vencidos |
| H8 | Producto terminado | Filtrar por venta y producto | Solo ventas del producto |
| H9 | Imprimir | Pulsar Imprimir | Vista limpia con encabezado institucional |
| H10 | CSV | Pulsar Descargar CSV | Archivo `reporte_tipo_fecha.csv` con BOM y `;` |
| H11 | Permiso | Cocinero intenta exportar | Acceso denegado |

---

## 10. Pruebas de usuarios

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| I1 | Crear usuario | Datos válidos y contraseña segura | Cuenta creada con hash |
| I2 | Contraseña débil | Menos de 8 caracteres o sin número | Error de validación |
| I3 | Confirmación distinta | Contraseñas no coinciden | Error |
| I4 | Usuario duplicado | Repetir nombre de usuario | Error |
| I5 | Correo duplicado | Repetir correo | Error |
| I6 | Editar usuario | Cambiar nombre o rol | Cambios persistidos |
| I7 | Restablecer contraseña | Nueva contraseña válida | El usuario puede iniciar con ella |
| I8 | Desactivar | Desactivar cuenta | No puede iniciar sesión |
| I9 | Reactivar | Reactivar cuenta | Puede iniciar sesión |
| I10 | Autoprotección | Desactivar la propia cuenta | Error |
| I11 | Último gerente | Desactivar al último gerente | Error |
| I12 | Cambio de rol propio | Intentar cambiar el propio rol | Bloqueado |

---

## 11. Pruebas de seguridad

| # | Caso | Pasos | Resultado esperado |
|---|---|---|---|
| J1 | CSRF | Enviar POST sin token o con token inválido | HTTP 419 / sesión expirada |
| J2 | Inyección SQL | Escribir `' OR 1=1 --` en filtros | No altera consultas; no hay error SQL |
| J3 | XSS | Guardar `<script>alert(1)</script>` en un campo | Se muestra escapado, no se ejecuta |
| J4 | Acceso a archivos internos | Abrir `/config/conexion.php` | Bloqueado por Apache (403) |
| J5 | Acceso a base de datos | Abrir `/database/hojaldras_la_ordaz.sql` | Bloqueado por Apache (403) |
| J6 | Sesión | Copiar cookie y cerrar sesión | La cookie anterior ya no sirve |
| J7 | Contraseñas | Revisar tabla `usuarios` | Valores hasheados, nunca texto plano |

---

## 12. Pruebas de integridad

Ejecuta estas consultas en phpMyAdmin o MySQL:

```sql
-- El stock de cada insumo debe coincidir con la suma de sus lotes
SELECT i.id_insumo, i.nombre, i.cantidad_actual,
       COALESCE(SUM(l.cantidad_actual), 0) AS lotes
FROM insumos i
LEFT JOIN lotes l ON l.id_insumo = i.id_insumo
GROUP BY i.id_insumo, i.nombre, i.cantidad_actual
HAVING ABS(i.cantidad_actual - lotes) > 0.0005;

-- No deben existir cantidades negativas
SELECT * FROM insumos WHERE cantidad_actual < 0;
SELECT * FROM lotes WHERE cantidad_actual < 0;
SELECT * FROM productos WHERE stock_actual < 0;

-- Todo movimiento debe tener usuario válido
SELECT COUNT(*) FROM movimientos_inventario m
LEFT JOIN usuarios u ON u.id_usuario = m.id_usuario
WHERE u.id_usuario IS NULL;

-- No debe haber stock sin movimiento de respaldo en el historial
SELECT i.id_insumo, i.nombre
FROM insumos i
LEFT JOIN movimientos_inventario m ON m.id_insumo = i.id_insumo
WHERE i.cantidad_actual > 0 AND m.id_movimiento IS NULL;
```

Todas las consultas de inconsistencias deben devolver **0 filas**.

---

## 13. Resultado de la validación automatizada

Durante el desarrollo se ejecutaron **123 comprobaciones automatizadas** de extremo a extremo con los tres roles, cubriendo:

- Inicio y cierre de sesión.
- Acceso permitido y denegado por rol.
- CRUD de insumos, productos y usuarios.
- Entradas, salidas, mermas y ajustes.
- Consumo FEFO y consistencia stock/lotes.
- Validación de cantidades y caducidades.
- Alertas y reportes con filtros.
- Exportación CSV de los 8 reportes.
- Protección CSRF.
- Protección del último gerente y de la propia cuenta.

Resultado: **123/123 pruebas correctas**.

---

## 14. Registro de incidencias

| Fecha | Módulo | Descripción | Estado |
|---|---|---|---|
| | | | |

---

© 2026 Hojaldras “La Ordaz” · Versión 1.0.0
