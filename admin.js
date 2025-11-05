// Admin Chat Assistant (Voice & Text)
(function () {
  const micBtn = document.getElementById('adminMicBtn');
  const sendBtn = document.getElementById('adminSendBtn');
  const chatInput = document.getElementById('adminChatInput');
  const chatMessages = document.getElementById('adminChatMessages');
  
  if (!micBtn || !sendBtn || !chatInput || !chatMessages) return;

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const synth = window.speechSynthesis;
  let recognition = null;
  let recognizing = false;

  function speak(text) {
    if (!synth) return;
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'es-ES';
    u.rate = 0.95;
    u.pitch = 1.0;
    const voices = synth.getVoices();
    const esVoice = voices.find(v => /es-/i.test(v.lang));
    if (esVoice) u.voice = esVoice;
    synth.cancel();
    synth.speak(u);
  }

  function addChatBubble(text, isBot) {
    const bubble = document.createElement('div');
    bubble.className = `admin-chat-bubble ${isBot ? 'bot' : 'user'}`;
    
    const avatar = document.createElement('div');
    avatar.className = 'bubble-avatar';
    avatar.textContent = isBot ? '🤖' : '👤';
    
    const textDiv = document.createElement('div');
    textDiv.className = 'bubble-text';
    textDiv.textContent = text;
    
    bubble.appendChild(avatar);
    bubble.appendChild(textDiv);
    chatMessages.appendChild(bubble);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  async function sendAdminQuestion(question) {
    if (!question.trim()) return;
    
    // Agregar mensaje del usuario
    addChatBubble(question, false);
    chatInput.value = '';
    
    try {
      const res = await fetch('admin_consulta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ question })
      });
      const data = await res.json();
      const answer = data && data.text ? data.text : (data.error || 'Sin respuesta');
      
      // Agregar respuesta del bot
      addChatBubble(answer, true);
      speak(answer);
    } catch (e) {
      addChatBubble('Error al conectar con el asistente.', true);
    }
  }

  // Enviar mensaje por texto
  sendBtn.addEventListener('click', () => {
    sendAdminQuestion(chatInput.value);
  });

  // Enter para enviar
  chatInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      sendAdminQuestion(chatInput.value);
    }
  });

  function init() {
    if (!SpeechRecognition || !synth) {
      console.warn('Tu navegador no soporta reconocimiento de voz completo.');
      micBtn.disabled = true;
      return;
    }
    recognition = new SpeechRecognition();
    recognition.lang = 'es-ES';
    recognition.interimResults = false;
    recognition.onstart = () => { 
      recognizing = true; 
      micBtn.style.background = '#ef4444';
      micBtn.textContent = '⏹️';
    };
    recognition.onerror = () => { 
      recognizing = false; 
      micBtn.style.background = '';
      micBtn.textContent = '🎤';
    };
    recognition.onend = () => { 
      recognizing = false; 
      micBtn.style.background = '';
      micBtn.textContent = '🎤';
    };
    recognition.onresult = (e) => {
      const t = e.results[0][0].transcript;
      chatInput.value = t;
      sendAdminQuestion(t);
    };

    micBtn.addEventListener('click', () => {
      if (!recognition) return;
      if (recognizing) recognition.stop(); else recognition.start();
    });

    // Inline editing handlers
    document.querySelectorAll('.inline-edit').forEach((cell) => {
      function save() {
        const id = cell.getAttribute('data-id');
        const field = cell.getAttribute('data-field');
        const value = cell.textContent.trim();
        const fd = new FormData();
        fd.append('action', 'inline_update');
        fd.append('id', id);
        fd.append('field', field);
        fd.append('value', value);
        fetch('admin_api.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(j => {
            if (j && j.ok) {
              cell.style.outline = '2px solid #10b981';
              setTimeout(() => { cell.style.outline = ''; }, 800);
            } else {
              cell.style.outline = '2px solid #ef4444';
              setTimeout(() => { cell.style.outline = ''; }, 1200);
            }
          })
          .catch(() => {
            cell.style.outline = '2px solid #ef4444';
            setTimeout(() => { cell.style.outline = ''; }, 1200);
          });
      }
      cell.addEventListener('blur', save);
      cell.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          cell.blur();
        }
      });
    });
  }

  init();

  // Row edit flow (Editar/Guardar/Cancelar)
  function getRowData(row) {
    const data = {};
    row.querySelectorAll('.inline-cell').forEach((cell) => {
      const field = cell.getAttribute('data-field');
      data[field] = cell.textContent.trim();
    });
    return data;
  }

  function setEditable(row, editable) {
    row.querySelectorAll('.inline-cell').forEach((cell) => {
      cell.contentEditable = editable ? 'true' : 'false';
      cell.classList.toggle('border', editable);
      cell.classList.toggle('border-info', editable);
      cell.classList.toggle('rounded', editable);
      if (editable) cell.dataset.orig = cell.textContent.trim();
    });
  }

  document.querySelectorAll('tr[data-row-id]').forEach((row) => {
    const editBtn = row.querySelector('.js-edit');
    const saveBtn = row.querySelector('.js-save');
    const cancelBtn = row.querySelector('.js-cancel');
    const id = row.getAttribute('data-row-id');

    if (!editBtn || !saveBtn || !cancelBtn) return;

    editBtn.addEventListener('click', () => {
      setEditable(row, true);
      editBtn.classList.add('d-none');
      saveBtn.classList.remove('d-none');
      cancelBtn.classList.remove('d-none');
    });

    cancelBtn.addEventListener('click', () => {
      row.querySelectorAll('.inline-cell').forEach((cell) => {
        if (cell.dataset.orig !== undefined) cell.textContent = cell.dataset.orig;
      });
      setEditable(row, false);
      saveBtn.classList.add('d-none');
      cancelBtn.classList.add('d-none');
      editBtn.classList.remove('d-none');
    });

    saveBtn.addEventListener('click', () => {
      const fields = getRowData(row);
      const fd = new FormData();
      fd.append('action', 'bulk_update');
      fd.append('id', id);
      fd.append('fields', JSON.stringify(fields));
      fetch('admin_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(j => {
          if (j && j.ok) {
            setEditable(row, false);
            saveBtn.classList.add('d-none');
            cancelBtn.classList.add('d-none');
            editBtn.classList.remove('d-none');
            row.style.outline = '2px solid #10b981';
            setTimeout(()=>{ row.style.outline=''; }, 800);
          } else {
            row.style.outline = '2px solid #ef4444';
            setTimeout(()=>{ row.style.outline=''; }, 1200);
          }
        })
        .catch(() => {
          row.style.outline = '2px solid #ef4444';
          setTimeout(()=>{ row.style.outline=''; }, 1200);
        });
    });
  });
})();
