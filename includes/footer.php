</main> <!-- End main-scrollable from header -->

<footer class="sticky-footer shadow-sm mt-auto">
    <div class="container-fluid px-4">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <span class="text-muted">&copy; <?php echo date('Y'); ?> <strong>XentraPOS</strong> - Premium Edition</span>
            </div>
            <div class="col-md-6 text-center text-md-end mt-2 mt-md-0">
                <span class="text-muted me-2 small">Developed by</span>
                <a href="https://github.com/thilinadias" target="_blank" class="text-primary text-decoration-none me-3 fw-bold">
                    <i class="bi bi-github"></i> Thilina Dias
                </a>
                <a href="https://www.linkedin.com/in/thilinaadias" target="_blank" class="text-primary text-decoration-none fw-bold">
                    <i class="bi bi-linkedin"></i> LinkedIn
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom Base JS -->
<script src="/pos/assets/js/main.js"></script>

<?php if (isset($_SESSION['user_id'])): ?>
<script src="/pos/assets/js/auth.js"></script>
<?php endif; ?>

</body>
</html>
