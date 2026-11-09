(function () {
  // mobile menu
  var btn = document.getElementById('menuBtn');
  if (btn) {
    btn.addEventListener('click', function () {
      var t = document.getElementById('mainnav') || document.getElementById('sidebar');
      if (t) t.classList.toggle('open');
    });
  }

  // confirm before destructive actions: <form data-confirm="Delete this?">
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // password match check
  document.querySelectorAll('form[data-match]').forEach(function (f) {
    var a = f.querySelector('[name="password"], [name="new_password"]');
    var b = f.querySelector('[name="password2"], [name="new_password2"]');
    if (!a || !b) return;
    function check() { b.setCustomValidity(b.value && a.value !== b.value ? 'Passwords do not match' : ''); }
    a.addEventListener('input', check);
    b.addEventListener('input', check);
  });

  // upload size / type check before sending
  var file = document.querySelector('input[type=file][data-max]');
  if (file) {
    file.addEventListener('change', function () {
      var max = parseInt(file.getAttribute('data-max'), 10) * 1048576;
      if (file.files[0] && file.files[0].size > max) {
        file.setCustomValidity('File is larger than ' + file.getAttribute('data-max') + ' MB');
        file.reportValidity();
      } else {
        file.setCustomValidity('');
      }
    });
  }

  // auto-submit filter selects
  document.querySelectorAll('select[data-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });

  // fade flash messages
  setTimeout(function () {
    document.querySelectorAll('.flashes .alert-success').forEach(function (a) { a.style.display = 'none'; });
  }, 6000);
})();
