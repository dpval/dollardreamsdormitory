  </main>
</div>
<script>
// Phone: the sidebar opens as a full-screen sheet
function toggleMenu(force) {
  const side = document.getElementById('sidebar');
  const open = force ?? !side.classList.contains('open');
  side.classList.toggle('open', open);
  document.querySelector('.menu-toggle')?.setAttribute('aria-expanded', String(open));
}
document.getElementById('sidebar').addEventListener('click', (e) => { if (e.target.closest('a')) toggleMenu(false); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggleMenu(false); });

// POST helper for the admin actions (delete.php, update.php), with the CSRF token
const CSRF = <?= json_encode(csrf_token()) ?>;
function post(url, data) {
  data.csrf = CSRF;
  return fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(data)
  }).then(res => res.text()).then(t => t.trim());
}
</script>
<?php /* the page adds its own <script> after this, then closes </body></html> */ ?>
