<?php
$pageTitle = "Job Sheet";
$currentPage = "jobsheet";
$headerTitle = "JobSheets";

$extraHead = '
    <style>
        .report-card {
            background: #fff;
            border-top: 4px solid var(--primary-gold);
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        .filter-label {
            font-weight: 700;
            font-size: 0.8rem;
            color: #444;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
        }
        .report-result-container {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-radius: 10px;
            border: 2px dashed #e2e8f0;
            margin-top: 20px;
        }
        .report-table th {
            background-color: #fdfaf3;
            color: var(--primary-gold-dark);
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            padding: 12px;
        }

        /* Unified Input Styling */
        .form-control, .form-select, .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px !important;
            padding: 0.75rem 1rem !important;
            height: auto !important;
            min-height: 50px;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #fff !important;
        }

        .form-control:focus, .form-select:focus, .select2-container--bootstrap-5.select2-container--focus .select2-selection {
            border-color: var(--primary-gold) !important;
            box-shadow: 0 0 0 4px rgba(197, 160, 89, 0.15) !important;
            outline: none;
        }

        .select2-container--bootstrap-5 .select2-selection {
            display: flex;
            align-items: center;
        }

        .select2-container--bootstrap-5 .select2-dropdown {
            border-color: #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: var(--primary-gold) !important;
        }

        .btn-primary, .btn-outline-secondary {
            border-radius: 10px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .btn-primary {
            background-color: var(--primary-gold);
            border-color: var(--primary-gold);
        }

        .btn-primary:hover {
            background-color: var(--primary-gold-dark);
            border-color: var(--primary-gold-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(197, 160, 89, 0.3);
        }
    </style>';

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
                    <select name="order_id" id="order_id" class="form-select select2-order" data-searchable="false">
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
        <div class="report-result-container">
            <h5 class="text-muted">Select Order And Date To Generate Report</h5>
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
                alert('Please select an order or date range.');
                return;
            }

            window.open(`jobsheet_print.php?order_id=${orderId}&from_date=${fromDate}&to_date=${toDate}&type=${type}`, '_blank');
        });
    });
</script>