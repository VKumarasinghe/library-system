</main>

<footer class="app-footer text-center py-4 mt-5">
  <div class="container">
    <small>&copy; <?= date('Y') ?> Smart Library Management System. All Rights Reserved.</small>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= isset($inAdminOrStudent) ? '../assets/js/script.js' : 'assets/js/script.js' ?>"></script>
</body>
</html>
