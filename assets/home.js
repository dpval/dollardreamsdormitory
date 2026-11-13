// Dollar Dreams landing page (index.php): loader, reveals, springs, carousels, menu and the viewing dialog.
// The page is complete without this file; if anything throws, showAll() undoes every hidden state.

const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const clamp01 = (v) => Math.min(1, Math.max(0, v));
const EXPO = 'cubic-bezier(0.16, 1, 0.3, 1)', QUART = 'cubic-bezier(0.25, 1, 0.5, 1)', INOUT = 'cubic-bezier(0.65, 0, 0.35, 1)';

function showAll() {
  $$('[data-inview]').forEach((el) => { el.style.opacity = 1; el.style.transform = ''; });
  $$('.ln > span, .w > span, .gw > span, .fw').forEach((s) => { s.style.transform = 'none'; s.style.opacity = 1; });
  $$('[data-route]').forEach((r) => r.classList.add('is-in'));
  $('.loader')?.remove();
}

try {
  window.scrollTo(0, 0);

  /* ---------- the root size scales up beyond a 1920px screen (the CSS scales it down below) ---------- */
  const rootScale = () => {
    const size = 16 - (16 * (((1920 - innerWidth) / 1920) * 100 * 0.6666)) / 100;
    if (size > 16) document.documentElement.style.fontSize = size + 'px'; else document.documentElement.style.removeProperty('font-size');
  };
  rootScale(); addEventListener('resize', rootScale);

  /* ---------- one frame loop ---------- */
  const subs = new Set();
  let raf = 0, last = 0;
  const loop = (t) => { const dt = Math.min(64, t - (last || t)) || 16; last = t; for (const f of [...subs]) f(t, dt); raf = subs.size ? requestAnimationFrame(loop) : 0; };
  const sub = (f) => { subs.add(f); if (!raf) { last = 0; raf = requestAnimationFrame(loop); } return () => subs.delete(f); };

  /* ---------- springs: { tension, friction }, mass 1, 1ms substeps ---------- */
  function spring(from, to, { tension = 170, friction = 26 } = {}, apply, delay = 0) {
    const keys = Object.keys(to), x = { ...from }, v = Object.fromEntries(keys.map((k) => [k, 0]));
    apply(x);
    if (reduced) { apply({ ...to }); return; }
    const start = () => {
      const off = sub((t, dt) => {
        let moving = false;
        for (let i = 0; i < dt; i++) for (const k of keys) {
          const a = -tension * (x[k] - to[k]) - friction * v[k];
          v[k] += a * 0.001; x[k] += v[k] * 0.001;
        }
        for (const k of keys) if (Math.abs(v[k]) > 0.002 || Math.abs(x[k] - to[k]) > 0.002) moving = true;
        if (!moving) { Object.assign(x, to); off(); }
        apply(x);
      });
    };
    delay ? setTimeout(start, delay) : start();
  }
  // a spring that can be retargeted (hover)
  function live(init, cfg, apply) {
    const x = { ...init }, v = Object.fromEntries(Object.keys(init).map((k) => [k, 0]));
    let target = { ...init }, off = null;
    apply(x);
    return (to) => {
      target = { ...target, ...to };
      if (reduced) { Object.assign(x, target); apply(x); return; }
      if (off) return;
      off = sub((t, dt) => {
        let moving = false;
        for (let i = 0; i < dt; i++) for (const k in target) {
          const a = -cfg.tension * (x[k] - target[k]) - cfg.friction * v[k];
          v[k] += a * 0.001; x[k] += v[k] * 0.001;
        }
        for (const k in target) if (Math.abs(v[k]) > 0.002 || Math.abs(x[k] - target[k]) > 0.002) moving = true;
        if (!moving) { Object.assign(x, target); off(); off = null; }
        apply(x);
      });
    };
  }

  /* ---------- smooth scroll (Lenis), with a scroll lock for the loader, the menu and the dialog ---------- */
  let lenis = null;
  if (!reduced) {
    try {
      const { default: Lenis } = await import('lenis');
      lenis = new Lenis({ smoothWheel: true });
      sub((t) => lenis.raf(t));
    } catch (e) { console.warn('Smooth scroll unavailable; using the browser scroll.', e); }
  }
  const html = document.documentElement;
  const lock = () => { lenis?.stop(); Object.assign(html.style, { position: 'relative', overflow: 'hidden', height: '100%' }); };
  const unlock = () => { lenis?.start(); ['position', 'overflow', 'height'].forEach((p) => html.style.removeProperty(p)); };
  const scrollToEl = (el) => (lenis ? lenis.scrollTo(el, { offset: -8 }) : el.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' }));

  /* ---------- reveal primitives ---------- */
  function revealParts(parts, { stagger = 120, base = 0, dur = 950, ease = EXPO } = {}) {
    parts.forEach((s, i) => {
      s.style.transition = reduced ? 'none' : `transform ${dur}ms ${ease} ${base + i * stagger}ms, opacity ${Math.round(dur * 0.6)}ms ease ${base + i * stagger}ms`;
      s.style.transform = 'none'; s.style.opacity = 1;
    });
  }
  function resetParts(parts) {
    parts.forEach((s) => { s.style.transition = 'none'; s.style.transform = 'translateY(115%)'; s.style.opacity = 0; });
    void parts[0]?.offsetWidth;   // apply the reset before the next reveal
  }
  const lineParts = (el) => $$('.ln > span', el);

  // fade-and-rise words in running text
  $$('[data-words]').forEach((p) => {
    const text = p.textContent.trim();
    p.setAttribute('aria-label', text);
    p.innerHTML = text.split(/\s+/).map((w) => `<span class="fw" aria-hidden="true">${w.replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]))}</span>`).join(' ');
    if (!reduced) $$('.fw', p).forEach((w) => { w.style.opacity = 0; w.style.transform = 'translateY(18px)'; });
  });

  // in-view entrances
  const FROM = { rise: { o: 0, y: 28, s: 1 }, scale: { o: 0, y: 0, s: 0.9 }, card: { o: 0, y: 60, s: 0.92 } };
  const CFG = { rise: { tension: 200, friction: 26 }, scale: { tension: 220, friction: 22 }, card: { tension: 170, friction: 26 } };
  function playInview(el) {
    if (el.dataset.played) return; el.dataset.played = '1';
    const kind = el.dataset.from || 'rise';
    spring(FROM[kind], { o: 1, y: 0, s: 1 }, CFG[kind], (x) => {
      el.style.opacity = x.o;
      el.style.transform = x.o === 1 && x.y === 0 && x.s === 1 ? '' : `translateY(${x.y}px) scale(${x.s})`;
    }, +el.dataset.inview || 0);
  }
  let ready = false;
  const io = new IntersectionObserver((entries) => {
    for (const en of entries) {
      if (!en.isIntersecting) continue;
      const el = en.target;
      const gated = el.hasAttribute('data-gate-inview') && !ready;
      if (el.matches('[data-inview]') && !gated) playInview(el);
      if (el.matches('.lines:not([data-gate])')) revealParts(lineParts(el), { stagger: 120, dur: 950 });
      if (el.matches('[data-words]')) revealParts($$('.fw', el), { stagger: 28, base: 250, dur: 700, ease: QUART });
      if (el.matches('.ghost')) revealParts($$('.gw > span', el), { stagger: 90, dur: 700 });
      if (el.matches('[data-route]')) el.classList.add('is-in');
      if (!gated) io.unobserve(el);
    }
  }, { rootMargin: '0px 0px -12% 0px' });
  $$('[data-inview], .lines:not([data-gate]):not(.modal__title), [data-words], .ghost, [data-route]').forEach((el) => io.observe(el));

  /* ---------- hover springs (not on phones and small tablets) ---------- */
  if (matchMedia('(min-width: 769px) and (hover: hover)').matches) {
    $$('.pill [data-hover-arrow]').forEach((a) => {
      const go = live({ x: 0 }, { tension: 320, friction: 20 }, (v) => (a.style.transform = `translateX(${v.x}px)`));
      a.closest('.pill').addEventListener('pointerenter', () => go({ x: 5 })); a.closest('.pill').addEventListener('pointerleave', () => go({ x: 0 }));
    });
    $$('.way').forEach((row) => {
      const a = $('[data-hover-arrow]', row);
      const go = live({ x: 0, o: 0.55 }, { tension: 300, friction: 20 }, (v) => { a.style.transform = `translateX(${v.x}px)`; a.style.opacity = v.o; });
      row.addEventListener('pointerenter', () => go({ x: 8, o: 1 })); row.addEventListener('pointerleave', () => go({ x: 0, o: 0.55 }));
    });
    $$('.arrowbtn').forEach((b) => {
      const i = $('.ico', b), flip = i.classList.contains('flip') ? -1 : 1;
      const go = live({ s: 1 }, { tension: 320, friction: 18 }, (v) => (i.style.transform = `scale(${v.s * flip}, ${v.s})`));
      b.addEventListener('pointerenter', () => go({ s: 1.15 })); b.addEventListener('pointerleave', () => go({ s: 1 }));
    });
    $$('[data-hover-scale]').forEach((t) => {
      const img = $('img', t);
      const go = live({ s: 1 }, { tension: 300, friction: 22 }, (v) => (img.style.transform = `scale(${v.s})`));
      t.addEventListener('pointerenter', () => go({ s: 1.03 })); t.addEventListener('pointerleave', () => go({ s: 1 }));
    });
    $$('[data-hover-lift]').forEach((c) => {
      const go = live({ y: 0 }, { tension: 300, friction: 22 }, (v) => (c.style.transform = `translateY(${v.y}px)`));
      c.addEventListener('pointerenter', () => go({ y: -8 })); c.addEventListener('pointerleave', () => go({ y: 0 }));
    });
    $$('.roundbtn').forEach((b) => {
      const x = $('.x', b); if (!x) return;
      const go = live({ r: 0 }, { tension: 300, friction: 18 }, (v) => (x.style.transform = `rotate(${v.r}deg)`));
      b.addEventListener('pointerenter', () => go({ r: 90 })); b.addEventListener('pointerleave', () => go({ r: 0 }));
    });
  }

  /* ---------- scroll parallax: the hero photo and the ghost words ---------- */
  const hero = $('.hero'), plate = $('.hero__plate'), rooms = $('.rooms');
  const progressOf = (el) => { const b = el.getBoundingClientRect(); return clamp01((innerHeight - b.top) / (innerHeight + b.height)); };
  if (!reduced) sub(() => {
    if (plate) plate.style.transform = `translateY(${progressOf(hero) * 12}%)`;
    if (rooms) {
      const p = progressOf(rooms);
      $$('[data-gx]', rooms).forEach((w) => { const [a, b] = w.dataset.gx.split(',').map(Number); w.style.transform = `translateX(${a + (b - a) * p}%)`; });
    }
  });

  /* ---------- hero card slider: autoplay every 3.8s once the page is ready ---------- */
  function slider() {
    const slides = $$('.slide'), dots = $$('[data-dots="slider"] button');
    if (slides.length < 2) return;
    let i = 0, timer = null;
    const show = (n) => {
      if (n === i) return;
      const out = slides[i], inn = slides[n];
      spring({ o: 1, y: 0, s: 1 }, { o: 0, y: 16, s: 0.96 }, { tension: 210, friction: 24 }, (x) => { out.style.opacity = x.o; out.style.transform = `translateY(${x.y}px) scale(${x.s})`; });
      out.classList.remove('is-on'); out.setAttribute('aria-hidden', 'true'); out.tabIndex = -1;
      inn.classList.add('is-on'); inn.removeAttribute('aria-hidden'); inn.removeAttribute('tabindex');
      spring({ o: 0, y: 16, s: 0.96 }, { o: 1, y: 0, s: 1 }, { tension: 210, friction: 24 }, (x) => { inn.style.opacity = x.o; inn.style.transform = `translateY(${x.y}px) scale(${x.s})`; });
      dots.forEach((d, k) => (k === n ? d.setAttribute('aria-current', 'true') : d.removeAttribute('aria-current')));
      i = n;
    };
    const play = () => { clearInterval(timer); if (!reduced) timer = setInterval(() => { if (!document.hidden) show((i + 1) % slides.length); }, 3800); };
    dots.forEach((d, k) => d.addEventListener('click', () => { show(k); play(); }));
    play();
  }

  /* ---------- rooms carousel: ghost words, photo and caption change together ---------- */
  (() => {
    const data = JSON.parse($('#room-slides')?.textContent || '[]');
    if (!data.length) return;
    const words = $$('.ghost .gw > span'), plain = $('[data-ghost-plain]'), img = $('.room-card img');
    const capName = $('[data-cap-name]'), capRole = $('[data-cap-role]'), dots = $$('[data-dots="rooms"] button');
    let i = 0;
    const go = (n) => {
      i = (n + data.length) % data.length;
      const s = data[i];
      resetParts(words);
      s.words.forEach((w, k) => (words[k].textContent = w));
      plain.textContent = s.words.join(' ');
      revealParts(words, { stagger: 90, dur: 700 });
      spring({ o: 0 }, { o: 1 }, { tension: 260, friction: 26 }, (x) => (img.style.opacity = x.o));
      img.src = s.img; img.alt = s.alt; capName.textContent = s.name; capRole.textContent = s.role;
      dots.forEach((d, k) => (k === i ? d.setAttribute('aria-current', 'true') : d.removeAttribute('aria-current')));
    };
    $$('[data-room-step]').forEach((b) => b.addEventListener('click', () => go(i + +b.dataset.roomStep)));
    dots.forEach((d, k) => d.addEventListener('click', () => go(k)));
  })();

  /* ---------- menu overlay ---------- */
  const menu = $('#menu'), burger = $('[data-open-menu]');
  let menuOpen = false;
  function openMenu() {
    if (menuOpen) return; menuOpen = true;
    menu.classList.add('is-open'); menu.setAttribute('aria-hidden', 'false'); burger.setAttribute('aria-expanded', 'true'); lock();
    spring({ o: 0 }, { o: 1 }, { tension: 260, friction: 30 }, (x) => ($('.menu__bg', menu).style.opacity = x.o));
    const panel = $('.menu__panel', menu);
    spring({ o: 0, y: -24 }, { o: 1, y: 0 }, { tension: 220, friction: 28 }, (x) => { panel.style.opacity = x.o; panel.style.transform = `translateY(${x.y}px)`; });
    $$('.menu__nav a', menu).forEach((a, k) => spring({ o: 0, y: 28 }, { o: 1, y: 0 }, { tension: 200, friction: 26 }, (x) => { a.style.opacity = x.o; a.style.transform = `translateY(${x.y}px)`; }, 120 + k * 70));
    setTimeout(() => $('.menu__top .roundbtn', menu).focus(), 60);
  }
  function closeMenu(refocus = true) {
    if (!menuOpen) return; menuOpen = false;
    const panel = $('.menu__panel', menu);
    spring({ o: 1 }, { o: 0 }, { tension: 260, friction: 30 }, (x) => { $('.menu__bg', menu).style.opacity = x.o; panel.style.opacity = x.o; });
    setTimeout(() => { if (!menuOpen) { menu.classList.remove('is-open'); menu.setAttribute('aria-hidden', 'true'); } }, reduced ? 0 : 400);
    burger.setAttribute('aria-expanded', 'false'); unlock(); if (refocus) burger.focus();
  }
  burger?.addEventListener('click', openMenu);
  $$('[data-close-menu]').forEach((b) => b.addEventListener('click', () => closeMenu()));

  /* ---------- viewing dialog: real slots from load.php, real request to save.php ---------- */
  const modal = $('#viewing'), form = $('#visitForm'), done = $('.visit__done'), errBox = $('.visit__err');
  const submitBtn = $('button[type=submit]', form);
  let modalOpen = false, slotsLoaded = false, lastFocus = null;
  const fmtTime = (t) => { const [h, m] = t.split(':'); const n = +h; return `${n % 12 || 12}:${m} ${n >= 12 ? 'PM' : 'AM'}`; };
  function loadSlots() {
    if (slotsLoaded) return; slotsLoaded = true;
    const sel = $('#visitSlot');
    fetch('load.php?type=schedule').then((r) => r.json()).then((rows) => {
      sel.innerHTML = rows.length ? '<option value="">Choose a time</option>' : '<option value="">No viewing slots open right now</option>';
      rows.forEach((s) => {
        const [y, mo, d] = s.date.split('-');
        const date = new Date(y, mo - 1, d).toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
        const o = document.createElement('option');
        o.value = o.textContent = `${s.day}, ${date} / ${fmtTime(s.time_start)} - ${fmtTime(s.time_end)}`;   // same wording as the booking page
        sel.appendChild(o);
      });
    }).catch(() => { slotsLoaded = false; sel.innerHTML = '<option value="">Could not load slots. Please call us.</option>'; });
  }
  function openVisit() {
    if (menuOpen) closeMenu(false);
    if (modalOpen) return; modalOpen = true; lastFocus = document.activeElement;
    modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); lock(); loadSlots();
    spring({ o: 0 }, { o: 1 }, { tension: 240, friction: 30 }, (x) => ($('.modal__bg', modal).style.opacity = x.o));
    const panel = $('.modal__panel', modal);
    spring({ o: 0, y: 28, s: 0.96 }, { o: 1, y: 0, s: 1 }, { tension: 240, friction: 26 }, (x) => { panel.style.opacity = x.o; panel.style.transform = `translateY(${x.y}px) scale(${x.s})`; });
    const t = $('#visit-title'); resetParts(lineParts(t)); revealParts(lineParts(t), { stagger: 90, dur: 800 });
    setTimeout(() => $('#visitSlot').focus(), 120);
  }
  function closeVisit() {
    if (!modalOpen) return; modalOpen = false;
    const panel = $('.modal__panel', modal);
    spring({ o: 1 }, { o: 0 }, { tension: 240, friction: 30 }, (x) => { $('.modal__bg', modal).style.opacity = x.o; panel.style.opacity = x.o; });
    setTimeout(() => { if (!modalOpen) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); } }, reduced ? 0 : 350);
    unlock(); lastFocus?.focus?.();
    setTimeout(() => { if (!modalOpen) { form.reset(); form.hidden = false; done.hidden = true; errBox.hidden = true; } }, 350);
  }
  $$('[data-open-visit]').forEach((b) => b.addEventListener('click', (e) => { e.preventDefault(); openVisit(); }));
  $$('[data-close-visit]').forEach((b) => b.addEventListener('click', closeVisit));
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const problem = !fd.get('schedule') ? 'Please choose a viewing time.'
      : !String(fd.get('name')).trim() ? 'Please enter your full name.'
      : !/^(09\d{9}|\+639\d{9})$/.test(String(fd.get('mobile')).replace(/[\s\-()]/g, '')) ? 'Please enter a valid mobile number, e.g. 09171234567.' : '';
    if (problem) { errBox.textContent = problem; errBox.hidden = false; return; }
    errBox.hidden = true;
    submitBtn.disabled = true; submitBtn.textContent = 'Sending…';
    fetch('save.php', { method: 'POST', body: fd }).then((r) => r.text()).then((t) => {
      if (t.trim() !== 'success') throw new Error(t.trim() || 'Could not send the request.');
      const first = String(fd.get('name')).trim().split(/\s+/)[0] || 'there';
      $('.visit__done-sub').textContent = `Thanks, ${first}. We will call or text you to confirm your viewing.`;
      form.hidden = true; done.hidden = false; $('.visit__done .pill').focus();
    }).catch((err) => { errBox.textContent = err.message.length < 160 ? err.message : 'Could not send the request. Please try again.'; errBox.hidden = false; })
      .finally(() => { submitBtn.disabled = false; submitBtn.textContent = 'Request a viewing'; });
  });
  if (location.hash === '#viewing') setTimeout(openVisit, 1600);

  /* ---------- anchors and keys ---------- */
  $$('a[href^="#"]:not([data-open-visit])').forEach((a) => a.addEventListener('click', (e) => {
    const t = $(a.getAttribute('href')); if (!t) return;
    e.preventDefault(); if (menuOpen) closeMenu(false); scrollToEl(t);
  }));
  addEventListener('keydown', (e) => { if (e.key === 'Escape') { closeVisit(); closeMenu(); } });

  /* ---------- the loader: the logo animation plays with a filling line, then the curtain slides up ---------- */
  // long enough for the coin and the first gold rings of image/dddloaading.mp4; never longer than MAX
  const MIN = reduced ? 200 : 2600, MAX = 4500, EXIT = reduced ? 0 : 850;
  const loader = $('.loader');
  function begin() {
    if (ready) return; ready = true;
    unlock();
    if (reduced) { showAll(); slider(); return; }   // reduced motion: everything is simply there
    revealParts($$('#hero-title .w > span'), { stagger: 140, dur: 1100 });
    const tagline = $('.tagline');   // optional: the hero may have no tagline
    if (tagline) revealParts(lineParts(tagline), { stagger: 110, base: 350, dur: 900 });
    $$('[data-gate-inview]').forEach((el) => { const b = el.getBoundingClientRect(); if (b.top < innerHeight && b.bottom > 0) { playInview(el); io.unobserve(el); } });
    slider();
    if (loader) {
      loader.style.transition = `transform ${EXIT}ms ${INOUT}`; loader.style.transform = 'translateY(-105%)';
      setTimeout(() => loader.remove(), EXIT + 50);
    }
  }
  if (loader) {
    lock();
    const video = $('.loader__video', loader), fill = $('.loader__track span', loader);
    if (video && !reduced) video.play().catch(() => {});   // muted, so browsers allow it; the poster shows if not
    if (video && !reduced) spring({ o: 0, s: 0.96 }, { o: 1, s: 1 }, { tension: 200, friction: 22 }, (x) => { video.style.opacity = x.o; video.style.transform = `scale(${x.s})`; });
    requestAnimationFrame(() => { fill.style.transition = `transform ${MIN - 120}ms ${INOUT} 120ms`; fill.style.transform = 'scaleX(1)'; });
    const countdown = () => setTimeout(begin, MIN);
    if (document.readyState === 'complete') countdown(); else addEventListener('load', countdown, { once: true });
    setTimeout(begin, MAX);
  } else begin();
} catch (err) {
  console.error(err);
  showAll();
}
