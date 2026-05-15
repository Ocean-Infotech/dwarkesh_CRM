<?php
$pageTitle = "Dashboard";
$currentPage = "dashboard";
$headerTitle = "Dashboard Overview";

// Extra content for head and footer
$extraHead = '
<link rel="stylesheet" href="assets/css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

include 'include/header.php';

// Get Filter Parameters
$filterMonth = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$filterYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$whereFilter = " AND MONTH(order_date) = $filterMonth AND YEAR(order_date) = $filterYear ";
$whereFilterCosting = " AND MONTH(created_at) = $filterMonth AND YEAR(created_at) = $filterYear ";

// Fetch Stats (Filtered)
$orderStats = $ai_db->aiGetQuery("SELECT COUNT(*) as total_count, SUM(rate * box_qty) as total_amount FROM tbl_orders WHERE is_deleted=0 $whereFilter");
$totalOrders = intval($orderStats[0]['total_count'] ?? 0);
$totalOrderAmount = floatval($orderStats[0]['total_amount'] ?? 0);

$costingStats = $ai_db->aiGetQuery("SELECT COUNT(*) as total_count, SUM(total) as total_amount FROM tbl_costings WHERE is_deleted=0 $whereFilterCosting");
$totalCostings = intval($costingStats[0]['total_count'] ?? 0);
$totalCostingAmount = floatval($costingStats[0]['total_amount'] ?? 0);

$customerStats = $ai_db->aiGetQuery("SELECT COUNT(*) as total_count FROM tbl_customer WHERE is_deleted=0");
$totalCustomers = intval($customerStats[0]['total_count'] ?? 0);

$productStats = $ai_db->aiGetQuery("SELECT COUNT(*) as total_count FROM tbl_product WHERE is_deleted=0");
$totalProducts = intval($productStats[0]['total_count'] ?? 0);

// Recent 10 Orders (Global or Filtered? Let's keep it global or last 10 for the filtered month)
$recentOrders = $ai_db->aiGetQuery("SELECT o.*, p.ply AS product_ply
    FROM tbl_orders o
    LEFT JOIN tbl_product p ON p.id = o.product_id
    WHERE o.is_deleted=0 $whereFilter
    ORDER BY o.id DESC
    LIMIT 10");

// Fetch DAILY Revenue Data for the SELECTED month
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $filterMonth, $filterYear);
$dailyRevenue = $ai_db->aiGetQuery("
    SELECT 
        DAY(order_date) as order_day, 
        SUM(rate * box_qty) as total_rev 
    FROM tbl_orders 
    WHERE is_deleted = 0 
    AND MONTH(order_date) = $filterMonth 
    AND YEAR(order_date) = $filterYear
    GROUP BY DAY(order_date)
    ORDER BY DAY(order_date) ASC
");

$revenueMap = [];
foreach ($dailyRevenue as $row) {
    $revenueMap[intval($row['order_day'])] = floatval($row['total_rev']);
}

$revLabels = [];
$revData = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $revLabels[] = "Day " . $d;
    $revData[] = $revenueMap[$d] ?? 0;
}

$extraFooter = '
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Revenue Chart (Creative Bar + Line hybrid look)
        const revCtx = document.getElementById("revenueChart").getContext("2d");
        const revDataPoints = ' . json_encode($revData) . ';
        
        if (revDataPoints.length === 0 || revDataPoints.every(v => v === 0)) {
            const container = revCtx.canvas.parentNode;
            container.innerHTML += `<div class="no-data-overlay">
                <i class="bi bi-graph-up d-block mb-2 fs-2 opacity-25"></i>
                <div class="fw-bold opacity-50 small">No revenue trend available for this period</div>
            </div>`;
        } else {
            const barGradient = revCtx.createLinearGradient(0, 0, 0, 400);
            barGradient.addColorStop(0, "rgba(197, 160, 89, 0.85)");
            barGradient.addColorStop(1, "rgba(197, 160, 89, 0.1)");

            new Chart(revCtx, {
                type: "bar",
                data: {
                    labels: ' . json_encode($revLabels) . ',
                    datasets: [{
                        label: "Daily Revenue",
                        data: revDataPoints,
                        backgroundColor: barGradient,
                        borderRadius: 8,
                        hoverBackgroundColor: "#c5a059",
                        barPercentage: 0.6,
                        categoryPercentage: 0.8,
                    }, {
                        label: "Trend Line",
                        data: revDataPoints,
                        type: "line",
                        borderColor: "rgba(30, 41, 59, 0.15)",
                        borderWidth: 2,
                        pointRadius: 0,
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: "index", intersect: false },
                    plugins: { 
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: "#1e293b",
                            padding: 12,
                            titleFont: { size: 14, weight: "bold" },
                            bodyFont: { size: 13 },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    if(context.datasetIndex === 1) return null;
                                    return "Revenue: " + new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR" }).format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            min: 0,
                            grid: { color: "rgba(0,0,0,0.03)", drawBorder: false },
                            ticks: { 
                                font: { family: "Inter", size: 11 },
                                callback: function(value) {
                                    if (value >= 1000) return "₹" + (value / 1000) + "k";
                                    return "₹" + value;
                                }
                            }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: {
                                font: { family: "Inter", size: 10, weight: "600" },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 15,
                                callback: function(val, index) {
                                    return (index + 1);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Distribution Chart (Premium Doughnut)
        const distCtx = document.getElementById("distChart").getContext("2d");
        const distData = [' . $totalOrderAmount . ', ' . $totalCostingAmount . '];
        
        if (distData[0] === 0 && distData[1] === 0) {
            const container = distCtx.canvas.parentNode;
            container.innerHTML += `<div class="no-data-overlay">
                <i class="bi bi-pie-chart d-block mb-2 fs-2 opacity-25"></i>
                <div class="fw-bold opacity-50 small">No distribution data for this month</div>
            </div>`;
        } else {
            const g1 = distCtx.createLinearGradient(0, 0, 0, 300);
            g1.addColorStop(0, "#c5a059");
            g1.addColorStop(1, "#f2d399");

            const g2 = distCtx.createLinearGradient(0, 0, 0, 300);
            g2.addColorStop(0, "#1e293b");
            g2.addColorStop(1, "#475569");

            new Chart(distCtx, {
                type: "doughnut",
                data: {
                    labels: ["Orders Value", "Costings Value"],
                    datasets: [{
                        data: distData,
                        backgroundColor: [g1, g2],
                        hoverBackgroundColor: ["#dcc28c", "#64748b"],
                        borderWidth: 0,
                        hoverOffset: 15,
                        borderRadius: 20,
                        spacing: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "80%",
                    plugins: {
                        legend: { position: "bottom", labels: { padding: 30, usePointStyle: true, font: { family: "Inter", weight: "700" } } },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ": " + new Intl.NumberFormat("en-IN", { style: "currency", currency: "INR" }).format(context.parsed);
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>';
?>

<div class="container-fluid py-4">
    <!-- Global Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div
                class="filter-wrapper p-3 shadow-sm rounded-4 bg-white d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="filter-icon me-3 bg-gold-light text-gold p-2 rounded-3">
                        <i class="bi bi-filter-left fs-5"></i>
                    </div>
                    <h6 class="fw-bold m-0 text-dark">Data Insights for
                        <?= date('F', mktime(0, 0, 0, $filterMonth, 10)) ?> <?= $filterYear ?>
                    </h6>
                </div>
                <form action="dashboard.php" method="GET" class="d-flex gap-2">
                    <select name="month"
                        class="form-select form-select-sm rounded-pill px-3 border-0 bg-light fw-semibold"
                        onchange="this.form.submit()">
                        <?php for ($m = 1; $m <= 12; $m++) { ?>
                            <option value="<?= $m ?>" <?= $filterMonth == $m ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                            </option>
                        <?php } ?>
                    </select>
                    <select name="year"
                        class="form-select form-select-sm rounded-pill px-3 border-0 bg-light fw-semibold"
                        onchange="this.form.submit()">
                        <?php for ($y = date('Y'); $y >= 2020; $y--) { ?>
                            <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php } ?>
                    </select>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold">Reset</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="premium-card card-orders">
                <div class="card-mesh"></div>
                <div class="card-content">
                    <div class="card-icon"><i class="bi bi-cart-check"></i></div>
                    <div class="card-info">
                        <div class="card-label">Total Orders</div>
                        <div class="card-main-val">
                            <?= number_format($totalOrders) ?>
                        </div>
                        <div class="card-sub-val">₹
                            <?= number_format($totalOrderAmount, 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card card-costing">
                <div class="card-mesh"></div>
                <div class="card-content">
                    <div class="card-icon"><i class="bi bi-calculator"></i></div>
                    <div class="card-info">
                        <div class="card-label">Total Costing</div>
                        <div class="card-main-val">
                            <?= number_format($totalCostings) ?>
                        </div>
                        <div class="card-sub-val">₹
                            <?= number_format($totalCostingAmount, 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card card-customers">
                <div class="card-mesh"></div>
                <div class="card-content">
                    <div class="card-icon"><i class="bi bi-people"></i></div>
                    <div class="card-info">
                        <div class="card-label">Total Customer</div>
                        <div class="card-main-val">
                            <?= number_format($totalCustomers) ?>
                        </div>
                        <div class="card-sub-val">Active Accounts</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="premium-card card-products">
                <div class="card-mesh"></div>
                <div class="card-content">
                    <div class="card-icon"><i class="bi bi-box-seam"></i></div>
                    <div class="card-info">
                        <div class="card-label">Total Product</div>
                        <div class="card-main-val">
                            <?= number_format($totalProducts) ?>
                        </div>
                        <div class="card-sub-val">Inventory SKUs</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="chart-card shadow-sm">
                <div class="card-header bg-transparent border-0 p-4 pb-0">
                    <h6 class="fw-bold m-0 text-dark">Revenue Trend:
                        <?= date('F Y', mktime(0, 0, 0, $filterMonth, 1, $filterYear)) ?>
                    </h6>
                    <p class="text-muted small m-0">Daily sales performance and revenue growth</p>
                </div>
                <div class="card-body p-4">
                    <div style="height: 350px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card shadow-sm">
                <div class="card-header bg-transparent border-0 p-4 pb-0">
                    <h6 class="fw-bold m-0 text-dark">Financial Distribution</h6>
                    <p class="text-muted small m-0">Orders vs Costings value ratio</p>
                </div>
                <div class="card-body p-4">
                    <div style="height: 350px; position: relative;">
                        <canvas id="distChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="dashboard-table-card shadow-sm border-0">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center p-4 pb-2">
            <div>
                <h5 class="fw-bold m-0">Recent Orders</h5>
                <p class="text-muted small m-0">Latest 10 transactions across the platform</p>
            </div>
            <a href="orders.php" class="btn btn-gold btn-sm rounded-pill px-4">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Order ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)) { ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No recent orders found.</td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($recentOrders as $order) {
                                $total = $order['rate'] * $order['box_qty'];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-gold">#
                                            <?= htmlspecialchars($order['order_no']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($order['customer_name']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($order['brand_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $dashboardProductName = trim((string) ($order['product_name'] ?? ''));
                                        $dashboardProductPly = trim((string) ($order['product_ply'] ?? ''));
                                        if ($dashboardProductName !== '' && $dashboardProductPly !== '' && stripos($dashboardProductName, $dashboardProductPly) === false) {
                                            $dashboardProductName .= ' - ' . $dashboardProductPly;
                                        }
                                        ?>
                                        <?= htmlspecialchars($dashboardProductName) ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold">₹
                                            <?= number_format($total, 2) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= date('d M, Y', strtotime($order['order_date'])) ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="orders.php?mode=edit&id=<?= $order['id'] ?>"
                                            class="btn btn-sm btn-light rounded-circle shadow-sm" title="Edit Order">
                                            <i class="bi bi-pencil-square text-primary"></i>
                                        </a>
                                        <a href="print_order.php?id=<?= $order['id'] ?>" target="_blank"
                                            class="btn btn-sm btn-light rounded-circle shadow-sm ms-1" title="Print Order">
                                            <i class="bi bi-printer text-success"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>
