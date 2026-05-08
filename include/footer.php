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
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    $(document).ready(function () {
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "3000"
        };

        <?php if (isset($_GET['msg'])) {
            $msg = $_GET['msg'];
            if ($msg == 1) { ?> toastr.success('Record added successfully!'); <?php }
            if ($msg == 2) { ?> toastr.info('Record updated successfully!'); <?php }
            if ($msg == 3) { ?> toastr.warning('Record deleted successfully!'); <?php }
        } ?>

        <?php if (isset($error) && !empty($error)) { ?>
            toastr.error('<?= addslashes($error) ?>');
        <?php } ?>
    });
</script>
<?php if (isset($extraFooter))
    echo $extraFooter; ?>
</body>

</html>