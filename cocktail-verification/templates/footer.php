</div> <!-- End main-content -->
    </div> <!-- End container-fluid -->

    <!-- Footer -->
    <footer class="mt-5 py-4" style="background: rgba(0, 0, 0, 0.2); backdrop-filter: blur(10px); border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="fw-bold mb-3" style="color: white;">
                        <i class="fas fa-cocktail me-2"></i><?php echo APP_NAME; ?>
                    </h5>
                    <p style="color: rgba(255, 255, 255, 0.7);">
                        Collaborative cocktail tag verification platform for AI research and bartender community.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-sm btn-outline-light">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-light">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-light">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-light">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-md-2">
                    <h6 class="fw-bold mb-3" style="color: white;">Navigation</h6>
                    <ul class="list-unstyled" style="color: rgba(255, 255, 255, 0.7);">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>dashboard.php" class="text-decoration-none">Dashboard</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>cocktails.php" class="text-decoration-none">Cocktails</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>verify.php" class="text-decoration-none">Verify Tags</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>tags.php" class="text-decoration-none">Tag Library</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>export.php" class="text-decoration-none">Export Data</a></li>
                    </ul>
                </div>
                
                <div class="col-md-2">
                    <h6 class="fw-bold mb-3" style="color: white;">Resources</h6>
                    <ul class="list-unstyled" style="color: rgba(255, 255, 255, 0.7);">
                        <li class="mb-2"><a href="#" class="text-decoration-none">Documentation</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">API Reference</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">Guidelines</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">FAQ</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">Support</a></li>
                    </ul>
                </div>
                
                <div class="col-md-2">
                    <h6 class="fw-bold mb-3" style="color: white;">Legal</h6>
                    <ul class="list-unstyled" style="color: rgba(255, 255, 255, 0.7);">
                        <li class="mb-2"><a href="#" class="text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">Terms of Service</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">Cookie Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-md-2">
                    <h6 class="fw-bold mb-3" style="color: white;">Version</h6>
                    <p style="color: rgba(255, 255, 255, 0.7);">
                        <small>v1.0.0</small><br>
                        <small>Last Updated: <?php echo date('F Y'); ?></small>
                    </p>
                </div>
            </div>
            
            <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 2rem 0;">
            
            <div class="row">
                <div class="col-md-8">
                    <p style="color: rgba(255, 255, 255, 0.6); margin-bottom: 0;">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved. Built with <i class="fas fa-heart" style="color: #EF4444;"></i> for the bartender community.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <small style="color: rgba(255, 255, 255, 0.6);">
                        Status: <span class="badge bg-success">Online</span> | 
                        Uptime: <span class="badge bg-info">99.9%</span>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo ASSETS_PATH; ?>js/main.js"></script>
    
    <?php if (isLoggedIn() && $currentPage === 'verify.php'): ?>
    <script src="<?php echo ASSETS_PATH; ?>js/verification.js"></script>
    <?php endif; ?>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Auto-dismiss alerts
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
