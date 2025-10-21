// ============================================
// CHATBOT DE VOZ - FARMACIA VIRTUAL
// Sistema completo de reconocimiento y síntesis de voz
// ============================================

(function() {
  'use strict';
  
  // Elementos del DOM
  const fab = document.getElementById('catalogMicFab');
  const fabText = document.getElementById('textInputFab');
  const textPanel = document.getElementById('textInputPanel');
  const textInput = document.getElementById('textInput');
  const sendBtn = document.getElementById('sendTextBtn');
  const closeBtn = document.getElementById('closeTextPanel');
  const toast = document.getElementById('voiceToast');
  const qEl = toast?.querySelector('.vt-question');
  const aEl = toast?.querySelector('.vt-answer');
  
  // APIs de voz
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const synth = window.speechSynthesis;
  
  // Estado
  let recognition = null;
  let recognizing = false;
  let voicesLoaded = false;
  
  // ============================================
  // FUNCIONES DE UI
  // ============================================
  
  function showToast() {
    if (toast) {
      toast.classList.remove('d-none');
    }
  }
  
  function hideToast() {
    if (toast) {
      toast.classList.add('d-none');
    }
  }
  
  function setQuestion(text) {
    if (qEl) {
      qEl.textContent = 'Tú: ' + text;
      showToast();
    }
  }
  
  function setAnswer(text) {
    if (aEl) {
      aEl.textContent = 'IA: ' + text;
      showToast();
    }
    speak(text);
  }
  
  function setTyping() {
    if (aEl) {
      aEl.textContent = 'IA: escribiendo…';
      showToast();
    }
  }
  
  // ============================================
  // SÍNTESIS DE VOZ (TEXT-TO-SPEECH)
  // ============================================
  
  function loadVoices() {
    return new Promise((resolve) => {
      const voices = synth.getVoices();
      if (voices.length > 0) {
        voicesLoaded = true;
        console.log('✅ Voces disponibles:', voices.length);
        resolve(voices);
      } else {
        synth.addEventListener('voiceschanged', () => {
          const v = synth.getVoices();
          voicesLoaded = true;
          console.log('✅ Voces cargadas:', v.length);
          resolve(v);
        }, { once: true });
      }
    });
  }
  
  function speak(text) {
    if (!synth) {
      console.error('❌ Speech Synthesis no disponible');
      return;
    }
    
    // Cancelar cualquier audio previo
    synth.cancel();
    
    // Crear utterance
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'es-ES';
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    // Seleccionar voz en español
    const voices = synth.getVoices();
    const spanishVoice = voices.find(v => v.lang.includes('es-ES')) || 
                        voices.find(v => v.lang.includes('es'));
    
    if (spanishVoice) {
      utterance.voice = spanishVoice;
      console.log('🗣️ Usando voz:', spanishVoice.name);
    }
    
    // Eventos
    utterance.onstart = () => {
      console.log('▶️ Audio iniciado');
    };
    
    utterance.onend = () => {
      console.log('✅ Audio completado');
    };
    
    utterance.onerror = (e) => {
      console.error('❌ Error en audio:', e);
    };
    
    // Reproducir
    console.log('🔊 Reproduciendo:', text);
    synth.speak(utterance);
  }
  
  // ============================================
  // RECONOCIMIENTO DE VOZ (SPEECH-TO-TEXT)
  // ============================================
  
  function startListening() {
    if (!recognition) {
      console.error('❌ Recognition no inicializado');
      alert('El reconocimiento de voz no está disponible. Recarga la página.');
      return;
    }
    
    if (recognizing) {
      console.log('⚠️ Ya está escuchando');
      return;
    }
    
    // Verificar conexión a internet
    if (!navigator.onLine) {
      console.error('❌ Sin conexión a internet');
      setAnswer('⚠️ No hay conexión a internet. El reconocimiento de voz requiere estar conectado.');
      alert('⚠️ Sin conexión a internet\n\nEl reconocimiento de voz de Chrome requiere conexión a internet para funcionar.\n\nPor favor verifica tu conexión e intenta de nuevo.');
      return;
    }
    
    console.log('🎤 Intentando iniciar reconocimiento...');
    console.log('🌐 Conexión a internet: OK');
    
    try {
      recognition.start();
    } catch (error) {
      console.error('❌ Error al iniciar:', error);
      
      // Si ya está corriendo, detenerlo primero
      if (error.message && error.message.includes('already started')) {
        console.log('⚠️ Ya estaba iniciado, reiniciando...');
        recognition.stop();
        setTimeout(() => {
          try {
            recognition.start();
          } catch (e) {
            console.error('❌ Error al reiniciar:', e);
            alert('Error con el micrófono. Por favor recarga la página.');
          }
        }, 300);
      } else {
        alert('No se pudo acceder al micrófono. Verifica los permisos del navegador.');
      }
    }
  }
  
  function stopListening() {
    if (recognition && recognizing) {
      recognition.stop();
      console.log('🛑 Deteniendo reconocimiento...');
    }
  }
  
  // ============================================
  // LÓGICA DE RESPUESTAS
  // ============================================
  
  function isGreeting(text) {
    const greetings = /\b(hola|buenas|buenos dias|buenos días|buenas tardes|buenas noches|hey|que tal|qué tal|saludos)\b/i;
    return greetings.test(text);
  }
  
  function getLocalGreeting() {
    const greetings = [
      '¡Hola! 😊 ¿En qué puedo ayudarte hoy?',
      '¡Qué gusto escucharte! 🙌 Dime, ¿qué necesitas?',
      '¡Hola, bienvenido a la farmacia! 🏪 Estoy listo para ayudarte.',
      '¡Hola! Soy tu asistente virtual. ¿Cómo puedo ayudarte?'
    ];
    return greetings[Math.floor(Math.random() * greetings.length)];
  }
  
  async function processQuestion(question) {
    console.log('💬 Procesando pregunta:', question);
    
    // Respuesta local para saludos
    if (isGreeting(question)) {
      const greeting = getLocalGreeting();
      setAnswer(greeting);
      return;
    }
    
    // Mostrar indicador de carga
    setTyping();
    
    try {
      const response = await fetch('api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ question })
      });
      
      if (!response.ok) {
        throw new Error('Error en la respuesta del servidor');
      }
      
      const data = await response.json();
      
      if (data && data.text) {
        setAnswer(data.text);
      } else {
        setAnswer('Mmm... 🤔 no estoy seguro. Puedo ayudarte con disponibilidad y precios. Por ejemplo: "¿Tienen paracetamol?"');
      }
    } catch (error) {
      console.error('❌ Error al consultar API:', error);
      setAnswer('Lo siento, hubo un error al procesar tu pregunta. Intenta de nuevo.');
    }
  }
  
  // ============================================
  // INICIALIZACIÓN
  // ============================================
  
  function initRecognition() {
    if (!SpeechRecognition) {
      console.error('❌ Speech Recognition no soportado');
      return false;
    }
    
    recognition = new SpeechRecognition();
    recognition.lang = 'es-ES';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;
    recognition.continuous = false;
    
    // Evento: inicio
    recognition.onstart = () => {
      recognizing = true;
      fab.textContent = '⏹️';
      fab.style.background = '#ef4444';
      fab.style.animation = 'pulse 1.5s infinite';
      fab.title = 'Detener grabación - Habla ahora';
      console.log('✅ 🎤 Escuchando... ¡Habla ahora!');
      
      // Mostrar mensaje visual
      if (aEl) {
        aEl.textContent = '🎤 Escuchando... Habla ahora';
        aEl.style.color = '#ef4444';
        showToast();
      }
    };
    
    // Evento: fin
    recognition.onend = () => {
      recognizing = false;
      fab.textContent = '🎤';
      fab.style.background = '#2563eb';
      fab.title = 'Hablar con el asistente';
      console.log('🎤 Reconocimiento detenido');
    };
    
    // Evento: error
    recognition.onerror = (event) => {
      recognizing = false;
      fab.textContent = '🎤';
      fab.style.background = '#2563eb';
      
      console.error('❌ Error de reconocimiento:', event.error);
      console.error('Detalles del error:', event);
      
      let errorMsg = '';
      
      switch(event.error) {
        case 'no-speech':
          errorMsg = 'No escuché nada. Haz clic de nuevo e intenta hablar más cerca del micrófono.';
          break;
        case 'not-allowed':
        case 'permission-denied':
          errorMsg = 'Por favor, permite el acceso al micrófono en tu navegador.';
          alert('⚠️ Necesitas dar permisos de micrófono.\n\n1. Haz clic en el icono de candado en la barra de direcciones\n2. Permite el acceso al micrófono\n3. Recarga la página');
          break;
        case 'audio-capture':
          errorMsg = 'No se detectó ningún micrófono. Verifica que esté conectado.';
          break;
        case 'network':
          errorMsg = 'Error de conexión. El reconocimiento de voz requiere internet. Verifica tu conexión y vuelve a intentar.';
          console.warn('⚠️ El reconocimiento de voz de Chrome requiere conexión a internet');
          break;
        case 'aborted':
          errorMsg = 'Reconocimiento cancelado.';
          break;
        default:
          errorMsg = `Error: ${event.error}. Intenta de nuevo.`;
      }
      
      setAnswer(errorMsg);
    };
    
    // Evento: resultado
    recognition.onresult = (event) => {
      const transcript = event.results[0][0].transcript;
      const confidence = event.results[0][0].confidence;
      
      console.log('📝 Texto reconocido:', transcript);
      console.log('🎯 Confianza:', (confidence * 100).toFixed(1) + '%');
      
      setQuestion(transcript);
      processQuestion(transcript);
    };
    
    return true;
  }
  
  function initButton() {
    if (!fab) {
      console.error('❌ Botón FAB no encontrado');
      return;
    }
    
    fab.addEventListener('click', () => {
      if (recognizing) {
        stopListening();
      } else {
        startListening();
      }
    });
    
    console.log('✅ Botón inicializado');
  }
  
  async function init() {
    console.log('🚀 Inicializando chatbot de voz...');
    
    // Verificar soporte
    if (!SpeechRecognition) {
      console.error('❌ Speech Recognition no soportado');
      if (fab) {
        fab.title = 'Voz no soportada en este navegador';
        fab.style.opacity = '0.5';
        fab.style.cursor = 'not-allowed';
      }
      return;
    }
    
    if (!synth) {
      console.error('❌ Speech Synthesis no soportado');
      return;
    }
    
    // Cargar voces
    await loadVoices();
    
    // Inicializar reconocimiento
    if (!initRecognition()) {
      return;
    }
    
    // Inicializar botón
    initButton();
    
    console.log('✅ Chatbot de voz listo');
    console.log('💡 Haz clic en el botón 🎤 para empezar');
  }
  
  // ============================================
  // INPUT DE TEXTO (ALTERNATIVA SIN VOZ)
  // ============================================
  
  function initTextInput() {
    if (!fabText || !textPanel || !textInput || !sendBtn || !closeBtn) {
      console.warn('⚠️ Elementos de input de texto no encontrados');
      return;
    }
    
    // Abrir panel
    fabText.addEventListener('click', () => {
      textPanel.classList.remove('d-none');
      textInput.focus();
      console.log('💬 Panel de texto abierto');
    });
    
    // Cerrar panel
    closeBtn.addEventListener('click', () => {
      textPanel.classList.add('d-none');
      textInput.value = '';
    });
    
    // Enviar con botón
    sendBtn.addEventListener('click', () => {
      const question = textInput.value.trim();
      if (question) {
        setQuestion(question);
        processQuestion(question);
        textInput.value = '';
        textPanel.classList.add('d-none');
      }
    });
    
    // Enviar con Enter
    textInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        const question = textInput.value.trim();
        if (question) {
          setQuestion(question);
          processQuestion(question);
          textInput.value = '';
          textPanel.classList.add('d-none');
        }
      }
    });
    
    console.log('✅ Input de texto inicializado');
  }
  
  // ============================================
  // EJECUTAR AL CARGAR
  // ============================================
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      init();
      initTextInput();
    });
  } else {
    init();
    initTextInput();
  }
  
})();
