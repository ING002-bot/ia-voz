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
        
        // Ejecutar acciones si hay comando
        if (data.action) {
          executeAction(data.action);
        }
      } else {
        setAnswer('Mmm... 🤔 no estoy seguro. Puedo ayudarte con disponibilidad y precios. Por ejemplo: "¿Tienen paracetamol?"');
      }
    } catch (error) {
      console.error('❌ Error al consultar API:', error);
      setAnswer('Lo siento, hubo un error al procesar tu pregunta. Intenta de nuevo.');
    }
  }
  
  // ============================================
  // EJECUCIÓN DE ACCIONES POR VOZ
  // ============================================
  
  function executeAction(action) {
    console.log('🎬 Ejecutando acción:', action);
    
    switch(action) {
      case 'open_cart':
        if (window.cartSystem && typeof window.cartSystem.openSidebar === 'function') {
          setTimeout(() => {
            window.cartSystem.openSidebar();
          }, 500);
        }
        break;
        
      case 'checkout':
        if (window.cartSystem && typeof window.cartSystem.openSidebar === 'function') {
          setTimeout(() => {
            window.cartSystem.openSidebar();
            // Hacer scroll al botón de checkout
            setTimeout(() => {
              const checkoutBtn = document.getElementById('checkoutBtn');
              if (checkoutBtn) {
                checkoutBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                checkoutBtn.classList.add('pulse-animation');
                setTimeout(() => {
                  checkoutBtn.classList.remove('pulse-animation');
                }, 2000);
              }
            }, 300);
          }, 500);
        }
        break;
        
      case 'show_historial':
        showHistorialModal();
        break;
        
      case 'download_pdf':
        downloadUltimaBoleta();
        break;
        
      case 'clear_cart':
        if (window.cartSystem && typeof window.cartSystem.clearCart === 'function') {
          window.cartSystem.clearCart();
        } else {
          // Limpiar manualmente si la función no existe
          localStorage.removeItem('omarcitoia_cart');
          location.reload();
        }
        break;
        
      default:
        console.warn('Acción no reconocida:', action);
    }
  }
  
  // ============================================
  // HISTORIAL DE COMPRAS
  // ============================================
  
  async function showHistorialModal() {
    try {
      const response = await fetch('historial_api.php?action=get_compras');
      const data = await response.json();
      
      if (data.success && data.compras) {
        const compras = data.compras;
        
        let html = `
          <div class="modal-overlay" id="historialModal" onclick="closeHistorialModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
              <div class="modal-header">
                <h3>📋 Mi Historial de Compras</h3>
                <button onclick="closeHistorialModal()" class="btn-close-modal">✕</button>
              </div>
              <div class="modal-body">
        `;
        
        if (compras.length === 0) {
          html += '<p class="empty-message">No tienes compras registradas aún.</p>';
        } else {
          html += '<div class="compras-list">';
          compras.forEach(compra => {
            const fecha = new Date(compra.fecha).toLocaleDateString('es-PE', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            });
            html += `
              <div class="compra-item">
                <div class="compra-info">
                  <h4>${compra.medicamento}</h4>
                  <p class="compra-fecha">${fecha}</p>
                </div>
                <div class="compra-details">
                  <span class="cantidad">Cantidad: ${compra.cantidad}</span>
                  <span class="precio">S/ ${parseFloat(compra.precio_unitario).toFixed(2)} c/u</span>
                  <span class="subtotal">Total: S/ ${parseFloat(compra.subtotal).toFixed(2)}</span>
                </div>
              </div>
            `;
          });
          html += '</div>';
        }
        
        html += `
              </div>
              <div class="modal-footer">
                <button onclick="showBoletasModal()" class="btn-secondary">Ver Boletas</button>
                <button onclick="closeHistorialModal()" class="btn-primary">Cerrar</button>
              </div>
            </div>
          </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
        
      } else {
        if (data.error === 'No autorizado. Debes iniciar sesión.') {
          alert('Debes iniciar sesión para ver tu historial de compras.');
          window.location.href = 'login_unified.php';
        } else {
          alert('No se pudo cargar el historial de compras.');
        }
      }
    } catch (error) {
      console.error('Error al cargar historial:', error);
      alert('Error al cargar el historial de compras.');
    }
  }
  
  // ============================================
  // BOLETAS
  // ============================================
  
  async function showBoletasModal() {
    closeHistorialModal();
    
    try {
      const response = await fetch('historial_api.php?action=get_boletas');
      const data = await response.json();
      
      if (data.success && data.boletas) {
        const boletas = data.boletas;
        
        let html = `
          <div class="modal-overlay" id="boletasModal" onclick="closeBoletasModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
              <div class="modal-header">
                <h3>📄 Mis Boletas</h3>
                <button onclick="closeBoletasModal()" class="btn-close-modal">✕</button>
              </div>
              <div class="modal-body">
        `;
        
        if (boletas.length === 0) {
          html += '<p class="empty-message">No tienes boletas registradas aún.</p>';
        } else {
          html += '<div class="boletas-list">';
          boletas.forEach(boleta => {
            const fecha = new Date(boleta.fecha).toLocaleDateString('es-PE', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            });
            html += `
              <div class="boleta-item">
                <div class="boleta-info">
                  <h4>Boleta N° ${boleta.numero_boleta}</h4>
                  <p class="boleta-fecha">${fecha}</p>
                </div>
                <div class="boleta-details">
                  <span class="total">Total: S/ ${parseFloat(boleta.total).toFixed(2)}</span>
                  <button onclick="downloadBoletaPDF(${boleta.id})" class="btn-download">
                    📥 Descargar PDF
                  </button>
                </div>
              </div>
            `;
          });
          html += '</div>';
        }
        
        html += `
              </div>
              <div class="modal-footer">
                <button onclick="closeBoletasModal()" class="btn-primary">Cerrar</button>
              </div>
            </div>
          </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
        
      } else {
        alert('No se pudo cargar las boletas.');
      }
    } catch (error) {
      console.error('Error al cargar boletas:', error);
      alert('Error al cargar las boletas.');
    }
  }
  
  // ============================================
  // DESCARGA DE PDFs
  // ============================================
  
  async function downloadUltimaBoleta() {
    try {
      const response = await fetch('historial_api.php?action=get_ultima_boleta');
      const data = await response.json();
      
      if (data.success && data.boleta) {
        generateBoletaPDF(data.boleta);
      } else {
        if (data.error === 'No autorizado. Debes iniciar sesión.') {
          alert('Debes iniciar sesión para descargar boletas.');
          window.location.href = 'login_unified.php';
        } else {
          alert('No tienes boletas para descargar. Realiza una compra primero.');
        }
      }
    } catch (error) {
      console.error('Error al descargar boleta:', error);
      alert('Error al descargar la boleta.');
    }
  }
  
  async function downloadBoletaPDF(boletaId) {
    try {
      const response = await fetch(`historial_api.php?action=get_boleta&id=${boletaId}`);
      const data = await response.json();
      
      if (data.success && data.boleta) {
        generateBoletaPDF(data.boleta);
      } else {
        alert('No se pudo cargar la boleta.');
      }
    } catch (error) {
      console.error('Error al descargar boleta:', error);
      alert('Error al descargar la boleta.');
    }
  }
  
  function generateBoletaPDF(boleta) {
    if (typeof window.jspdf === 'undefined') {
      alert('Error: jsPDF no está cargado. Por favor recarga la página.');
      return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Obtener info del usuario desde la sesión (si está disponible)
    const userInfo = {
      nombre: 'Cliente',
      username: 'usuario'
    };
    
    // Colores
    const azul = [30, 136, 229];
    const naranja = [251, 140, 0];
    
    // Encabezado
    doc.setFillColor(...azul);
    doc.rect(0, 0, 210, 45, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(28);
    doc.setFont('helvetica', 'bold');
    doc.text('Farmacia Omarcitoia', 105, 20, { align: 'center' });
    
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Tu salud, nuestra prioridad', 105, 30, { align: 'center' });
    doc.text('RUC: 20123456789 - Telf: (01) 234-5678', 105, 37, { align: 'center' });
    
    // Título
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text('BOLETA DE VENTA ELECTRÓNICA', 105, 58, { align: 'center' });
    
    // Información
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text(`N° Boleta: ${boleta.numero_boleta}`, 20, 70);
    doc.text(`Fecha: ${new Date(boleta.fecha).toLocaleDateString('es-PE', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })}`, 20, 77);
    doc.text(`Cliente: ${userInfo.nombre}`, 20, 84);
    doc.text(`Usuario: @${userInfo.username}`, 20, 91);
    
    // Línea separadora
    doc.setDrawColor(...naranja);
    doc.setLineWidth(0.8);
    doc.line(20, 98, 190, 98);
    
    // Tabla de productos
    let y = 108;
    doc.setFillColor(240, 240, 240);
    doc.rect(20, y - 5, 170, 8, 'F');
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.text('Producto', 22, y);
    doc.text('Cant.', 130, y);
    doc.text('P. Unit.', 150, y);
    doc.text('Subtotal', 175, y);
    
    y += 10;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    
    const productos = JSON.parse(boleta.detalles);
    productos.forEach((item, index) => {
      if (index % 2 === 0) {
        doc.setFillColor(250, 250, 250);
        doc.rect(20, y - 4, 170, 7, 'F');
      }
      
      doc.text(item.nombre.substring(0, 40), 22, y);
      doc.text(item.cantidad.toString(), 133, y);
      doc.text(`S/ ${item.precio.toFixed(2)}`, 150, y);
      doc.text(`S/ ${item.subtotal.toFixed(2)}`, 175, y);
      y += 7;
    });
    
    // Total
    y += 10;
    doc.setDrawColor(...azul);
    doc.setLineWidth(0.5);
    doc.line(20, y, 190, y);
    
    y += 10;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('TOTAL:', 150, y);
    doc.setTextColor(...naranja);
    doc.setFontSize(16);
    doc.text(`S/ ${parseFloat(boleta.total).toFixed(2)}`, 175, y);
    
    // Footer
    doc.setTextColor(100, 100, 100);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    y += 15;
    doc.text('Información importante:', 20, y);
    y += 5;
    doc.setFontSize(8);
    doc.text('• Conserve este documento como comprobante de su compra', 22, y);
    y += 4;
    doc.text('• Ganaste ' + Math.floor(parseFloat(boleta.total) / 10) + ' puntos con esta compra', 22, y);
    y += 4;
    doc.text('• Para consultas o reclamos comuníquese al (01) 234-5678', 22, y);
    
    y += 15;
    doc.setFillColor(...azul);
    doc.rect(0, y, 210, 30, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Gracias por su compra en Farmacia Omarcitoia', 105, y + 10, { align: 'center' });
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text('www.farmaciaomarcitoia.com | info@omarcitoia.com', 105, y + 17, { align: 'center' });
    
    // Descargar
    doc.save(`Boleta_${boleta.numero_boleta}.pdf`);
    
    console.log('📄 PDF generado:', boleta.numero_boleta);
  }
  
  // Funciones globales para los modales
  window.closeHistorialModal = function() {
    const modal = document.getElementById('historialModal');
    if (modal) modal.remove();
  };
  
  window.closeBoletasModal = function() {
    const modal = document.getElementById('boletasModal');
    if (modal) modal.remove();
  };
  
  window.showBoletasModal = showBoletasModal;
  window.downloadBoletaPDF = downloadBoletaPDF;
  
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
