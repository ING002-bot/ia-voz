// Panel de cliente - JavaScript
let currentSection = 'inicio';

// Navegación entre secciones
function showSection(sectionName) {
  // Ocultar todas las secciones
  document.querySelectorAll('.content-section').forEach(section => {
    section.classList.remove('active');
  });
  
  // Mostrar la sección seleccionada
  const section = document.getElementById(sectionName + '-section');
  if (section) {
    section.classList.add('active');
  }
  
  // Actualizar navegación activa
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
  });
  
  const navItem = document.querySelector(`[data-section="${sectionName}"]`);
  if (navItem) {
    navItem.classList.add('active');
  }
  
  currentSection = sectionName;
}

// Event listeners para navegación
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.nav-item[data-section]').forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const section = item.getAttribute('data-section');
      showSection(section);
    });
  });
});

// Editar perfil
function editProfile() {
  Swal.fire({
    title: 'Editar Perfil',
    html: `
      <input id="swal-email" class="swal2-input" placeholder="Email">
      <input id="swal-telefono" class="swal2-input" placeholder="Teléfono">
      <input id="swal-direccion" class="swal2-input" placeholder="Dirección">
    `,
    confirmButtonText: 'Guardar',
    confirmButtonColor: '#1E88E5',
    showCancelButton: true,
    cancelButtonText: 'Cancelar',
    preConfirm: () => {
      return {
        email: document.getElementById('swal-email').value,
        telefono: document.getElementById('swal-telefono').value,
        direccion: document.getElementById('swal-direccion').value
      };
    }
  }).then((result) => {
    if (result.isConfirmed) {
      // Aquí se enviaría al servidor
      Swal.fire({
        icon: 'success',
        title: 'Perfil actualizado',
        confirmButtonColor: '#1E88E5'
      });
    }
  });
}

// Chat con Omarcitoia
let chatRecognition = null;

function sendChatMessage() {
  const input = document.getElementById('chatInput');
  const message = input.value.trim();
  
  if (!message) return;
  
  // Agregar mensaje del usuario
  addChatMessage(message, 'user');
  input.value = '';
  
  // Enviar a la API
  fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ question: message })
  })
  .then(res => res.json())
  .then(data => {
    const answer = data.text || 'Lo siento, no pude procesar tu pregunta.';
    addChatMessage(answer, 'bot');
    speakText(answer);
  })
  .catch(err => {
    addChatMessage('Error al conectar con el asistente.', 'bot');
  });
}

function addChatMessage(text, type) {
  const messagesContainer = document.getElementById('chatMessages');
  const messageDiv = document.createElement('div');
  messageDiv.className = `chat-message ${type}`;
  
  const avatar = document.createElement('div');
  avatar.className = 'message-avatar';
  avatar.textContent = type === 'bot' ? '🤖' : '👤';
  
  const content = document.createElement('div');
  content.className = 'message-content';
  
  const p = document.createElement('p');
  p.textContent = text;
  content.appendChild(p);
  
  messageDiv.appendChild(avatar);
  messageDiv.appendChild(content);
  
  messagesContainer.appendChild(messageDiv);
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function speakText(text) {
  if ('speechSynthesis' in window) {
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = 'es-ES';
    utterance.rate = 0.95;
    utterance.pitch = 1.0;
    window.speechSynthesis.speak(utterance);
  }
}

// Reconocimiento de voz para chat
document.getElementById('chatVoiceBtn')?.addEventListener('click', () => {
  if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
    Swal.fire({
      icon: 'error',
      title: 'No disponible',
      text: 'Tu navegador no soporta reconocimiento de voz',
      confirmButtonColor: '#FB8C00'
    });
    return;
  }
  
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  chatRecognition = new SpeechRecognition();
  chatRecognition.lang = 'es-ES';
  chatRecognition.continuous = false;
  chatRecognition.interimResults = false;
  
  chatRecognition.onstart = () => {
    document.getElementById('chatVoiceBtn').style.background = '#ef4444';
  };
  
  chatRecognition.onresult = (event) => {
    const transcript = event.results[0][0].transcript;
    document.getElementById('chatInput').value = transcript;
    sendChatMessage();
  };
  
  chatRecognition.onend = () => {
    document.getElementById('chatVoiceBtn').style.background = '';
  };
  
  chatRecognition.onerror = (event) => {
    console.error('Error de reconocimiento:', event.error);
  };
  
  chatRecognition.start();
});

// Enter para enviar mensaje
document.getElementById('chatInput')?.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    sendChatMessage();
  }
});

// Generar PDF de boleta
function generarBoletaPDF(boletaData) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  
  // Colores
  const azul = [30, 136, 229]; // #1E88E5
  const naranja = [251, 140, 0]; // #FB8C00
  
  // Encabezado
  doc.setFillColor(...azul);
  doc.rect(0, 0, 210, 40, 'F');
  
  doc.setTextColor(255, 255, 255);
  doc.setFontSize(24);
  doc.setFont('helvetica', 'bold');
  doc.text('💊 Farmacia Omarcitoia', 105, 20, { align: 'center' });
  
  doc.setFontSize(12);
  doc.setFont('helvetica', 'normal');
  doc.text('Tu salud, nuestra prioridad', 105, 30, { align: 'center' });
  
  // Información de la boleta
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(16);
  doc.setFont('helvetica', 'bold');
  doc.text('BOLETA DE VENTA', 105, 55, { align: 'center' });
  
  doc.setFontSize(10);
  doc.setFont('helvetica', 'normal');
  doc.text(`N° Boleta: ${boletaData.numero}`, 20, 70);
  doc.text(`Fecha: ${boletaData.fecha}`, 20, 77);
  doc.text(`Cliente: ${boletaData.cliente}`, 20, 84);
  doc.text(`Username: @${boletaData.username}`, 20, 91);
  
  // Línea separadora
  doc.setDrawColor(...naranja);
  doc.setLineWidth(0.5);
  doc.line(20, 95, 190, 95);
  
  // Tabla de productos
  let y = 105;
  doc.setFont('helvetica', 'bold');
  doc.text('Producto', 20, y);
  doc.text('Cant.', 120, y);
  doc.text('P. Unit.', 145, y);
  doc.text('Subtotal', 170, y);
  
  y += 7;
  doc.setFont('helvetica', 'normal');
  
  boletaData.productos.forEach(prod => {
    doc.text(prod.nombre, 20, y);
    doc.text(prod.cantidad.toString(), 120, y);
    doc.text(`S/ ${prod.precio}`, 145, y);
    doc.text(`S/ ${prod.subtotal}`, 170, y);
    y += 7;
  });
  
  // Total
  y += 10;
  doc.setDrawColor(...azul);
  doc.line(20, y - 5, 190, y - 5);
  
  doc.setFont('helvetica', 'bold');
  doc.setFontSize(14);
  doc.text('TOTAL:', 145, y);
  doc.setTextColor(...naranja);
  doc.text(`S/ ${boletaData.total}`, 170, y);
  
  // Mensaje de agradecimiento
  doc.setTextColor(0, 0, 0);
  doc.setFontSize(10);
  doc.setFont('helvetica', 'italic');
  doc.text('💊 Gracias por su compra en Farmacia Omarcitoia.', 105, y + 20, { align: 'center' });
  
  // Guardar PDF
  doc.save(`Boleta_${boletaData.numero}.pdf`);
}
