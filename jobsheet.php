<?php
$pageTitle = "Job Sheet";
$currentPage = "jobsheet";
$headerTitle = "JobSheets";

$extraHead = '<link rel="stylesheet" href="assets/css/jobsheet.css">';

include 'include/header.php';

// Fetch orders for dropdown
$orders = $ai_db->aiGetQuery("SELECT id, order_no, customer_name, brand_name FROM tbl_orders WHERE is_deleted=0 ORDER BY id DESC");
?>

<div class="container-fluid py-4">
    <div class="report-card">
        <form id="jobsheet_filter_form">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="filter-label">Select JobSheet</label>
                    <select name="order_id" id="order_id" class="form-select select2-order">
                        <option value="">Select Orders</option>
                        <?php foreach ($orders as $order) { ?>
                            <option value="<?= $order['id'] ?>">
                                #<?= htmlspecialchars($order['order_no']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">From Date</label>
                    <input type="date" name="from_date" id="from_date" class="form-control"
                        placeholder="Enter From Date">
                </div>
                <div class="col-md-2">
                    <label class="filter-label">To Date</label>
                    <input type="date" name="to_date" id="to_date" class="form-control" placeholder="Enter To Date">
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Jobsheet Type</label>
                    <select name="jobsheet_type" id="jobsheet_type" class="form-select select2-type"
                        data-searchable="false">
                        <option value="Half">Half</option>
                        <option value="Full">Full</option>
                        <option value="New">New</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Generate Report</button>
                        <button type="button" id="btn_print" class="btn btn-outline-secondary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="report_display">
        <div class="report-result-container shadow-sm border-0">
            <i class="bi bi-file-earmark-text display-4 text-muted mb-3 opacity-25"></i>
            <h5 class="text-muted fw-bold">Select Order And Date To Generate Report</h5>
            <p class="text-muted small">Choose the criteria above to view detailed job sheets</p>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>

<script>
    $(document).ready(function () {
        $('.select2-order').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select Orders'
        });

        $('.select2-type').select2({
            theme: 'bootstrap-5',
            width: '100%',
            minimumResultsForSearch: Infinity
        });

        $('#jobsheet_filter_form').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $('#report_display').html('<div class="report-result-container"><div class="spinner-border text-primary" role="status"></div></div>');

            $.ajax({
                url: 'ajax_jobsheet.php',
                type: 'POST',
                data: formData + '&action=generate_report',
                success: function (response) {
                    $('#report_display').html(response);
                },
                error: function () {
                    $('#report_display').html('<div class="alert alert-danger">Error generating report.</div>');
                }
            });
        });

        $('#btn_print').on('click', function () {
            const orderId = $('#order_id').val();
            const fromDate = $('#from_date').val();
            const toDate = $('#to_date').val();
            const type = $('#jobsheet_type').val();

            if (!orderId && !fromDate) {
                showToast('Filter Required', 'Please select an order or date range to print.', 'warning');
                return;
            }

            window.open(`jobsheet_print.php?order_id=${orderId}&from_date=${fromDate}&to_date=${toDate}&type=${type}`, '_blank');
        });
    });
</script>