// Catalog page voice assistant
(function(){
  const fab = document.getElementById('catalogMicFab');
  const toast = document.getElementById('voiceToast');
  const qEl = toast ? toast.querySelector('.vt-question') : null;
  const aEl = toast ? toast.querySelector('.vt-answer') : null;

  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  const synth = window.speechSynthesis;
  let recognition = null; let recognizing = false;

  function showToast() { if (toast) toast.classList.remove('d-none'); }
  function setQ(t){ if(qEl){ qEl.textContent = 'Tú: ' + t; showToast(); } }
  function setA(t){ if(aEl){ aEl.textContent = 'IA: ' + t; showToast(); } speak(t); }
  function setTyping(){ if(aEl){ aEl.textContent = 'IA: escribiendo…'; showToast(); } }
  function isGreeting(s){ return /(\bhola\b|\bbuenas\b|buenos dias|buenos días|buenas tardes|buenas noches|\bhey\b|que tal|qué tal)/i.test(s); }
  function localGreeting(){
    const opts = [
      '¡Hola! 😊 ¿En qué puedo ayudarte hoy?',
      '¡Qué gusto escucharte! 🙌 Dime, ¿qué necesitas?',
      '¡Hola, bienvenido a la farmacia! 🏪 Estoy listo para ayudarte.'
    ];
    return opts[Math.floor(Math.random()*opts.length)];
  }
  function speak(text){ if(!synth) return; const u = new SpeechSynthesisUtterance(text); u.lang='es-ES'; const v=synth.getVoices().find(v=>/es-/i.test(v.lang)); if(v) u.voice=v; synth.cancel(); synth.speak(u); }

  async function ask(question){
    // Local friendly reply for greetings (instant feedback)
    if (isGreeting(question)) {
      const msg = localGreeting();
      setA(msg);
    } else {
      setTyping();
    }
    try{
      const res = await fetch('api.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ question }) });
      const data = await res.json();
      if (data && data.text) setA(data.text);
      else setA('Mmm... 🤔 no estoy seguro. Puedo ayudarte con disponibilidad y precios, por ejemplo: "¿Tienen paracetamol?"');
    }catch(e){ setA('Error consultando.'); }
  }

  function init(){
    if(!fab) return;
    if(!SpeechRecognition || !synth){ fab.title='Voz no soportada'; return; }
    recognition = new SpeechRecognition();
    recognition.lang='es-ES'; recognition.interimResults=false; recognition.maxAlternatives=1;
    recognition.onstart=()=>{ recognizing=true; fab.textContent='⏹️'; };
    recognition.onend=()=>{ recognizing=false; fab.textContent='🎤'; };
    recognition.onerror=()=>{ recognizing=false; fab.textContent='🎤'; };
    recognition.onresult=(e)=>{ const t=e.results[0][0].transcript; setQ(t); ask(t); };
    fab.addEventListener('click',()=>{ if(!recognition) return; if(recognizing) recognition.stop(); else recognition.start(); });
  }

  init();
})();
