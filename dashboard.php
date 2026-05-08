<?php
$pageTitle = "Dashboard";
$currentPage = "dashboard";
$headerTitle = "Dashboard Overview";

// Extra content for head and footer
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
$extraFooter = '
    <script>
        window.addEventListener("load", function() {
            const revCanvas = document.getElementById("revenueChart");
            if (revCanvas) {
                const ctx1 = revCanvas.getContext("2d");
                new Chart(ctx1, {
                    type: "line",
                    data: {
                        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
                        datasets: [{
                            label: "Revenue",
                            data: [12000, 19000, 15000, 25000, 22000, 30000],
                            borderColor: "#c5a059",
                            backgroundColor: "rgba(197, 160, 89, 0.1)",
                            borderWidth: 3,
                            pointBackgroundColor: "#c5a059",
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            const orderCanvas = document.getElementById("orderChart");
            if (orderCanvas) {
                const ctx2 = orderCanvas.getContext("2d");
                new Chart(ctx2, {
                    type: "doughnut",
                    data: {
                        labels: ["Completed", "Pending", "Cancelled"],
                        datasets: [{
                            data: [65, 25, 10],
                            backgroundColor: ["#c5a059", "#1a1a1a", "#e9ecef"],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: "bottom" } }
                    }
                });
            }
        });
    </script>';

include 'include/header.php';
?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-gold">
            <div class="card-body">
                <div class="card-title text-uppercase">Total Orders</div>
                <div class="card-value">1,284</div>
                <div class="text-success small mt-2"><i class="bi bi-arrow-up"></i> 12% since last month</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-info">
            <div class="card-body">
                <div class="card-title text-uppercase">Revenue</div>
                <div class="card-value">$42,500</div>
                <div class="text-success small mt-2"><i class="bi bi-arrow-up"></i> 8.5% since last month</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-success">
            <div class="card-body">
                <div class="card-title text-uppercase">Customers</div>
                <div class="card-value">856</div>
                <div class="text-success small mt-2"><i class="bi bi-arrow-up"></i> 5% since last month</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 border-start border-4 border-warning">
            <div class="card-body">
                <div class="card-title text-uppercase">Products</div>
                <div class="card-value">124</div>
                <div class="text-muted small mt-2">Active SKUs</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card p-3">
            <div class="card-body">
                <h6 class="fw-bold mb-4">Sales Revenue (Last 6 Months)</h6>
                <div style="height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <div class="card-body">
                <h6 class="fw-bold mb-4">Order Distribution</h6>
                <div style="height: 300px;">
                    <canvas id="orderChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Data Table -->
<div class="card p-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-bold m-0">Recent Orders</h6>
            <button class="btn btn-sm btn-outline-primary">View All</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#ORD-001</td>
                        <td>John Doe</td>
                        <td>Corrugated Box</td>
                        <td>$450.00</td>
                        <td><span class="badge bg-success-subtle text-success">Completed</span></td>
                        <td><button class="btn btn-sm btn-light"><i class="bi bi-three-dots-vertical"></i></button></td>
                    </tr>
                    <tr>
                        <td>#ORD-002</td>
                        <td>Jane Smith</td>
                        <td>Stretch Film</td>
                        <td>$280.00</td>
                        <td><span class="badge bg-warning-subtle text-warning">Pending</span></td>
                        <td><button class="btn btn-sm btn-light"><i class="bi bi-three-dots-vertical"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>