# 🏥 Farmacia Virtual - Sistema Completo

## ✅ Sistema Implementado

### Características Principales:
- **27 productos** organizados en **10 categorías**
- **Imágenes optimizadas** de Unsplash (500x400px)
- **Diseño moderno y responsive**
- **Sistema de categorías** con headers decorativos
- **Panel de administración** completo

## 🚀 Instalación

### 1. Base de Datos
Ejecuta el archivo `schema_completo.sql` en phpMyAdmin:
```
http://localhost/phpmyadmin
```
- Elimina la BD `omarcitoia` si existe
- Ejecuta todo el contenido de `schema_completo.sql`

### 2. Verificar Instalación
Abre en tu navegador:
```
http://localhost/ia-voz/index.php
```

## 📊 Estructura de Categorías

| Categoría | Productos |
|-----------|-----------|
| 💊 Analgésicos | 4 |
| 🦠 Antibióticos | 3 |
| 🤧 Antihistamínicos | 3 |
| 🍽️ Gastrointestinales | 3 |
| ❤️ Cardiovasculares | 4 |
| 🩸 Diabetes | 2 |
| 💪 Vitaminas | 3 |
| 🫁 Respiratorios | 2 |
| 🧠 Neurológicos | 1 |
| 💉 Corticoides | 1 |

## 🎨 Características Visuales

### Imágenes
- Tamaño: 500x400px optimizado
- Formato: WebP con fallback
- Carga: Lazy loading automático
- Efecto: Gradiente overlay en hover

### Diseño Responsive
- **Desktop (>1200px)**: 4 columnas
- **Tablet (768-1200px)**: 3 columnas
- **Móvil (480-768px)**: 2 columnas
- **Móvil pequeño (<480px)**: 1 columna

### Efectos
- Hover con elevación de tarjeta
- Transiciones suaves (0.3s)
- Sombras dinámicas
- Bordes con gradiente azul

## 📁 Archivos Principales

```
ia-voz/
├── schema_completo.sql    ← Base de datos completa
├── index.php              ← Catálogo público
├── styles.css             ← Estilos optimizados
├── admin_panel.php        ← Panel de administración
├── login.php              ← Sistema de login
└── db.php                 ← Conexión a BD
```

## 🔧 Solución de Problemas

### Las imágenes no cargan
1. Verifica que XAMPP esté corriendo
2. Verifica tu conexión a internet (imágenes de Unsplash)
3. Abre la consola del navegador (F12) para ver errores

### Error de base de datos
1. Verifica que MySQL esté corriendo en XAMPP
2. Verifica las credenciales en `db.php`
3. Re-ejecuta `schema_completo.sql`

### Estilos no se aplican
1. Limpia la caché del navegador (Ctrl + F5)
2. Verifica que `styles.css` esté en la misma carpeta

## 📞 Soporte

Si tienes problemas:
1. Verifica los logs de PHP en XAMPP
2. Abre la consola del navegador (F12)
3. Verifica que todas las tablas existan en phpMyAdmin

---

**Última actualización:** Octubre 2025
**Versión:** 2.0 - Sistema con Categorías
