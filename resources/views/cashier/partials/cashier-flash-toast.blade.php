{{-- Centered toast; optional reload (e.g. after AJAX success). Session flash if present. --}}
<style>
  #cashierFlashToast {
    z-index: 1090;
    min-width: 260px;
    background: linear-gradient(135deg, #198754 0%, #157347 100%);
    color: #fff;
  }
  #cashierFlashToast.cashier-flash--danger {
    background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%);
  }
</style>
<div id="cashierFlashToast" role="status" aria-live="polite"
  class="position-fixed top-50 start-50 translate-middle d-none px-5 py-4 rounded-3 fw-semibold shadow-lg text-center fs-5"></div>
<script>
(function () {
  window.showCashierFlashToast = function (message, options) {
    options = options || {};
    var danger = options.variant === 'danger';
    var reloadAfter = !!options.reloadAfter;
    var ms = reloadAfter ? 1350 : 2600;
    var el = document.getElementById('cashierFlashToast');
    if (!el || typeof message !== 'string') return;
    el.textContent = message;
    el.classList.toggle('cashier-flash--danger', danger);
    el.classList.remove('d-none');
    clearTimeout(window.__cashierFlashToastT);
    window.__cashierFlashToastT = setTimeout(function () {
      el.classList.add('d-none');
      if (reloadAfter) window.location.reload();
    }, ms);
  };
})();
@if(session('success'))
document.addEventListener('DOMContentLoaded', function () {
  if (typeof showCashierFlashToast === 'function') {
    showCashierFlashToast(@json(session('success')), { variant: 'success' });
  }
});
@endif
</script>
