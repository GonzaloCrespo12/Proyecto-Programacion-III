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
* **Dark Mode:** Tema oscuro persistente (guardado en LocalStorage).
* **Sidebar Inteligente:** Muestra el usuario logueado y oculta botones redundantes según la pantalla actual.
* **Feedback:** Mensajes "Flash" de éxito o error tras cada operación.

---

## ⚙️ Instalación y Puesta en Marcha

### 1. Base de Datos
Importar el archivo `base_datos_clientes.sql` incluido en este repositorio. Esto creará las tablas:
* `usuarios` (Nueva)
* `clientes`
* `especialidades`
* `cliente_especialidad`

### 2. Configuración
Revisar el archivo `includes/config.php` y ajustar la constante `BASE_URL` si es necesario (por defecto configurado para `localhost/Proyecto-Programacion-III`).

### 3. Usuario de Prueba
El sistema ya cuenta con un administrador creado en la base de datos:

* **Email:** `admin@demo.local`
* **Contraseña:** `admin123`

> **Nota:** También se incluye el script `auth/crearAdmin.php` para generar nuevos administradores manualmente si fuera necesario.

---

## 🛠 Tecnologías Utilizadas
* **Backend:** PHP 8 (PDO, Sessions).
* **Frontend:** Bootstrap 5.3, FontAwesome.
* **Base de Datos:** MySQL / MariaDB.
