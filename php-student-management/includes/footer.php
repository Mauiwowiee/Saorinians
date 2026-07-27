        </div>
    </main>
    
    <?php if (isLoggedIn()): ?>
    <footer class="footer mt-auto py-4 border-top" role="contentinfo">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted small mb-0">
                        <i class="bi bi-c-circle me-1" aria-hidden="true"></i>
                        <span><?= date('Y') ?></span>
                        <a href="#" class="text-muted text-decoration-none"><?= APP_NAME ?></a>
                        <span class="mx-1">v<?= APP_VERSION ?? '1.0.0' ?></span>
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <ul class="list-unstyled d-flex justify-content-center justify-content-md-end gap-3">
                        <li><a href="#" class="text-muted text-decoration-none small" title="Privacy Policy">Privacy</a></li>
                        <li><a href="#" class="text-muted text-decoration-none small" title="Terms of Service">Terms</a></li>
                        <li><a href="#" class="text-muted text-decoration-none small" title="Contact Us">Contact</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>
    
    <!-- Bootstrap 5 JS Bundle (Deferred for Performance) -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9yk0LslrbIlK12OSVAOS2FtqLoJihYYdHvT0q3+8duLvFixucQzRn0zIx" crossorigin="anonymous"></script>
    <!-- Custom JS (Deferred for Performance) -->
    <script defer src="<?= BASE_URL ?>assets/js/main.js"></script>
    
    <!-- Performance Optimization: Lazy load images -->
    <script>
        if ('IntersectionObserver' in window) {
            const images = document.querySelectorAll('img[loading="lazy"]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            });
            images.forEach(img => imageObserver.observe(img));
        }
    </script>
</body>
</html>
