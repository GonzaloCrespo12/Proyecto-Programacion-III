1. Experiencia de Usuario (UI/UX) y Accesibilidad
Título: Sistema de Temas Dinámico (Dark Mode)

"Se implementó un sistema de cambio de tema (Claro/Oscuro) para mejorar la accesibilidad y reducir
 la fatiga visual del usuario durante sesiones de uso prolongadas.

Aspectos Técnicos:

Variables CSS (Custom Properties): 
Se definieron variables globales en :root (ej: --bg-body, --card-bg) que cambian dinámicamente 
al alternar la clase .dark-theme en el DOM. Esto permite una transición suave y 
mantenible sin duplicar hojas de estilo.

Persistencia: Se utiliza localStorage de HTML5 para recordar la preferencia del usuario. 
Al recargar la página, un script de ejecución inmediata en el footer aplica el tema guardado antes de 
que el contenido sea visible, evitando el 'flickeo' visual."

Título: Diseño "Clean" y Navegación Contextual

"La interfaz se alejó de los templates genéricos de Bootstrap para adoptar 
un estilo 'SaaS' moderno (Clean Design). Se priorizó el espacio en blanco, 
sombras suaves (box-shadow) y bordes redondeados para jerarquizar la información.

Además, se implementó una Navegación Contextual Inteligente en el sidebar: el menú se adapta dinámicamente
 ocultando opciones redundantes (ej: el botón 'Nuevo Cliente' desaparece si el usuario 
 ya se encuentra en el formulario de alta), mejorando el foco cognitivo."

2. Optimización y Renderizado de Datos
Título: Renderizado Eficiente de Tablas y Paginación

"Para garantizar la escalabilidad del sistema ante un gran volumen de datos, 
se evitó cargar la totalidad de los registros en la vista. Se implementó una estrategia de Paginación
 del Lado del Servidor (Server-Side Pagination).

Detalle Técnico:

Se utilizan las cláusulas LIMIT y OFFSET en las consultas SQL mediante PDO.

Se realiza un cálculo previo (COUNT) para determinar la cantidad total de páginas.

Esto asegura que el consumo de memoria de PHP y el tiempo de transferencia HTTP se mantengan constantes y bajos,
 independientemente de si existen 10 o 10.000 clientes en la base de datos."

Título: Optimización de Consultas SQL (N+1 Problem)

"Para mostrar las especialidades de cada cliente en el listado, se evitó realizar una sub-consulta
 por cada fila (el conocido problema N+1). En su lugar, se utilizó la función de agregación GROUP_CONCAT
  junto con LEFT JOIN en la consulta principal. Esto permite recuperar toda la información
   necesaria (Cliente + Foto + Especialidades) en una única petición eficiente a la base de datos."

3. Integridad y Seguridad de Datos
Título: Borrado Lógico (Soft Delete)

"Siguiendo estándares de la industria para la preservación de datos históricos, 
se implementó un mecanismo de Borrado Lógico. En lugar de ejecutar sentencias DELETE destructivas, 
el sistema actualiza un flag de estado (activo = 0). El listado principal filtra automáticamente 
estos registros (WHERE activo = 1). Esto permite la recuperación futura de datos y mantiene 
la integridad referencial con otras tablas del sistema."

Título: Transacciones ACID

"Para las operaciones de Alta y Modificación que involucran múltiples tablas 
(Tabla clientes y tabla pivot cliente_especialidad), se implementaron Transacciones de Base de Datos 
($pdo->beginTransaction(), commit(), rollback()). Esto garantiza la Atomicidad: si falla 
la inserción de las especialidades o la subida de la imagen, no se crea el cliente a medias, 
evitando datos corruptos o huérfanos en el sistema."

Título: Protección Avanzada de Login (Rate Limiting)

"Para mitigar ataques de fuerza bruta contra el formulario de acceso, se desarrolló un 
sistema de Rate Limiting personalizado. El sistema rastrea los intentos fallidos por dirección IP utilizando
 un almacenamiento temporal (JSON). Si una IP supera los 5 intentos fallidos en una ventana de 10 minutos, 
 el acceso se bloquea temporalmente, protegiendo las credenciales de los usuarios legítimos."

 ## 🚀 Características Técnicas Destacadas

### 🛡️ Seguridad
* **Rate Limiting:** Protección contra fuerza bruta en el login (bloqueo por IP).
* **Session Hardening:** Cookies `HttpOnly` y `SameSite=Strict` para mitigar XSS y CSRF.
* **CSRF Protection:** Validación de tokens en todos los formularios transaccionales.
* **Secure Password:** Hashing con Bcrypt (`password_hash`).

### ⚡ Performance & Database
* **Soft Delete:** Implementación de borrado lógico para integridad histórica.
* **Transacciones ACID:** Uso de `PDO::beginTransaction` para asegurar la consistencia de datos en operaciones complejas.
* **Server-Side Pagination:** Renderizado optimizado mediante `LIMIT/OFFSET` en SQL.

### 🎨 UI/UX
* **Dark Mode:** Sistema de temas persistente con `localStorage` y variables CSS.
* **Responsive Sidebar:** Menú lateral fijo y navegación contextual inteligente.
* **Feedback:** Sistema de notificaciones "Flash Messages" para confirmar acciones.