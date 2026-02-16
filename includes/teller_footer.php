<footer class="mt-auto py-3 bg-white border-top">
    <div class="container text-center text-muted">
        <small>
            ArMaTech Pawnshop System &copy; <?php echo date('Y'); ?> <br>
            <span class="text-success"><i class="fa-solid fa-wifi"></i> System Online</span>
        </small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('keydown', function(event) {
        // Alt + N = New Pawn
        if (event.altKey && event.key === 'n') {
            window.location.href = 'new_pawn.php';
        }
    });
</script>

</body>
</html>