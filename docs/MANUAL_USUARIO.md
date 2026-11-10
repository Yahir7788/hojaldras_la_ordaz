# Manual de usuario

**Hojaldras “La Ordaz” — Sistema de Gestión de Almacén e Inventarios**

Este manual explica el uso diario del sistema según el rol: **gerente**, **cocinero** y **personal de ventas**.

---

## 1. Iniciar sesión

1. Abre la URL del sistema, por ejemplo:
   ```
   http://localhost/hojaldras_la_ordaz/
   ```
2. Escribe tu **usuario** y **contraseña**.
3. Pulsa **Iniciar sesión**.

### Recomendaciones

- Usa el botón del ojo para mostrar u ocultar la contraseña.
- Si fallas 5 veces en 5 minutos, el acceso se bloquea temporalmente.
- La sesión expira tras 30 minutos de inactividad.
- Cierra sesión desde el menú superior derecho → **Cerrar sesión**.

### Usuarios de prueba

| Rol | Usuario | Contraseña |
|---|---|---|
| Gerente | `gerente` | `Gerente123!` |
| Cocinero | `cocinero` | `Cocinero123!` |
| Ventas | `ventas` | `Ventas123!` |

---

## 2. Dashboard

Al entrar verás el panel principal con:

- **Indicadores**: total de insumos, stock bajo, agotados, mermas del periodo, valor del inventario y productos disponibles.
- **Gráficas**: entradas vs. salidas, distribución por categoría, stock bajo y movimientos recientes.
- **Alertas recientes**: insumos agotados o próximos a caducar.
- **Accesos rápidos** según tu rol.

El dashboard del rol **ventas** se enfoca en producto terminado: existencias, producción, ventas y productos por debajo del mínimo.

---

## 3. Módulo de insumos (gerente y cocinero)

### Consultar

- **Insumos** muestra nombre, categoría, unidad, stock actual, stock mínimo, estado y próxima caducidad.
- Usa los filtros de **búsqueda**, **categoría** y **estado**.
- Las tarjetas superiores resumen total, disponibles, críticos y agotados.

### Crear un insumo (solo gerente)

1. Pulsa **Nuevo insumo**.
2. Captura:
   - Nombre (único).
   - Descripción.
   - Categoría.
   - Unidad de medida (`kg`, `g`, `L`, `ml`, `pza`, `caja`, `paquete`, `bolsa`, `rollo`).
   - Stock mínimo.
   - Cantidad inicial (puede ser 0).
   - Fecha de caducidad si la categoría es perecedera y hay existencia inicial.
3. Pulsa **Guardar insumo**.
4. Si hay cantidad inicial, el sistema crea un lote y un movimiento de ajuste auditado.

### Editar un insumo (solo gerente)

1. En la fila del insumo, pulsa **Editar**.
2. Modifica nombre, descripción, categoría, unidad o stock mínimo.
3. La existencia **no se edita aquí**; se corrige con **Ajustar stock**.
4. Guarda los cambios.

### Ajustar stock (solo gerente)

1. Pulsa **Ajustar** en la fila del insumo.
2. Elige el tipo de ajuste:
   - **Fijar**: deja la existencia exactamente en la cantidad indicada.
   - **Sumar**: agrega cantidad al stock actual.
   - **Restar**: descuenta cantidad del stock actual.
3. Captura la cantidad, la fecha de caducidad (si el ajuste suma a un perecedero) y el motivo.
4. Pulsa **Guardar ajuste**.

El ajuste queda registrado en el historial con usuario, fecha, stock anterior y stock nuevo. Si el ajuste reduce existencias, se consumen lotes FEFO.

### Eliminar / reactivar (solo gerente)

- Un insumo **con existencia** no puede eliminarse. Primero registra una merma o un ajuste a cero.
- La eliminación es **lógica**: el insumo se desactiva y su historial se conserva.
- Puedes reactivarlo desde el filtro **Inactivos** con el botón **Reactivar**.

---

## 4. Entradas de almacén (solo gerente)

1. Ve a **Entradas → Registrar entrada**.
2. Selecciona el insumo.
3. Captura la cantidad recibida (mayor que cero).
4. Indica la fecha de ingreso (no puede ser futura).
5. Si el insumo es perecedero, la **fecha de caducidad es obligatoria**.
6. Captura el **número de lote**.
7. Agrega observaciones (proveedor, factura, condiciones).
8. Pulsa **Registrar entrada**.

### Resultado

- El stock del insumo aumenta automáticamente.
- Se crea un lote con la cantidad recibida y su caducidad.
- Se registra un movimiento de tipo **Entrada** con el usuario responsable.

### Consultar entradas

La lista permite filtrar por fecha, insumo, categoría y lote. Cada fila muestra cantidad, unidad, lote, caducidad y usuario.

---

## 5. Salidas de almacén (gerente y cocinero)

1. Ve a **Salidas → Registrar salida**.
2. Selecciona el insumo (solo aparecen los que tienen existencia).
3. Captura la cantidad solicitada.
4. Indica la fecha (no futura).
5. Escribe el destino:
   - Cocina - preparación
   - Cocina - panadería
   - Cocina - producción
   - Mostrador
   - Otro destino
6. Agrega observaciones si es necesario.
7. Pulsa **Registrar salida**.

### Validaciones

- No se permiten cantidades menores o iguales a cero.
- No se puede retirar más de lo disponible.
- El sistema descuenta primero los lotes con caducidad más próxima (**FEFO**).
- El stock y los lotes se actualizan en una sola transacción.

---

## 6. Mermas (gerente y cocinero)

1. Ve a **Mermas → Registrar merma**.
2. Selecciona el insumo.
3. Captura la cantidad perdida.
4. Elige el motivo:
   - Caducidad
   - Deterioro
   - Desperdicio en producción
   - Producto dañado
5. Indica la fecha.
6. Describe la merma (mínimo 5 caracteres).
7. Pulsa **Registrar merma**.

El inventario se reduce automáticamente y se registra un movimiento de tipo **Merma**. En el panel lateral se muestran los lotes caducados para ayudarte a identificar producto vencido.

---

## 7. Alertas (gerente y cocinero)

El módulo **Alertas** se actualiza en tiempo real y muestra:

- **Stock crítico**: insumos por debajo del mínimo.
- **Agotados**: existencia en cero.
- **Próximos a caducar**: lotes que vencen en 7, 15 o 30 días.
- **Caducados**: lotes vencidos con existencia.
- **Sugerencias de reabastecimiento**: cantidad recomendada para volver al doble del mínimo.

Desde cada alerta puedes ir directamente a registrar una entrada, salida o merma.

El número rojo junto a **Alertas** en el menú indica el total de alertas activas.

---

## 8. Producto terminado (gerente y ventas)

Este módulo controla hojaldres, volovanes, pizzas y demás productos de venta.

### Consultar

- Tarjetas con existencias, productos bajo mínimo, producción y ventas del día.
- Filtros por búsqueda y estado.
- Historial reciente de movimientos.

### Registrar producción

1. Pulsa **Producción** en la tarjeta del producto (o **Nuevo movimiento → Producción**).
2. Captura las unidades producidas.
3. Agrega observaciones.
4. Pulsa **Registrar producción**.

El stock aumenta automáticamente y se registra el movimiento con el usuario.

### Registrar venta

1. Pulsa **Venta** en la tarjeta del producto.
2. Captura las unidades vendidas.
3. El sistema impide vender más de lo disponible.
4. Pulsa **Registrar venta**.

El stock disminuye automáticamente.

### Ajustar existencia (solo gerente)

1. Pulsa **Ajustar**.
2. Captura la nueva existencia exacta.
3. Describe el motivo del ajuste.
4. Pulsa **Guardar ajuste**.

El movimiento queda auditado con stock anterior y nuevo.

### Crear / editar / desactivar productos (solo gerente)

- **Nuevo producto**: nombre, descripción, precio, existencia inicial y stock mínimo.
- **Editar**: nombre, descripción, precio, stock mínimo y estatus.
- No se puede desactivar un producto con existencia; primero registra una venta o un ajuste a cero.
- La desactivación es lógica y conserva el historial.

---

## 9. Reportes (solo gerente)

1. Ve a **Reportes**.
2. Elige una pestaña:
   - Inventario actual
   - Movimientos de inventario
   - Entradas de almacén
   - Salidas de almacén
   - Mermas y pérdidas
   - Stock bajo y agotados
   - Control de caducidades
   - Producto terminado
3. Ajusta los filtros disponibles (fechas, estado, categoría, insumo, motivo, producto, vencimiento).
4. Pulsa **Aplicar**.
5. Revisa el resumen y la tabla.
6. Pulsa **Imprimir** para generar una vista limpia con el encabezado institucional.
7. Pulsa **Descargar CSV** para abrir el reporte en Excel.

El CSV conserva los filtros aplicados y usa UTF-8 con BOM y separador `;` para compatibilidad con Excel en español.

---

## 10. Usuarios (solo gerente)

### Crear usuario

1. Ve a **Usuarios → Nuevo usuario**.
2. Captura nombre completo, usuario, correo, rol y estatus.
3. Define una contraseña de al menos 8 caracteres, con una letra y un número.
4. Confirma la contraseña.
5. Pulsa **Crear cuenta**.

### Editar usuario

- Permite cambiar nombre, usuario, correo, rol y estatus.
- No puedes cambiar tu propio rol ni desactivar tu propia cuenta.
- El sistema impide dejar la aplicación sin al menos un gerente activo.

### Restablecer contraseña

1. Pulsa **Restablecer** en la fila del usuario.
2. Captura la nueva contraseña y su confirmación.
3. Pulsa **Actualizar contraseña**.

### Desactivar / reactivar

- La desactivación es lógica y conserva el historial.
- Un usuario inactivo no puede iniciar sesión.
- Puedes reactivarlo desde el mismo módulo.

---

## 11. Cerrar sesión

1. Pulsa tu nombre en la esquina superior derecha.
2. Selecciona **Cerrar sesión**.
3. El sistema destruye la sesión y regresa al login.

---

## 12. Preguntas frecuentes

**¿Por qué no veo el módulo de reportes?**
Solo el rol gerente tiene acceso. Inicia sesión con una cuenta de gerente.

**¿Por qué no puedo eliminar un insumo?**
Tiene existencia. Registra una merma o un ajuste a cero antes de eliminarlo.

**¿Por qué no puedo desactivar un producto?**
Tiene unidades disponibles. Registra una venta o un ajuste a cero.

**¿Por qué me pide fecha de caducidad?**
La categoría del insumo es perecedera. Es obligatoria para poder rastrear lotes.

**¿Puedo recuperar un insumo eliminado?**
Sí. Filtra por **Inactivos** y pulsa **Reactivar**.

**¿Dónde veo quién hizo cada movimiento?**
En el historial de movimientos y en los reportes, cada fila incluye el usuario responsable.

**¿El sistema borra el historial?**
No. Las bajas son lógicas y los movimientos se conservan para auditoría.

---

© 2026 Hojaldras “La Ordaz” · Versión 1.0.0
