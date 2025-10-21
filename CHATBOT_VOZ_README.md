# 🎤 Chatbot de Voz - Guía Completa

## ⚠️ IMPORTANTE: Requiere Conexión a Internet

El reconocimiento de voz de Chrome/Edge **requiere conexión a internet** porque usa los servidores de Google para procesar el audio.

### Error Común: `network`

Si ves este error en la consola:
```
❌ Error de reconocimiento: network
```

**Causa:** No hay conexión a internet o la conexión es inestable.

**Solución:**
1. ✅ Verifica que estés conectado a internet
2. ✅ Verifica que tu firewall no esté bloqueando Chrome
3. ✅ Intenta con otra red WiFi
4. ✅ Recarga la página (Ctrl + F5)

## 🚀 Cómo Usar el Chatbot

### 1. Requisitos

- ✅ **Navegador:** Chrome, Edge o Safari (Firefox tiene soporte limitado)
- ✅ **Conexión a internet:** Obligatoria
- ✅ **Micrófono:** Conectado y con permisos
- ✅ **HTTPS o localhost:** Requerido por seguridad

### 2. Pasos para Usar

1. **Abre la página:**
   ```
   http://localhost/ia-voz/index.php
   ```

2. **Haz clic en el botón 🎤** (esquina inferior derecha)

3. **Da permisos al micrófono** si te lo pide

4. **El botón se pondrá ROJO** ⏹️ y verás "Escuchando..."

5. **Habla claramente** (ej: "Hola", "¿Tienen paracetamol?")

6. **Espera la respuesta** en texto y audio

### 3. Prueba Simple

Antes de usar el chatbot completo, prueba que tu micrófono funcione:

```
http://localhost/ia-voz/test-mic.html
```

Esta página te dirá exactamente qué está fallando.

## 🔧 Solución de Problemas

### Problema 1: "Error de red"

**Síntoma:** Error `network` en la consola

**Solución:**
- Verifica tu conexión a internet
- Intenta con otra red
- Verifica que no haya firewall bloqueando

### Problema 2: "Permisos denegados"

**Síntoma:** Error `not-allowed` o `permission-denied`

**Solución:**
1. Haz clic en el candado 🔒 en la barra de direcciones
2. Busca "Micrófono"
3. Cambia a "Permitir"
4. Recarga la página

### Problema 3: "No se detectó micrófono"

**Síntoma:** Error `audio-capture`

**Solución:**
- Verifica que el micrófono esté conectado
- Prueba con otro micrófono
- Verifica en Configuración de Windows → Sonido → Entrada

### Problema 4: "No escuché nada"

**Síntoma:** Error `no-speech`

**Solución:**
- Habla más cerca del micrófono
- Habla más fuerte
- Verifica el volumen del micrófono en Windows
- Asegúrate que el micrófono no esté silenciado

## 📊 Logs en la Consola

Abre la consola (F12) para ver logs detallados:

### Inicialización Exitosa:
```
🚀 Inicializando chatbot de voz...
✅ Voces disponibles: 20
✅ Botón inicializado
✅ Chatbot de voz listo
💡 Haz clic en el botón 🎤 para empezar
```

### Uso Normal:
```
🎤 Intentando iniciar reconocimiento...
🌐 Conexión a internet: OK
✅ 🎤 Escuchando... ¡Habla ahora!
📝 Texto reconocido: "hola"
🎯 Confianza: 95.2%
💬 Procesando pregunta: hola
🗣️ Usando voz: Microsoft Helena - Spanish (Spain)
🔊 Reproduciendo: ¡Hola! 😊 ¿En qué puedo ayudarte hoy?
▶️ Audio iniciado
✅ Audio completado
```

### Error de Red:
```
❌ Sin conexión a internet
⚠️ El reconocimiento de voz de Chrome requiere conexión a internet
```

## 🎯 Comandos de Prueba

Prueba estos comandos para verificar que funcione:

1. **Saludos:**
   - "Hola"
   - "Buenos días"
   - "Buenas tardes"

2. **Consultas de productos:**
   - "¿Tienen paracetamol?"
   - "¿Cuánto cuesta el ibuprofeno?"
   - "¿Hay stock de amoxicilina?"

3. **Preguntas generales:**
   - "¿Qué medicamentos tienen?"
   - "¿Cuál es el horario?"

## 🌐 ¿Por Qué Requiere Internet?

El reconocimiento de voz de Chrome usa la **Web Speech API** que envía el audio a los servidores de Google para procesarlo con inteligencia artificial avanzada.

**Ventajas:**
- ✅ Muy preciso (95%+ de precisión)
- ✅ Entiende español natural
- ✅ No requiere instalación

**Desventajas:**
- ❌ Requiere internet
- ❌ Envía audio a Google
- ❌ No funciona offline

## 🔒 Privacidad

- El audio se envía a Google para procesamiento
- Google puede almacenar el audio temporalmente
- No se almacena información personal en nuestro servidor
- Solo se procesa el texto reconocido

## 📱 Compatibilidad

| Navegador | Reconocimiento | Síntesis | Notas |
|-----------|---------------|----------|-------|
| Chrome | ✅ | ✅ | Totalmente compatible |
| Edge | ✅ | ✅ | Totalmente compatible |
| Safari | ✅ | ✅ | Compatible (iOS 14.5+) |
| Firefox | ⚠️ | ✅ | Soporte limitado |
| Opera | ✅ | ✅ | Compatible |

## 🆘 Soporte

Si sigues teniendo problemas:

1. **Verifica los requisitos:**
   - ✅ Chrome/Edge actualizado
   - ✅ Conexión a internet estable
   - ✅ Micrófono funcionando
   - ✅ Permisos otorgados

2. **Prueba el test simple:**
   ```
   http://localhost/ia-voz/test-mic.html
   ```

3. **Revisa la consola (F12)** para ver errores específicos

4. **Recarga la página** con Ctrl + F5

---

**Última actualización:** Octubre 2025
**Versión:** 2.0 - Sistema de Voz Completo
