# Sistema de Gestión de Clientes (CRM) con Autenticación Segura

Trabajo Práctico Final para la asignatura **Programación III**.
Este proyecto es una evolución del sistema CRUD desarrollado en clase, incorporando un módulo completo de seguridad, autenticación y control de sesiones.

## 👥 Integrantes del Grupo
* **Mateo Cluchinsky**
* **Gonzalo Crespo**
* **Martin Ñañez**

---

## 🚀 Nuevas Funcionalidades (Login & Seguridad)

A diferencia de la versión anterior, este sistema incluye una capa robusta de protección:

### 🔐 Módulo de Autenticación
* **Login Seguro:** Verificación de credenciales contra base de datos.
* **Hashing de Contraseñas:** Uso de `password_hash()` (Bcrypt) para no almacenar claves en texto plano.
* **Protección contra Fuerza Bruta:** Sistema de *Rate Limiting* que bloquea la IP del atacante tras 5 intentos fallidos (usando almacenamiento temporal JSON para no saturar la BD).
* **Middleware de Sesión:** Archivo `require_login.php` que protege todas las rutas privadas, redirigiendo a intrusos al login.

### 🛡️ Seguridad Ofensiva/Defensiva
* **Protección CSRF:** Todos los formularios (Alta, Edición, Baja) incluyen un token único de sesión para evitar ataques de falsificación de peticiones.
* **Seguridad de Cookies:** Configuración de `HttpOnly` y `SameSite` en `config.php`.
* **Roles de Usuario:**
* **Admin:** Control total (Puede crear, editar y **eliminar**).
* **Operador:** Acceso restringido (Solo lectura y edición, sin permisos destructivos).

### 🎨 Mejoras de Interfaz (UI/UX)
* **Toggle Tema Claro/Oscuro:** Botón en la cabecera para alternar entre tema claro y oscuro, con preferencia persistente (guardada en LocalStorage).
* **Variables CSS Dinámicas:** Transiciones suaves entre temas sin recargar la página.
* **Sidebar Inteligente:** Muestra el usuario logueado y oculta botones redundantes según la pantalla actual.
* **Feedback:** Mensajes "Flash" de éxito o error tras cada operación.
* **Diseño Responsivo:** Bootstrap 5 para una interfaz adaptable a todos los dispositivos.

---

## ⚙️ Instalación y Puesta en Marcha

### 1. Base de Datos
> **⚠️ IMPORTANTE:** Asignar el nombre de la base de datos en `includes/config.php` (constante `DB_NAME`).

1. Crear una nueva base de datos en MySQL/MariaDB (ej: `crm_database`).
2. Importar el archivo `base_datos_clientes.sql` incluido en este repositorio. Esto creará las tablas:
   - `usuarios` (administrador y operadores)
   - `clientes` (gestión de clientes)
   - `especialidades` (categorías/servicios)
   - `cliente_especialidad` (relación muchos-a-muchos)

### 2. Configuración
Editar el archivo `includes/config.php` y asignar los valores correctos:
* `DB_NAME`: Nombre de la base de datos creada (ej: `crm_database`)
* `DB_USER`: Usuario de MySQL (por defecto: `root`)
* `DB_PASS`: Contraseña de MySQL (por defecto: vacío en XAMPP)
* `BASE_URL`: Mantener como está o ajustar según sea necesario

### 3. Usuario de Prueba
El sistema incluye un administrador preconfigurado en la base de datos:

| Campo          | Valor             |
|----------------|-------------------|
| **Email**      | `admin@demo.local`|
| **Contraseña** | `admin123`        |
| **Rol**        |Admin(acceso total)| 

## 🛠 Tecnologías Utilizadas
* **Backend:** PHP 8 (PDO, Sessions, Bcrypt).
* **Frontend:** Bootstrap 5.3, FontAwesome 6.4, CSS Variables (temas dinámicos).
* **Base de Datos:** MySQL / MariaDB.
* **Seguridad:** CSRF Tokens, Rate Limiting, Password Hashing, HttpOnly Cookies.

---

## 📁 Estructura del Proyecto

```
proyectoProgramacionIII/
├── index.php                   # Dashboard principal (protegido)
├── auth/
│   ├── login.php               # Formulario y lógica de autenticación
│   └── logout.php              # Cierre de sesión
├── clientes/
│   ├── index.php               # Listado de clientes
│   ├── crear.php               # Formulario para nuevo cliente
│   ├── editar.php              # Edición de cliente existente
│   └── eliminar.php            # Eliminación de cliente (solo Admin)
├── includes/
│   ├── config.php              # Configuración global (BD, constantes, funciones)
│   ├── header.php              # Encabezado HTML, navbar, variables CSS
│   ├── footer.php              # Pie de página, scripts comunes
│   └── require_login.php       # Middleware de protección de sesión
├── uploads/                    # Directorio para imágenes de clientes
├── base_datos_clientes.sql     # Dump de la base de datos (importar primero)
└── README.md                   # Este archivo
```

---

## 🔄 Flujo de Uso

1. **Acceso:** Dirigirse a `http://localhost/proyectoProgramacionIII/auth/login.php`
2. **Autenticación:** Ingresar credenciales (email + contraseña)
3. **Dashboard:** Tras login exitoso, se redirige a `index.php` con el panel de control
4. **Gestión de Clientes:**
   - **Ver:** Acceder a "Ver Clientes" para listar todos los clientes activos
   - **Crear:** Rellenar formulario con datos e imagen (validación automática)
   - **Editar:** Modificar datos de cliente existente (solo Admin/Operador)
   - **Eliminar:** Baja lógica de cliente (solo Admin)
5. **Tema:** Usar el botón de toggle (luna/sol) en la cabecera para alternar tema
6. **Logout:** Cerrar sesión desde el menú de perfil (esquina inferior del sidebar)

---

## 🔒 Notas de Seguridad

- Las contraseñas se almacenan con **Bcrypt** (nunca en texto plano).
- Todos los formularios incluyen **CSRF tokens** únicos por sesión.
- Las sesiones están configuradas con **HttpOnly** y **SameSite=Strict**.
- Rate Limiting activo: máximo **5 intentos fallidos** de login antes de bloqueo temporal.
- El middleware `require_login.php` protege todas las rutas privadas automáticamente.
