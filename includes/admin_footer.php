</div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="modal fade" id="adminLogoutModal" tabindex="-1" aria-labelledby="adminLogoutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="adminLogoutLabel">
                    <i class="fa-solid fa-user-shield me-2"></i> Admin Console
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-secondary bg-secondary bg-opacity-10 p-3 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                    <i class="fa-solid fa-right-from-bracket fa-2x"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Ready to Leave?</h5>
                <p class="text-muted mb-0">Select "Logout" below if you are ready to end your current session.</p>
            </div>

            <div class="modal-footer border-0 bg-light d-flex justify-content-center gap-2 pb-4">
                <button type="button" class="btn btn-outline-secondary fw-bold px-4" data-bs-dismiss="modal">
                    Cancel
                </button>
                <a href="../../core/logout.php" class="btn btn-dark fw-bold px-4 shadow-sm">
                    Logout
                </a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Sidebar Toggle Script (Keep this if you have it)
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");

    if (toggleButton) {
        toggleButton.onclick = function () {
            el.classList.toggle("toggled");
        };
    }
</script>
</body>
</html>

<script>
    // Sidebar Toggle Script
    var el = document.getElementById("wrapper");
    var toggleButton = document.getElementById("menu-toggle");

    toggleButton.onclick = function () {
        el.classList.toggle("toggled");
    };
</script>
</body>
</html>