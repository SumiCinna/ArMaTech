<footer class="text-center text-muted py-4 mt-5">
    <small>&copy; <?php echo date('Y'); ?> ArMaTech Pawnshop System. All rights reserved.</small>
</footer>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="logoutModalLabel">
                    <i class="fa-solid fa-right-from-bracket me-2"></i> Ready to Leave?
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger bg-danger bg-opacity-10 p-3 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-power-off fa-2x"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Confirm Logout</h5>
                <p class="text-muted mb-0">Select ‘Logout’ to safely exit your account.</p>
            </div>

            <div class="modal-footer border-0 bg-light d-flex justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-light border fw-bold px-4 rounded-pill" data-bs-dismiss="modal">
                    Cancel
                </button>
                <a href="../../core/logout.php" class="btn btn-danger fw-bold px-4 rounded-pill shadow-sm">
                    Logout
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>