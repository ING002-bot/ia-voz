// Admin Voice Assistant (Web Speech API)
(function () {
  const btn = document.getElementById('adminMicBtn');
  const out = document.getElementById('adminVoiceOut');
  if (!btn || !out) return;

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const synth = window.speechSynthesis;
  let recognition = null;
  let recognizing = false;

  function speak(text) {
    if (!synth) return;
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'es-ES';
    const voices = synth.getVoices();
    const esVoice = voices.find(v => /es-/i.test(v.lang));
    if (esVoice) u.voice = esVoice;
    synth.cancel();
    synth.speak(u);
  }

  function setOut(text) {
    out.textContent = text;
  }

  async function sendAdminQuestion(question) {
    try {
      const res = await fetch('admin_consulta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ question })
      });
      const data = await res.json();
      const answer = data && data.text ? data.text : (data.error || 'Sin respuesta');
      setOut(answer);
      speak(answer);
    } catch (e) {
      setOut('Error al consultar.');
    }
  }

  function init() {
    if (!SpeechRecognition || !synth) {
      setOut('Tu navegador no soporta voz.');
      return;
    }
    recognition = new SpeechRecognition();
    recognition.lang = 'es-ES';
    recognition.interimResults = false;
    recognition.onstart = () => { recognizing = true; btn.textContent = '⏹️ Admin'; };
    recognition.onerror = () => { recognizing = false; btn.textContent = '🎤 Admin'; };
    recognition.onend = () => { recognizing = false; btn.textContent = '🎤 Admin'; };
    recognition.onresult = (e) => {
      const t = e.results[0][0].transcript;
      setOut('Tú: ' + t);
      sendAdminQuestion(t);
    };

    btn.addEventListener('click', () => {
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
