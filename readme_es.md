# Filtro de Profesores del Curso para Moodle 4+
**Repositorio:** `moodle-filter_courseprofesores`

Un plugin de filtro para Moodle que reemplaza la etiqueta `{courseprofesores}` por una lista visualmente atractiva de los profesores del curso, agrupados por rol. Cada tarjeta de profesor muestra su avatar, un enlace directo a su perfil y un enlace para enviarle un mensaje.

## Características

- **Agrupado por rol**: Los profesores se organizan según su rol (Profesor con permiso de edición, Profesor sin permiso de edición, Gestor).
- **Visualización de avatar**: Muestra la foto de perfil de cada docente.
- **Enlace al perfil**: Enlace directo a la página de perfil de cada profesor.
- **Enlace de mensajería**: Enlace directo para enviar un mensaje a través del sistema de mensajería de Moodle.
- **Estado en línea**: Indica si el profesor está conectado («En línea ahora», punto verde) o muestra su último acceso al curso, para que los estudiantes sepan cuándo contactarlo. El último acceso se puede mostrar como fecha absoluta o tiempo relativo desde la configuración del plugin.
- **Horario de atención**: Aviso opcional de texto libre (conserva los saltos de línea) que se muestra a los estudiantes sobre la lista de profesores, p. ej. «Lunes y miércoles, 15:00-17:00». Sus colores de fondo, borde y texto siguen el **Color de fondo de tarjeta** configurado, en armonía con las tarjetas de los docentes.
- **Opciones de color de tarjeta**: Color de acento personalizable (botones, bordes y destacados) y color de fondo de las tarjetas, con paletas predefinidas: azul estándar, naranja vibrante, azul profundo/violeta y rosa/rojo intenso.
- **Estilos de visualización**: Elige entre los diseños de Tarjetas, Lista o Compacto desde la configuración del plugin.
- **Enlace de participantes**: Enlace a la lista completa de participantes del curso.
- **Basado en roles**: Solo muestra usuarios con roles docentes (profesor con permiso de edición, profesor sin permiso de edición, gestor), configurable desde la página de ajustes.
- **Visibilidad basada en permisos**: Los profesores se muestran a los usuarios que posean el permiso `filter/courseprofesores:viewprofesores` (otorgado por defecto a estudiantes y roles docentes).
- **Sensible al contexto**: Si no se encuentran profesores a nivel de curso, busca en contextos superiores.
- **Cumplimiento de privacidad**: No almacena datos personales (compatible con RGPD/GDPR).
- **Bilingüe**: Soporte para inglés y español.

## ¿Cómo funciona?

Simplemente escribe `{courseprofesores}` en cualquier parte del contenido de tu curso (página, etiqueta, resumen de sección, etc.) y el filtro lo reemplazará con una lista de todos los profesores del curso.

## Instalación

1. Descarga el plugin o clónalo desde el repositorio:
   ```bash
   git clone https://github.com/dronix69/filter_courseprofesores.git
   ```

2. Coloca la carpeta `filter_courseprofesores` dentro del directorio `filter` de tu Moodle:
   ```
   /ruta/a/moodle/filter/filter_courseprofesores/
   ```

3. Inicia sesión en Moodle como administrador.

4. Ve a **Administración del sitio > Notificaciones** para completar la instalación.

5. Ve a **Administración del sitio > Extensiones > Filtros > Gestionar filtros**.

6. Habilita el filtro "Profesores del Curso".

7. Opcionalmente, configura los ajustes en **Administración del sitio > Extensiones > Filtros > Profesores del Curso**.

## Configuración

El plugin ofrece los siguientes ajustes en **Administración del sitio > Extensiones > Filtros > Profesores del Curso**:

| Ajuste | Descripción |
|---|---|
| **Mostrar avatares** | Muestra las imágenes de avatar de los profesores en la lista |
| **Mostrar departamento** | Muestra la información del departamento del profesor si está disponible |
| **Mostrar institución** | Muestra la información de la institución del profesor si está disponible |
| **Mostrar enlace de mensaje** | Muestra un enlace directo para enviar un mensaje al profesor |
| **Mostrar estado en línea** | Muestra si el profesor está en línea ahora o la fecha/hora de su último acceso al curso |
| **Formato del último acceso** | Formato utilizado para profesores desconectados: fecha absoluta (p. ej. 14/10/2026 15:30) o tiempo relativo (p. ej. hace 2 horas) |
| **Horario de atención** | Aviso opcional de texto libre para los estudiantes sobre la lista de profesores; conserva los saltos de línea en el diseño de tarjetas |
| **Mostrar enlace de participantes** | Muestra un enlace a la página de participantes del curso |
| **Roles a incluir** | Selecciona qué roles del curso deben mostrarse como profesores |
| **Estilo de visualización** | Elige el diseño: Tarjetas, Lista o Compacto |
| **Color de acento** | Color de acento para las tarjetas de profesores y botones: Azul estándar (#0f6cbf), Naranja vibrante (#fc6500), Azul profundo/violeta (#120ef2) o Rosa/rojo intenso (#f20e3f) |
| **Color de fondo de tarjeta** | Color de fondo para las tarjetas de los profesores: Predeterminado del tema, Naranja cálido (#fc6500), Azul profundo (#120ef2) o Rosa/rojo intenso (#f20e3f) |

## Uso

### Uso básico

Coloca la etiqueta en cualquier lugar del contenido de tu curso:

```
{courseprofesores}
```

### Ejemplo: Página de bienvenida

```
¡Bienvenido/a a {coursefullname}!

Los profesores a cargo de este curso son:

{courseprofesores}

Si tienes alguna pregunta, no dudes en contactarlos directamente a través del enlace de mensaje.
```

### Ejemplo: Descripción del curso

```
{courseprofesores}

---
Haz clic en el nombre de cualquier profesor para ver su perfil completo, o usa el botón de mensaje para contactarlo directamente.
```

## Estructura del HTML generado

El filtro genera HTML con la siguiente estructura:

```html
<div class="filter-courseprofesores-container">
    <div class="profesores-officehours">
        <i class="icon fa fa-clock-o fa-fw" aria-hidden="true"></i>
        <span class="profesores-officehours-title">Horario de atención:</span>
        <span class="profesores-officehours-text">Lunes y miércoles, 15:00-17:00</span>
    </div>
    <div class="profesores-role-group">
        <h4 class="profesores-role-title">Profesor</h4>
        <div class="profesores-list">
            <div class="profesor-card">
                <div class="profesor-avatar">
                    <a href="/user/view.php?id=X&course=Y">
                        <img src="..." class="userpicture" />
                    </a>
                </div>
                <div class="profesor-info">
                    <a href="/user/view.php?id=X&course=Y" class="profesor-name">Nombre Completo</a>
                    <div class="profesor-details">Departamento, Institución</div>
                    <div class="profesor-actions">
                        <a href="/message/index.php?id=X" class="profesor-action-link message-link">
                            Enviar mensaje
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="profesores-footer">
        <a href="/user/index.php?id=Y" class="participants-link">
            Ver todos los participantes
        </a>
    </div>
</div>
```

## Clases CSS para personalización

| Clase | Descripción |
|---|---|
| `.filter-courseprofesores-container` | Contenedor principal |
| `.profesores-role-group` | Contenedor para cada grupo de roles |
| `.profesores-officehours` | Contenedor del aviso de horario de atención (utiliza los colores configurados de la tarjeta) |
| `.profesores-role-title` | Título del rol |
| `.profesores-list` | Lista de profesores dentro de un rol |
| `.profesor-card` | Tarjeta individual del profesor |
| `.profesor-avatar` | Contenedor del avatar |
| `.profesor-info` | Contenedor de detalles del profesor |
| `.profesor-name` | Enlace con el nombre del profesor |
| `.profesor-details` | Información de departamento/institución |
| `.profesor-actions` | Contenedor de enlaces de acción |
| `.message-link` | Enlace de mensaje |
| `.profesores-footer` | Pie con enlace a participantes |
| `.participants-link` | Enlace a la página de participantes |

## Seguridad y privacidad

- Todos los datos de usuario son saneados usando las funciones integradas de Moodle
- Solo muestra a los profesores que el usuario actual tiene permiso para ver
- Respeta la configuración de privacidad de mensajería de Moodle (`can_message_user()`)
- No almacena datos personales (cumple con RGPD/GDPR mediante `null_provider`)
- Los usuarios eliminados son excluidos automáticamente
- Utiliza el sistema de permisos (capabilities) de Moodle para la visibilidad del enlace a participantes

## Requisitos

- Moodle 4.5 o superior (versión 2.0.0+, arquitectura con espacios de nombres — compatible con Moodle 5.0+)
- PHP 8.1 o superior

> **Nota para Moodle 5.0+:** Desde la versión 2.0.0, el plugin utiliza la nueva arquitectura de filtros con espacios de nombres (`\filter_courseprofesores\text_filter`) según el estándar MDL-82427, eliminando todas las advertencias de obsolescencia.

## Licencia

Este plugin es software libre: puedes redistribuirlo y/o modificarlo bajo los términos de la Licencia Pública General de GNU según lo publicado por la Free Software Foundation, ya sea la versión 3 de la Licencia, o (a tu elección) cualquier versión posterior.

El texto completo de la licencia está disponible en el archivo [LICENSE](LICENSE) incluido en la raíz del plugin.

---
**Repositorio oficial:** [filter_courseprofesores](https://github.com/dronix69/filter_courseprofesores)
