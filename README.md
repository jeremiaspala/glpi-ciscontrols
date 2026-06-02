# CIS Controls Plugin para GLPI

**Versión:** 2.1.0  
**Autor:** Jeremías Palazzesi — [nerdadas.com](https://www.nerdadas.com)  
**Licencia:** GPL v2+  
**Compatibilidad:** GLPI 11.0.x (requiere >= 11.0.0, no compatible con 12.x)

---

## Descripción

Plugin para GLPI que permite gestionar controles de seguridad basados en el framework **CIS Controls v8.1**. Incluye una biblioteca completa de los 153 safeguards CIS organizados por Grupo de Implementación (IG1, IG2, IG3), vinculación con controles internos, gestión de ejecuciones periódicas, árbol de documentación y cobertura de cumplimiento.

---

## Funcionalidades

- **Biblioteca CIS v8.1** — 153 safeguards con filtros por nivel IG, grupo y búsqueda libre
- **Controles internos** — crear, activar/desactivar y gestionar controles propios vinculados a safeguards CIS
- **Ejecuciones** — registro de ejecuciones periódicas por control con estados (pendiente, completado, vencido)
- **Documentación** — árbol de carpetas y archivos subibles, vinculables tanto a controles como a safeguards CIS directamente
- **Dashboard** — cobertura de cumplimiento por nivel IG
- **Recordatorios** — emails automáticos configurables por control
- **Configuración** — nivel IG objetivo, nombre de empresa, email de compliance

---
<img width="1920" height="901" alt="ciscontrols_1" src="https://github.com/user-attachments/assets/6abdab53-5f18-4f94-a443-d00a6cc841d4" />
<img width="1904" height="924" alt="ciscontrols_2" src="https://github.com/user-attachments/assets/0135dd98-7b03-4b33-a44e-9a1e25f69739" />
<img width="1901" height="676" alt="ciscontrols_3" src="https://github.com/user-attachments/assets/e1495b6f-db58-4411-af02-c7c68330430e" />
<img width="1907" height="680" alt="ciscontrols_4" src="https://github.com/user-attachments/assets/a3ed8eeb-ca55-46c2-9442-fea447b4b925" />
<img width="1914" height="748" alt="ciscontrols_5" src="https://github.com/user-attachments/assets/94015d26-5486-4861-b8ed-532fcca9d441" />
## Instalación

### Requisitos previos

- GLPI 11.0.0 o superior (< 12.0.0)
- PHP 8.1+
- MariaDB / MySQL 10.3+
- Acceso SSH al servidor (para copiar archivos) o acceso al sistema de archivos

### Paso 1 — Copiar el plugin al servidor

```bash
# Opción A: desde la máquina local
scp ciscontrols-2.1.0.tar.gz usuario@servidor-glpi:/tmp/

# Opción B: directamente en el servidor
# subir el .tar.gz por SFTP, panel de control, etc.
```

### Paso 2 — Extraer en la carpeta de plugins

```bash
cd /var/www/html/glpi/plugins/
tar -xzf /tmp/ciscontrols-2.1.0.tar.gz
```

El resultado debe ser la carpeta `/var/www/html/glpi/plugins/ciscontrols/`.

> Ajustar la ruta si GLPI está instalado en otro directorio (p. ej. `/opt/glpi/`, `/usr/share/glpi/`).

### Paso 3 — Ajustar permisos

```bash
chown -R www-data:www-data /var/www/html/glpi/plugins/ciscontrols/
```

> Usar el usuario del servidor web correspondiente (`apache`, `nginx`, `www-data`, etc.).

### Paso 4 — Activar desde la UI de GLPI

1. Iniciar sesión en GLPI como Super-Administrador
2. Ir a **Configuración → Plugins**
3. Buscar **CIS Controls** en la lista
4. Hacer clic en **Instalar** (crea las tablas en la base de datos automáticamente)
5. Hacer clic en **Activar**

El plugin aparecerá en el menú lateral izquierdo como **CIS Controls**.

---

## Primera configuración

Al activar el plugin, ingresar a **CIS Controls → Configuración** y definir:

- **Nombre de la empresa** — aparece en reportes
- **Email de compliance** — destinatario de alertas
- **Nivel IG objetivo** — IG1 (básico), IG2 (intermedio) o IG3 (avanzado)

---

## Actualización desde versión anterior

Si ya tenía instalada una versión anterior (2.0.0):

```bash
# Reemplazar archivos del plugin
cd /var/www/html/glpi/plugins/
rm -rf ciscontrols/
tar -xzf /tmp/ciscontrols-2.1.0.tar.gz
chown -R www-data:www-data ciscontrols/
```

Luego en la UI de GLPI ir a **Configuración → Plugins** y hacer clic en **Actualizar** (si aparece) o desactivar/reactivar el plugin. La actualización ejecuta automáticamente la creación de la nueva tabla `glpi_plugin_ciscontrols_cislib_documents`.

> Si la opción "Actualizar" no aparece, ejecutar manualmente en MySQL:
> ```sql
> CREATE TABLE IF NOT EXISTS glpi_plugin_ciscontrols_cislib_documents (
>   id int NOT NULL AUTO_INCREMENT,
>   plugin_ciscontrols_cislib_id int NOT NULL,
>   plugin_ciscontrols_documents_id int NOT NULL,
>   link_type varchar(50) DEFAULT 'referencia',
>   PRIMARY KEY (id),
>   UNIQUE KEY unique_link (plugin_ciscontrols_cislib_id, plugin_ciscontrols_documents_id)
> ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
> ```

---

## Desinstalación

1. En GLPI: **Configuración → Plugins → CIS Controls → Desactivar → Desinstalar**
2. La desinstalación elimina todas las tablas del plugin de la base de datos
3. Eliminar la carpeta del servidor:
   ```bash
   rm -rf /var/www/html/glpi/plugins/ciscontrols/
   ```

> **Atención:** la desinstalación borra todos los datos (controles, ejecuciones, documentos, configuración). Hacer backup antes si es necesario.

---

## Estructura de archivos

```
ciscontrols/
├── setup.php                    # Registro del plugin en GLPI
├── hook.php                     # Install / Uninstall
├── front/
│   ├── ciscontrol.php           # Lista de controles
│   ├── ciscontrol.form.php      # Alta/edición/baja de controles
│   ├── cisexecution.php         # Lista de ejecuciones
│   ├── cisexecution.form.php    # Alta/edición de ejecuciones
│   ├── cislib.php               # Biblioteca CIS v8.1
│   ├── cislib.form.php          # Vinculación cislib ↔ controles/documentos
│   ├── config.php               # Configuración del plugin
│   ├── dashboard.php            # Dashboard de cobertura
│   ├── doctree.php              # Árbol de documentación
│   ├── doctree.form.php         # Gestión de carpetas y archivos
│   └── evidence.php             # Evidencias de ejecución
├── inc/
│   ├── control.class.php        # Clase PluginCiscontrolsControl
│   └── execution.class.php      # Clase PluginCiscontrolsExecution
└── locale/                      # Traducciones
```

---

## Tablas en base de datos

| Tabla | Descripción |
|---|---|
| `glpi_plugin_ciscontrols_controls` | Controles internos |
| `glpi_plugin_ciscontrols_executions` | Ejecuciones por control |
| `glpi_plugin_ciscontrols_cislib` | Biblioteca CIS v8.1 (153 safeguards) |
| `glpi_plugin_ciscontrols_config` | Configuración del plugin |
| `glpi_plugin_ciscontrols_documents` | Árbol de documentación |
| `glpi_plugin_ciscontrols_control_cislib` | Vínculos control ↔ safeguard |
| `glpi_plugin_ciscontrols_control_documents` | Vínculos control ↔ documento |
| `glpi_plugin_ciscontrols_cislib_documents` | Vínculos safeguard ↔ documento |

---

## Notas de seguridad

- Todas las acciones de escritura requieren método POST con token CSRF validado por el kernel de GLPI 11
- Los archivos subibles se almacenan en `GLPI_DOC_DIR/_plugins/ciscontrols/docs/` (fuera del webroot)
- Extensiones permitidas para subir: jpg, jpeg, png, pdf, txt, zip, rar, 7z, xls, xlsx, doc, docx, csv
- El acceso al plugin requiere el derecho `plugin_ciscontrols` en el perfil de GLPI

---

## Soporte

**Autor:** Jeremías Palazzesi  
**Web:** [https://www.nerdadas.com](https://www.nerdadas.com)

---

## Datos incluidos — Biblioteca CIS v8.1

La carpeta `data/` contiene los datos de la biblioteca CIS Controls v8.1 para cargar al instalar el plugin:

- `schema.sql` — estructura de todas las tablas del plugin
- `cislib_seed.sql` — 119 safeguards CIS v8.1 con documentos de referencia asociados

```bash
# Importar la biblioteca después de instalar el plugin desde GLPI
mysql -u glpiuser -p glpi < data/cislib_seed.sql
```

> Los datos incluidos son únicamente los safeguards públicos de CIS Controls v8.1. No incluye controles internos, ejecuciones, documentación ni configuración de ninguna organización.
