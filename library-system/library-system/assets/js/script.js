document.addEventListener('DOMContentLoaded', function () {

  // Confirm before destructive actions (delete, reject, etc.)
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  // Password strength meter
  const pwInput = document.getElementById('password');
  const pwBar = document.getElementById('pwStrengthBar');
  if (pwInput && pwBar) {
    pwInput.addEventListener('input', function () {
      const val = pwInput.value;
      let score = 0;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      const levels = [
        { pct: 0,   color: '#e5e8f0' },
        { pct: 25,  color: '#c23e63' },
        { pct: 50,  color: '#e0a53c' },
        { pct: 75,  color: '#4a6fa5' },
        { pct: 100, color: '#2fb98c' },
      ];
      const lvl = levels[score];
      pwBar.style.width = lvl.pct + '%';
      pwBar.style.background = lvl.color;
    });
  }

  // Live cover image preview on file select
  const coverInput = document.getElementById('cover_image');
  const coverPreview = document.getElementById('coverPreview');
  if (coverInput && coverPreview) {
    coverInput.addEventListener('change', function () {
      const file = coverInput.files[0];
      if (!file) return;

      const allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (!allowed.includes(file.type)) {
        alert('Please choose a JPG, PNG, or WEBP image.');
        coverInput.value = '';
        return;
      }
      if (file.size > 3 * 1024 * 1024) {
        alert('Image must be 3MB or smaller.');
        coverInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        coverPreview.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  // Bootstrap client-side validation styling
  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });

  // Auto-dismiss alerts after a few seconds
  document.querySelectorAll('.alert').forEach(function (alertEl) {
    setTimeout(function () {
      const alert = bootstrap.Alert.getOrCreateInstance(alertEl);
      alert.close();
    }, 6000);
  });
});
