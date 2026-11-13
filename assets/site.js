// Dollar Dreams — shared page script: reveal-once on scroll. Content is never left hidden.
window.DD = window.DD || {};
(() => {
  const io = 'IntersectionObserver' in window ? new IntersectionObserver((entries) => {
    for (const entry of entries) {
      if (!entry.isIntersecting) continue;
      entry.target.classList.add('is-in');
      io.unobserve(entry.target);
    }
  }, { rootMargin: '0px 0px -10% 0px', threshold: 0.08 }) : null;
  // Watch every [data-reveal] inside root (call again for content added later)
  DD.reveal = (root = document) => {
    const targets = root.querySelectorAll('[data-reveal]:not(.is-in)');
    if (!io) { targets.forEach((el) => el.classList.add('is-in')); return; }
    targets.forEach((el) => io.observe(el));
  };
  DD.reveal();
  // safety net: anything on screen a few seconds after load is shown
  window.addEventListener('load', () => setTimeout(() => {
    document.querySelectorAll('[data-reveal]').forEach((el) => { const r = el.getBoundingClientRect(); if (r.top < innerHeight && r.bottom > 0) el.classList.add('is-in'); });
  }, 2500));
})();

// Same pricing as functions.php calculate_booking(): days are counted inclusive of move-in and move-out.
DD.price = function (days, rates) {
  if (days >= 30) return { tier: 'monthly', total: Math.floor(days / 30) * rates.monthly + (days % 30) * (rates.monthly / 30), term: Math.floor(days / 30) + ' mo ' + (days % 30) + ' day(s)' };
  if (days >= 7)  return { tier: 'weekly',  total: Math.floor(days / 7) * rates.weekly + (days % 7) * rates.daily, term: Math.floor(days / 7) + ' wk ' + (days % 7) + ' day(s)' };
  return { tier: 'daily', total: days * rates.daily, term: days + ' day(s)' };
};
DD.peso = (n) => '₱' + Math.round(n).toLocaleString('en-PH');
DD.iso = (d) => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
