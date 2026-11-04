# Nutri-Fluidos — Sistema POS a la medida

Sistema web de punto de venta y administración desarrollado a la medida para **Nutri-Fluidos**, una empresa de sistemas de riego y agrofluidos en Guatemala. Centraliza ventas, inventario, clientes, pedidos, créditos y pagos en un solo lugar.

> Proyecto en producción, desarrollado y mantenido por [SOLTEC](https://soltec.click) (Soluciones Tecnológicas).

## Módulos

| Módulo | Qué hace |
|---|---|
| **Dashboard** | Resumen del negocio con gráficas de ventas e indicadores |
| **Ventas** | Registro de ventas al contado y al crédito, con comprobante en PDF |
| **Productos** | Catálogo con fotos, precios y existencias |
| **Kardex** | Historial de movimientos de inventario por producto, exportable a PDF |
| **Clientes** | Fichas de clientes, historial de compras y estado de cuenta |
| **Pedidos** | Seguimiento de pedidos de clientes |
| **Abonos** | Control de créditos y pagos parciales, con recibo en PDF |
| **Facturas por pagar** | Cuentas por pagar a proveedores, con respaldo de la factura |
| **Proveedores** | Directorio y exportación de proveedores |
| **Usuarios y permisos** | Cuentas con permisos por módulo |
| **Informes** | Reportes de ventas, stock bajo y existencias, en PDF y en archivo exportable |
| **Calculadora** | Herramienta de cálculo integrada al sistema |

## Tecnologías

- **Backend:** PHP y MySQL
- **Frontend:** HTML, CSS, JavaScript, Bootstrap 5, jQuery
- **Tablas y gráficas:** DataTables, Chart.js
- **Documentos:** FPDF (comprobantes, recibos e informes en PDF)
- **Alertas e iconos:** SweetAlert2, Font Awesome
- **Servidor:** Apache (`.htaccess`) en Hostinger, con despliegue automático desde GitHub

## Seguridad

- Contraseñas almacenadas con hash.
- Control de sesión y permisos por módulo.
- Las credenciales de la base de datos, las copias de seguridad y los archivos que suben los usuarios (facturas, comprobantes, fotos) **no forman parte de este repositorio**; viven únicamente en el servidor.
- Las carpetas de subidas están protegidas para que no se pueda ejecutar código en ellas.

## Estructura

```
/
├── index.php            Entrada al sistema (inicio de sesión)
├── .htaccess            Reglas de Apache y protección de archivos
└── nutrifluidos/
    ├── *.php            Pantallas de cada módulo
    ├── php/             Lógica del servidor (consultas, PDF, exportaciones)
    ├── js/ · css/       Interfaz de cada módulo
    └── fpdf/            Generación de PDF
```

## Autor

Desarrollado por **Gilmar Tello Vásquez** — [@Gilmar-Tello](https://github.com/Gilmar-Tello) · SOLTEC
