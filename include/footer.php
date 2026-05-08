</div>
</div>

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