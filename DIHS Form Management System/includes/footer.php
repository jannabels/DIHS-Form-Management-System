<!-- Footer -->
<!-- Footer -->
<footer class="bg-white border-t border-gray-200">
    <div class="max-w-7xl mx-auto py-3 px-4 overflow-hidden sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="text-sm text-gray-500">
                &copy; <?= date('Y') ?> Dasmariñas Integrated High School. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom Scripts -->
<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips if using a library like Tippy.js
    if (typeof tippy === 'function') {
        tippy('[data-tippy-content]');
    }
    
    // Initialize any other global JavaScript functionality here
});
</script>

<!-- Additional Scripts -->
<?php if (isset($additional_scripts)): ?>
    <?php foreach ($additional_scripts as $script): ?>
        <script src="<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
