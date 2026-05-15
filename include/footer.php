</div>
</div>

<?php
$lowStockCornerItems = [];
if (isset($lowStockMaterials) && is_array($lowStockMaterials)) {
    foreach ($lowStockMaterials as $material) {
        $lowStockCornerItems[] = [
            'name' => (string) ($material['name'] ?? ''),
            'stock_qty' => (float) ($material['stock_qty'] ?? 0),
        ];
    }
}
?>

<?php if (!empty($lowStockCornerItems)) { ?>
    <div id="lowStockCornerAlert" class="low-stock-corner-alert" aria-live="polite" role="button" tabindex="0"
        data-href="product_stock.php#materials">
        <button type="button" class="low-stock-corner-close" id="lowStockCornerClose" aria-label="Close notification">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="low-stock-corner-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="low-stock-corner-content">
            <div class="low-stock-corner-title">Low Stock</div>
            <div class="low-stock-corner-message" id="lowStockCornerMessage"></div>
        </div>
    </div>
<?php } ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/script.js"></script>
<script>
    $(document).ready(function () {
        const lowStockCornerItems = <?= json_encode($lowStockCornerItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const lowStockThreshold = <?= isset($lowStockThreshold) ? intval($lowStockThreshold) : 500 ?>;
        const lowStockAlertBox = document.getElementById('lowStockCornerAlert');
        const lowStockAlertMessage = document.getElementById('lowStockCornerMessage');
        const lowStockAlertClose = document.getElementById('lowStockCornerClose');

        if (lowStockAlertBox && Array.isArray(lowStockCornerItems) && lowStockCornerItems.length > 0) {
            let lowStockIndex = 0;
            let lowStockDismissed = false;
            let lowStockTimer = null;

            const openLowStockPage = () => {
                const targetUrl = lowStockAlertBox.getAttribute('data-href') || 'product_stock.php#materials';
                window.location.href = targetUrl;
            };

            lowStockAlertBox.addEventListener('click', function (event) {
                if (event.target.closest('#lowStockCornerClose')) {
                    return;
                }
                openLowStockPage();
            });

            lowStockAlertBox.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openLowStockPage();
                }
            });

            if (lowStockAlertClose) {
                lowStockAlertClose.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    lowStockDismissed = true;
                    if (lowStockTimer) {
                        clearInterval(lowStockTimer);
                    }
                    lowStockAlertBox.classList.add('is-closing');
                    setTimeout(function () {
                        lowStockAlertBox.style.display = 'none';
                    }, 280);
                });
            }

            const renderLowStockMessage = () => {
                const current = lowStockCornerItems[lowStockIndex];
                if (!current) {
                    return;
                }

                const qty = Number(current.stock_qty || 0).toFixed(2);
                lowStockAlertMessage.textContent = `${current.name} only ${qty} KG left (below ${lowStockThreshold} KG)`;
            };

            renderLowStockMessage();
            lowStockTimer = setInterval(function () {
                if (lowStockDismissed) {
                    return;
                }
                lowStockAlertBox.classList.add('is-closing');
                setTimeout(function () {
                    lowStockIndex = (lowStockIndex + 1) % lowStockCornerItems.length;
                    renderLowStockMessage();
                    lowStockAlertBox.classList.remove('is-closing');
                    lowStockAlertBox.classList.add('is-opening');
                    setTimeout(function () {
                        lowStockAlertBox.classList.remove('is-opening');
                    }, 320);
                }, 280);
            }, 5000);
        }

        <?php if (isset($_GET['msg'])) {
            $msg = $_GET['msg'];
            if ($msg == 1) { ?> showToast('Success', 'Record added successfully!', 'success'); <?php }
            if ($msg == 2) { ?> showToast('Update', 'Record updated successfully!', 'info'); <?php }
            if ($msg == 3) { ?> showToast('Deleted', 'Record deleted successfully!', 'warning'); <?php }
            if ($msg == 4) { ?> showToast('BOM Success', 'BOM item added successfully!', 'success'); <?php }
            if ($msg == 5) { ?> showToast('BOM Update', 'BOM item updated successfully!', 'info'); <?php }
            if ($msg == 6) { ?> showToast('BOM Deleted', 'BOM item deleted successfully!', 'warning'); <?php }
        } ?>

        <?php if (isset($error) && !empty($error)) { ?>
            showToast('Error', '<?= addslashes($error) ?>', 'danger');
        <?php } ?>
    });
</script>
<?php if (isset($extraFooter))
    echo $extraFooter; ?>
</body>

</html>
