// Dollar Dreams landing page: motion and interaction (index.php).
// One requestAnimationFrame loop drives everything: smooth scroll, springs, scroll scrubs and canvases.
// If anything here fails, the page is still complete: every hidden state is undone by showAll().

const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const clamp01 = (v) => Math.min(1, Math.max(0, v));
const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

function showAll() {
  $$('[data-rise],[data-view]').forEach((el) => el.classList.add('is-in'));
  $('.veil')?.remove();
}

try {
  window.scrollTo(0, 0);

  /* ================= the ticker: one rAF loop, subscribers with their own frame rate ================= */
  const subs = new Set();
  let rafId = 0;
  function loop(time) {
    for (const s of [...subs]) if (time - s.last > s.every) { s.fn(time, Math.min(64, time - (s.last || time))); s.last = time; }
    rafId = subs.size ? requestAnimationFrame(loop) : 0;
  }
  function subscribe(fn, every = 0) {
    const s = { fn, every, last: 0 };
    subs.add(s);
    if (!rafId) rafId = requestAnimationFrame(loop);
    return () => subs.delete(s);
  }

  /* ================= the spring: damped, mass 1, stepped in 1ms substeps ================= */
  class Spring {
    constructor(value, { tension = 170, friction = 26 } = {}) { this.value = value; this.target = value; this.v = 0; this.k = tension; this.c = friction; }
    to(t) { this.target = t; return this; }
    step(ms) {
      for (let i = 0; i < Math.min(64, ms); i++) {
        const a = this.k * (this.target - this.value) - this.c * this.v;
        this.v += a * 0.001; this.value += this.v * 0.001;
      }
      if (Math.abs(this.v) < 0.001 && Math.abs(this.target - this.value) < 0.001) { this.value = this.target; this.v = 0; return true; }
      return false;
    }
  }
  // animate a set of springs until all rest
  function run(springs, apply) {
    const off = subscribe((t, dt) => { let done = true; for (const s of springs) done = s.step(dt || 16) && done; apply(); if (done) off(); });
  }

  /* ================= smooth scroll (Lenis), registered first so every reader sees this frame's scroll ================= */
  let lenis = null;
  if (!reduced) {
    try {
      const { default: Lenis } = await import('lenis');
      lenis = new Lenis({ smoothWheel: true });
      subscribe((time) => lenis.raf(time));
    } catch (e) { console.warn('Smooth scroll unavailable, using the browser scroll.', e); }
  }
  const go = (target) => (lenis ? lenis.scrollTo(target, { offset: 0 }) : target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' }));
  $$('a[href^="#"]').forEach((a) => a.addEventListener('click', (e) => {
    const t = $(a.getAttribute('href'));
    if (!t) return;
    e.preventDefault(); closeSheet(); go(t);
  }));

  /* ================= text reveals: words or letters, each on its own spring ================= */
  function split(el) {
    if (el.dataset.split) return [...el.children];
    const text = el.textContent;
    const units = el.hasAttribute('data-letters') ? [...text].map((c) => (c === ' ' ? ' ' : c)) : text.split(/\s+/).filter(Boolean);
    el.textContent = '';
    units.forEach((u) => { const s = document.createElement('span'); s.textContent = u; el.appendChild(s); });
    el.dataset.split = '1';
    return [...el.children];
  }
  function reveal(el, delay = 0) {
    if (el.dataset.played) return;
    el.dataset.played = '1';
    const parts = split(el);
    const letters = el.hasAttribute('data-letters');
    const stagger = letters ? 26 : 110, from = letters ? 0.3 : 0.35;
    if (reduced) { parts.forEach((p) => { p.style.opacity = 1; p.style.transform = 'none'; }); return; }
    parts.forEach((p, i) => {
      const o = new Spring(0, letters ? { tension: 200, friction: 24 } : { tension: 90, friction: 26 });
      const y = new Spring(from, letters ? { tension: 200, friction: 24 } : { tension: 90, friction: 26 });
      const apply = () => { p.style.opacity = o.value; p.style.transform = `translateY(${y.value}em)`; };
      apply();
      setTimeout(() => { o.to(1); y.to(0); run([o, y], apply); }, delay + i * stagger);
    });
  }
  // hide the split units until they play (only now that scripting has proven to work)
  $$('[data-words],[data-letters]').forEach((el) => { if (!reduced) split(el).forEach((p) => { p.style.opacity = 0; }); });

  /* ================= in-view: entrances and reveals that play once ================= */
  const io = new IntersectionObserver((entries) => {
    for (const en of entries) {
      if (!en.isIntersecting) continue;
      const el = en.target;
      el.classList.add('is-in');
      $$('[data-words],[data-letters]', el).forEach((t, i) => reveal(t, 120 + i * 90));
      io.unobserve(el);
    }
  }, { rootMargin: '0px 0px -18% 0px' });
  $$('[data-view]').forEach((el) => io.observe(el));
  // section headings outside a [data-view] block
  const headIo = new IntersectionObserver((entries) => {
    for (const en of entries) if (en.isIntersecting) {
      $$('[data-words]', en.target).forEach((t, i) => reveal(t, i * 130));
      const stop = $('.stop', en.target);
      if (stop && !reduced) { stop.style.opacity = 0; setTimeout(() => { stop.style.transition = 'opacity 500ms'; stop.style.opacity = 1; }, 400); }
      headIo.unobserve(en.target);
    }
  }, { rootMargin: '0px 0px -15% 0px' });
  $$('.head:not(.ident__name)').forEach((h) => headIo.observe(h));
  const navIo = new IntersectionObserver((entries) => {
    for (const en of entries) if (en.isIntersecting) { $$('[data-letters]', en.target).forEach((t, i) => reveal(t, 260 + i * 80)); navIo.unobserve(en.target); }
  });
  $$('.foot__nav').forEach((n) => navIo.observe(n));
  // reduced motion: everything is simply there, nothing waits to be scrolled to
  if (reduced) { $$('[data-rise],[data-view]').forEach((el) => el.classList.add('is-in')); $$('[data-words],[data-letters]').forEach((t) => reveal(t)); }

  /* ================= the loading veil: progress creeps, completes on load, lifts in three beats ================= */
  function heroEntrance() {
    $$('.hero [data-rise]').forEach((el) => {
      const d = +el.dataset.rise || 0;
      setTimeout(() => el.classList.add('is-in'), d);
    });
    reveal($('.ident__name [data-words]'), 180);
    $$('.stats [data-letters]').forEach((t, i) => reveal(t, 1000 + i * 90));
  }
  const veil = $('.veil');
  if (!veil || reduced) { veil?.remove(); heroEntrance(); }
  else {
    const fill = $('.veil__fill', veil), meter = $('.veil__meter span', veil), inner = $('.veil__inner', veil);
    const prog = new Spring(0, { tension: 10, friction: 30 }).to(0.7);
    const paint = () => { fill.style.transform = `scaleY(${prog.value})`; meter.style.transform = `scaleX(${prog.value})`; };
    const offProg = subscribe((t, dt) => { prog.step(dt || 16); paint(); });
    let finished = false;
    const finish = () => {
      if (finished) return; finished = true;
      prog.k = 170; prog.c = 26; prog.to(1);
      veil.setAttribute('aria-label', 'Loaded');
      setTimeout(() => {   // beat 1: the veil's own content clears
        veil.style.pointerEvents = 'none';
        inner.style.transition = 'opacity 450ms, transform 450ms'; inner.style.opacity = 0; inner.style.transform = 'translateY(-0.75rem)';
        $('.veil__meter', veil).style.transition = 'opacity 450ms'; $('.veil__meter', veil).style.opacity = 0;
        setTimeout(() => {   // beat 2: the veil lifts and the page's entrance begins
          veil.style.transition = 'opacity 900ms cubic-bezier(0.2,0,0,1)'; veil.style.opacity = 0;
          heroEntrance();
          const t0 = performance.now();
          const off = subscribe(() => {   // beat 3: remove it once it has really faded
            if (parseFloat(getComputedStyle(veil).opacity) <= 0.004 || performance.now() - t0 > 3000) { veil.remove(); offProg(); off(); }
          });
        }, 430 + 240);
      }, 380);
    };
    if (document.readyState === 'complete') setTimeout(finish, 500); else addEventListener('load', finish, { once: true });
    setTimeout(finish, 6000);   // a slow photo never keeps the page shut
  }

  /* ================= scroll scrub helper: progress of an element between two positions ================= */
  const vh = () => innerHeight || 1;
  function progress(el, start, end) {   // start/end: [edge of element, edge of viewport] e.g. ['top','bottom']
    const b = el.getBoundingClientRect();
    const at = (edge) => (edge === 'top' ? b.top : edge === 'bottom' ? b.bottom : b.top + b.height / 2);
    const vp = (edge) => (edge === 'top' ? 0 : edge === 'bottom' ? vh() : vh() / 2);
    const s = at(start[0]) - vp(start[1]), e = at(end[0]) - vp(end[1]);
    return clamp01(s / (s - e || 1));
  }

  /* ================= the sticky stack: each pinned layer recedes as the next one covers it ================= */
  const phone = matchMedia('(max-width: 639px)');
  const pinned = $$('[data-layer]').map((layer) => ({ layer, inner: $('.layer__inner', layer), shade: $('.layer__shade', layer), next: layer.nextElementSibling }));
  const narrow = matchMedia('(max-width: 1023px)');
  function stack() {
    if (narrow.matches) { pinned.forEach((p) => { p.inner.style.transform = ''; p.inner.style.visibility = 'visible'; }); return; }
    for (const p of pinned) {
      // a layer taller than the screen pins by its foot, so all of it is seen before the next covers it
      p.layer.style.top = Math.min(0, vh() - p.layer.offsetHeight) + 'px';
      if (!p.next) continue;
      const t = clamp01(1 - p.next.getBoundingClientRect().top / vh());
      const shrink = phone.matches || reduced ? 0 : 0.1;
      p.inner.style.transform = t > 0 && shrink ? `scale(${1 - shrink * t})` : '';
      p.shade.style.opacity = 0.55 * t;
      p.inner.style.visibility = t >= 1 ? 'hidden' : 'visible';
    }
  }

  /* ================= chequered seam: the colour above carried into the block, breaking up as it scrolls ================= */
  const noise = (x, y) => { let h = Math.imul(x, 374761393) + Math.imul(y, 668265263); h = Math.imul(h ^ (h >>> 13), 1274126177); return ((h ^ (h >>> 16)) >>> 0) / 4294967295; };
  const seams = $$('canvas.seam').map((c) => ({ c, ctx: c.getContext('2d'), host: c.parentElement.closest('section'), last: -1 }));
  function drawSeam(s) {
    const p = reduced ? 1 : progress(s.host, ['top', 'bottom'], ['top', 'top']);
    if (Math.abs(p - s.last) < 0.002 && s.c.width) return;
    s.last = p;
    const r = Math.min(devicePixelRatio || 1, 2), w = s.c.clientWidth, h = s.c.clientHeight;
    if (s.c.width !== Math.round(w * r)) { s.c.width = Math.round(w * r); s.c.height = Math.round(h * r); }
    const ctx = s.ctx; ctx.setTransform(r, 0, 0, r, 0, 0); ctx.clearRect(0, 0, w, h);
    const CELL = phone.matches ? 16 : 24, cols = Math.ceil(w / CELL), rows = Math.ceil(h / CELL), lift = p * 2;
    const surface = s.c.dataset.carry === 'light' ? css('--bg') : css('--dark'), accent = css('--accent');
    for (let y = 0; y < rows; y++) {
      const depth = y / Math.max(1, rows - 1) + lift;
      if (depth > 1) break;
      const solid = depth <= 0.16, fade = clamp01(1 - (depth - 0.16) / 0.84);
      for (let x = 0; x < cols; x++) {
        if (!solid) { if ((x + y) % 2) continue; if (noise(x, y) > fade) continue; }
        ctx.fillStyle = !solid && noise(x + 101, y + 57) < 0.06 ? accent : surface;
        ctx.fillRect(x * CELL, y * CELL, CELL, CELL);
      }
    }
  }

  /* ================= contour lines: marching squares over a slowly rolling noise field ================= */
  const vnoise = (() => {
    const p = new Uint8Array(512); for (let i = 0; i < 256; i++) p[i] = i;
    for (let i = 255; i > 0; i--) { const j = (noise(i, 7) * (i + 1)) | 0; [p[i], p[j]] = [p[j], p[i]]; }
    for (let i = 0; i < 256; i++) p[i + 256] = p[i];
    const f = (t) => t * t * (3 - 2 * t), lerp = (a, b, t) => a + (b - a) * t;
    const g = (x, y, z) => p[(p[(p[x & 255] + y) & 511] + z) & 511] / 255;
    return (x, y, z) => {
      const X = Math.floor(x), Y = Math.floor(y), Z = Math.floor(z), u = f(x - X), v = f(y - Y), w = f(z - Z);
      const a = lerp(lerp(g(X, Y, Z), g(X + 1, Y, Z), u), lerp(g(X, Y + 1, Z), g(X + 1, Y + 1, Z), u), v);
      const b = lerp(lerp(g(X, Y, Z + 1), g(X + 1, Y, Z + 1), u), lerp(g(X, Y + 1, Z + 1), g(X + 1, Y + 1, Z + 1), u), v);
      return lerp(a, b, w);
    };
  })();
  const contours = $$('canvas.contours').map((c) => ({ c, ctx: c.getContext('2d'), on: false }));
  const cio = new IntersectionObserver((es) => es.forEach((e) => { const k = contours.find((x) => x.c === e.target); if (k) k.on = e.isIntersecting; }));
  contours.forEach((k) => cio.observe(k.c));
  function drawContours(k, t) {
    const r = Math.min(devicePixelRatio || 1, 1.5), w = k.c.clientWidth, h = k.c.clientHeight;
    if (!w || !h) return;
    if (k.c.width !== Math.round(w * r) || k.c.height !== Math.round(h * r)) { k.c.width = Math.round(w * r); k.c.height = Math.round(h * r); }
    const ctx = k.ctx; ctx.setTransform(r, 0, 0, r, 0, 0); ctx.clearRect(0, 0, w, h);
    ctx.strokeStyle = k.c.dataset.contours === 'light' ? 'rgba(255,255,255,0.1)' : css('--contour');
    ctx.lineWidth = 1;
    const S = 22, cols = Math.ceil(w / S) + 1, rows = Math.ceil(h / S) + 1, z = t * 0.00004, field = [];
    for (let y = 0; y <= rows; y++) { field[y] = []; for (let x = 0; x <= cols; x++) field[y][x] = vnoise(x * 0.055, y * 0.055, z) * 12; }
    ctx.beginPath();
    for (let level = 1; level < 12; level++) {
      for (let y = 0; y < rows; y++) for (let x = 0; x < cols; x++) {
        const a = field[y][x], b = field[y][x + 1], c = field[y + 1][x + 1], d = field[y + 1][x];
        const idx = (a > level) | ((b > level) << 1) | ((c > level) << 2) | ((d > level) << 3);
        if (idx === 0 || idx === 15) continue;
        const px = x * S, py = y * S, it = (p, q) => (level - p) / (q - p || 1);
        const T = [px + S * it(a, b), py], R = [px + S, py + S * it(b, c)], B = [px + S * it(d, c), py + S], L = [px, py + S * it(a, d)];
        const seg = (p, q) => { ctx.moveTo(p[0], p[1]); ctx.lineTo(q[0], q[1]); };
        switch (idx) {
          case 1: case 14: seg(L, T); break; case 2: case 13: seg(T, R); break; case 3: case 12: seg(L, R); break;
          case 4: case 11: seg(R, B); break; case 6: case 9: seg(T, B); break; case 7: case 8: seg(L, B); break;
          case 5: seg(L, T); seg(R, B); break; case 10: seg(T, R); seg(L, B); break;
        }
      }
    }
    ctx.stroke();
  }

  /* ================= halftone behind the free-today board: a dot field that thins toward the copy ================= */
  function drawHalftone() {
    $$('canvas.halftone').forEach((c) => {
      const r = Math.min(devicePixelRatio || 1, 2), w = c.clientWidth, h = c.clientHeight;
      c.width = Math.round(w * r); c.height = Math.round(h * r);
      const ctx = c.getContext('2d'); ctx.setTransform(r, 0, 0, r, 0, 0);
      ctx.fillStyle = '#7a7a7a';
      const G = 14;
      for (let y = G; y < h; y += G) for (let x = G; x < w; x += G) {
        const n = vnoise(x * 0.008, y * 0.008, 3.1), reach = clamp01((x / w - 0.35) * 1.6);
        const rad = Math.max(0, (n - 0.35) * 3.2) * reach;
        if (rad > 0.25) { ctx.beginPath(); ctx.arc(x, y, Math.min(2.4, rad), 0, Math.PI * 2); ctx.fill(); }
      }
    });
  }

  /* ================= the timeline: rail thread, turning marker, parallax layers, hover dimming ================= */
  const rows = $('.how__rows'), rail = $('.rail');
  const railRun = $('.rail__run'), railMark = $('.rail__mark'), railTrack = $('.rail__track');
  const parEls = $$('[data-par]');
  const runSpring = new Spring(0, { tension: 140, friction: 30 });
  function timeline(dt) {
    if (!rows || !rail) return;
    const rest = Math.max(0, rows.offsetHeight - 60);
    railTrack.setAttribute('y2', rest);
    runSpring.to(progress(rows, ['top', 'center'], ['bottom', 'bottom']) * rest);
    runSpring.step(dt);
    const run = runSpring.value;
    railRun.setAttribute('height', run);
    railMark.setAttribute('transform', `translate(8 ${run}) rotate(${(run / (rest || 1)) * 1800})`);
    if (phone.matches || reduced) { parEls.forEach((el) => (el.style.translate = '')); return; }
    parEls.forEach((el) => {
      const row = el.closest('.row');
      const p = progress(row, ['top', 'bottom'], ['bottom', 'top']);
      const travel = (+el.dataset.par / 1440) * innerWidth;
      el.style.translate = `0 ${(1 - 2 * p) * travel}px`;
    });
  }
  if (rows && matchMedia('(hover: hover)').matches) {
    $$('.row', rows).forEach((row) => {
      const plate = $('.plate', row);
      plate.addEventListener('mouseenter', () => { rows.classList.add('is-hovering'); row.classList.add('is-hot'); });
      plate.addEventListener('mouseleave', () => { rows.classList.remove('is-hovering'); row.classList.remove('is-hot'); });
    });
  }

  /* ================= hero photo: slow crossfade, and a tilt that follows the pointer ================= */
  const plate = $('[data-tilt]');
  if (plate && !reduced) {
    const imgs = $$('img', plate); let i = 0;
    setInterval(() => { if (document.hidden) return; imgs[i].classList.remove('is-on'); i = (i + 1) % imgs.length; imgs[i].classList.add('is-on'); }, 5500);
    const rx = new Spring(0, { tension: 120, friction: 20 }), ry = new Spring(0, { tension: 120, friction: 20 });
    const base = getComputedStyle(plate).transform === 'none' ? '' : 'translateY(-46%) ';
    const apply = () => { plate.style.transform = `${matchMedia('(max-width: 1023px)').matches ? '' : base}perspective(1200px) rotateX(${rx.value}deg) rotateY(${ry.value}deg)`; };
    addEventListener('pointermove', (e) => {
      if (e.pointerType !== 'mouse') return;
      rx.to(((e.clientY / innerHeight) - 0.5) * -6); ry.to(((e.clientX / innerWidth) - 0.5) * 8); run([rx, ry], apply);
    }, { passive: true });
  }

  /* ================= menu sheet ================= */
  const sheet = $('#menuSheet'), burger = $('.burger');
  function openSheet() {
    sheet.hidden = false; burger.setAttribute('aria-expanded', 'true'); lenis?.stop(); document.documentElement.style.overflow = 'hidden';
    $$('.sheet__nav a', sheet).forEach((a, i) => { a.style.opacity = 0; a.style.transform = 'translateY(0.75rem)'; setTimeout(() => { a.style.transition = 'opacity 500ms, transform 600ms cubic-bezier(0.33,0,0,1)'; a.style.opacity = 1; a.style.transform = 'none'; }, 120 + i * 55); });
    $('.sheet__close', sheet).focus();
  }
  function closeSheet() {
    if (!sheet || sheet.hidden) return;
    sheet.hidden = true; burger.setAttribute('aria-expanded', 'false'); lenis?.start(); document.documentElement.style.overflow = ''; burger.focus();
  }
  burger?.addEventListener('click', openSheet);
  $('.sheet__close')?.addEventListener('click', closeSheet);
  $('#menuSheet .logo')?.addEventListener('click', closeSheet);
  $$('.sheet__nav a').forEach((a) => a.addEventListener('click', closeSheet));
  addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSheet(); });
  matchMedia('(min-width: 1280px)').addEventListener('change', (m) => m.matches && closeSheet());

  /* ================= the frame loop for scroll readers and canvases ================= */
  subscribe((t, dt) => { stack(); seams.forEach(drawSeam); timeline(dt || 16); }, 10);
  subscribe((t) => contours.forEach((k) => { if (k.on || !k.drawn) { drawContours(k, reduced ? 0 : t); k.drawn = true; } }), reduced ? 1e9 : 1000 / 20);
  drawHalftone();
  let rz; addEventListener('resize', () => { clearTimeout(rz); rz = setTimeout(() => { drawHalftone(); seams.forEach((s) => (s.last = -1)); }, 150); });
} catch (err) {
  console.error(err);
  showAll();
}
