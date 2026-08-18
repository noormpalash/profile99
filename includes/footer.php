</div> <!-- Closes main-content -->

<footer class="app-footer mt-auto py-3 border-top">
  <div class="container-fluid">
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 text-muted small">
      <div>
        &copy; <?= date('Y') ?> Unit Personnel System. All rights reserved.
      </div>
      <div class="fw-medium text-secondary">
        Developed by Cpl Noor Mohammad Palash, EB
      </div>
    </div>
  </div>
</footer>
<script src="../assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/main.js?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
<?php if (Auth::isLoggedIn()): ?>
  <script>
    // Security: auto-logout after 5 minutes of inactivity
    (function () {
      const TIMEOUT = <?= Auth::getSessionTimeout() ?> * 1000; // ms
      const WARN_BEFORE = 30000; // warn 30s before
      let timer, warnTimer, warnEl;

      function resetTimer() {
        clearTimeout(timer);
        clearTimeout(warnTimer);
        if (warnEl) { warnEl.remove(); warnEl = null; }

        warnTimer = setTimeout(function () {
          warnEl = document.createElement('div');
          warnEl.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:9999;background:#dc3545;color:#fff;text-align:center;padding:10px;font-weight:600;font-size:0.9rem;';
          warnEl.textContent = 'Session expiring in 30 seconds due to inactivity. Move your mouse to stay logged in.';
          document.body.appendChild(warnEl);
        }, TIMEOUT - WARN_BEFORE);

        timer = setTimeout(function () {
          const fd = new FormData();
          fd.append('csrf_token', '<?= Auth::csrfToken() ?>');
          fetch('../pages/logout.php', { method: 'POST', body: fd }).finally(() => {
            window.location.href = '../pages/login.php?reason=timeout';
          });
        }, TIMEOUT);
      }

      ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll', 'click'].forEach(function (e) {
        document.addEventListener(e, resetTimer, { passive: true });
      });

      resetTimer();

      // Tab close auto-logout using sessionStorage
      <?php if (!empty($_SESSION['just_logged_in'])): ?>
        sessionStorage.setItem('app_tab_active', '1');
        <?php unset($_SESSION['just_logged_in']); ?>
      <?php endif; ?>

      if (!sessionStorage.getItem('app_tab_active')) {
        window.location.href = '../pages/login.php?reason=tabclosed';
      }

      let isNav = false;
      document.addEventListener('click', e => { if (e.target.closest('a') || e.target.closest('button')) isNav = true; });
      document.addEventListener('submit', () => { isNav = true; });
    })();
  </script>
<?php endif; ?>
</body>

</html>