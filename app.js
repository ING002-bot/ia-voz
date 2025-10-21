// Web Speech API (STT + TTS) and backend integration
(function () {
  const btnToggle = document.getElementById('btnToggle');
  const statusEl = document.getElementById('status');
  const transcriptEl = document.getElementById('transcript');
  const responseEl = document.getElementById('response');
  const txtInput = document.getElementById('txtInput');
  const btnSend = document.getElementById('btnSend');

  let recognition = null;
  let recognizing = false;

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const synth = window.speechSynthesis;

  function supportsSpeech() {
    return !!SpeechRecognition && !!synth;
  }

  function setStatus(text) {
    statusEl.textContent = text;
  }

  function appendTranscript(text) {
    transcriptEl.textContent = text;
  }

  function setResponse(text) {
    responseEl.textContent = text;
  }

  function speak(text) {
    if (!synth) return;
    const utter = new SpeechSynthesisUtterance(text);
    utter.lang = 'es-ES';
    const voices = synth.getVoices();
    const esVoice = voices.find(v => /es-/i.test(v.lang));
    if (esVoice) utter.voice = esVoice;
    synth.cancel();
    synth.speak(utter);
  }

  async function sendQuestion(question) {
    try {
      setStatus('Consultando...');
      const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ question })
      });
      const data = await res.json();
      const answer = data && data.text ? data.text : 'No pude obtener una respuesta.';
      setResponse(answer);
      speak(answer);
      setStatus('Listo');
    } catch (err) {
      console.error(err);
      setResponse('Ocurrió un error al consultar el servidor.');
      setStatus('Error');
    }
  }

  function initRecognition() {
    if (!SpeechRecognition) return;
    recognition = new SpeechRecognition();
    recognition.lang = 'es-ES';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart = () => {
      recognizing = true;
      setStatus('Escuchando...');
      btnToggle.textContent = '⏹️ Detener';
    };

    recognition.onerror = (e) => {
      console.warn('Reconocimiento error', e.error);
      setStatus('Error de reconocimiento');
      recognizing = false;
      btnToggle.textContent = '🎤 Hablar';
    };

    recognition.onend = () => {
      recognizing = false;
      setStatus('Procesando...');
      btnToggle.textContent = '🎤 Hablar';
    };

    recognition.onresult = (event) => {
      const result = event.results[0][0].transcript;
      appendTranscript(result);
      sendQuestion(result);
    };
  }

  function toggleRecording() {
    if (!recognition) return;
    if (recognizing) {
      recognition.stop();
    } else {
      transcriptEl.textContent = '';
      responseEl.textContent = '';
      recognition.start();
    }
  }

  // UI bindings
  btnToggle.addEventListener('click', () => {
    if (!supportsSpeech()) {
      setResponse('Tu navegador no soporta Web Speech API. Usa la entrada manual.');
      return;
    }
    toggleRecording();
  });

  btnSend.addEventListener('click', () => {
    const q = (txtInput.value || '').trim();
    if (!q) return;
    appendTranscript(q);
    sendQuestion(q);
  });

  txtInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      btnSend.click();
    }
  });

  // Initialize
  if (supportsSpeech()) {
    initRecognition();
    setStatus('Listo');
  } else {
    setStatus('Tu navegador no soporta voz');
  }
})();
