    </div> <!-- End container-fluid -->

    <footer class="mt-auto py-3 bg-white border-top">
        <div class="container text-center text-muted">
            <small>
                ArMaTech Pawnshop System &copy; <?php echo date('Y'); ?> <br>
                <span class="text-success"><i class="fa-solid fa-wifi"></i> System Online</span>
            </small>
        </div>
    </footer>

    </div> <!-- End page-content-wrapper -->
</div> <!-- End wrapper -->

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="logoutModalLabel">
                    <i class="bi bi-box-arrow-right me-2"></i> Ready to Leave?
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger bg-danger bg-opacity-10 p-3 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="bi bi-power fa-2x" style="font-size: 2rem;"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Confirm Logout</h5>
                <p class="text-muted mb-0">Select ‘Logout’ to safely exit your account.</p>
            </div>
            <div class="modal-footer border-0 bg-light d-flex justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-light border fw-bold px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                <a href="../../core/logout.php" class="btn btn-danger fw-bold px-4 rounded-pill shadow-sm">Logout</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Sidebar Toggle Script
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");

    if (toggleButton) {
        toggleButton.onclick = function () {
            el.classList.toggle("toggled");
        };
    }

    document.addEventListener('keydown', function(event) {
        // Alt + N = New Pawn
        if (event.altKey && event.key === 'n') {
            window.location.href = 'new_pawn.php';
        }
    });
</script>

</body>
</html>