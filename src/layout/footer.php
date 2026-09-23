  </main>

  <!-- FOOTER -->
  <footer class="relative z-10 mt-16 pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white/60 backdrop-blur-sm rounded-3xl border-2 border-rose-100 px-6 py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-chocolate-700">
          <img src="<?= asset('img/logo.jpg') ?>" alt="<?= e(APP_NAME) ?>"
               class="h-9 w-9 rounded-full object-cover border-2 border-rose-200 shadow-sm">
          <div class="flex flex-col leading-tight">
            <span class="font-sweet text-lg text-rose-400"><?= e(APP_NAME) ?></span>
            <span class="text-xs text-chocolate-500"><?= e(APP_TAGLINE) ?></span>
          </div>
        </div>
        <div class="text-sm text-chocolate-500">
          © <?= date('Y') ?> · v<?= e(APP_VERSION) ?>
        </div>
      </div>
    </div>
  </footer>

</body>
</html>
