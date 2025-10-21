// Web Speech API (STT + TTS) and backend integration
(function () {
  const btnToggle = document.getElementById('btnToggle');
  const clientMicFab = document.getElementById('clientMicFab');
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

  function setTyping() {
    responseEl.textContent = 'IA: escribiendo…';
  }

  function isGreeting(s) {
    return /(\bhola\b|\bbuenas\b|buenos dias|buenos días|buenas tardes|buenas noches|\bhey\b|que tal|qué tal)/i.test(s);
  }

  function localGreeting() {
    const opts = [
      '¡Hola! 😊 ¿En qué puedo ayudarte hoy?',
      '¡Qué gusto escucharte! 🙌 Dime, ¿qué necesitas?',
      '¡Hola, bienvenido a la farmacia! 🏪 Estoy listo para ayudarte.'
    ];
    return opts[Math.floor(Math.random() * opts.length)];
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
      if (isGreeting(question)) {
        const msg = localGreeting();
        setResponse(msg);
        speak(msg);
      } else {
        setTyping();
      }
      const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ question })
      });
      const data = await res.json();
      const answer = data && data.text ? data.text : 'Mmm... 🤔 no estoy seguro. Puedo ayudarte con disponibilidad y precios. Por ejemplo: "¿Tienen paracetamol?" o "¿Cuánto cuesta el ibuprofeno?"';
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

  if (clientMicFab) {
    clientMicFab.addEventListener('click', () => {
      if (!supportsSpeech()) {
        setResponse('Tu navegador no soporta Web Speech API. Usa la entrada manual.');
        return;
      }
      toggleRecording();
    });
  }

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
