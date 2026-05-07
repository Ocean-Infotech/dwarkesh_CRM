<?php 
    $pageTitle = "Add Product";
    $currentPage = "form";
    $headerTitle = "Product Management";
    
    include 'include/header.php';
?>

<!-- Form Section -->
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card p-4">
            <div class="card-body">
                <div class="d-flex align-items-center mb-4">
                    <a href="dashboard.php" class="btn btn-sm btn-light me-3"><i class="bi bi-arrow-left"></i></a>
                    <h5 class="fw-bold m-0">Add New Product</h5>
                </div>
                <hr class="mb-4 opacity-50">

                <form id="addProductForm" class="row g-3" novalidate>
                    <div class="col-md-6">
                        <label for="productName" class="form-label fw-semibold">Product Name</label>
                        <input type="text" class="form-control" id="productName" placeholder="e.g. Heavy Duty Corrugated Box" required>
                    </div>

                    <div class="col-md-6">
                        <label for="category" class="form-label fw-semibold">Category</label>
                        <select class="form-select" id="category" required>
                            <option value="" selected disabled>Choose category...</option>
                            <option value="boxes">Corrugated Boxes</option>
                            <option value="films">Stretch Films</option>
                            <option value="tapes">Adhesive Tapes</option>
                            <option value="wrap">Bubble Wraps</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="price" class="form-label fw-semibold">Price ($)</label>
                        <input type="number" class="form-control" id="price" placeholder="0.00" step="0.01" required>
                    </div>

                    <div class="col-md-4">
                        <label for="quantity" class="form-label fw-semibold">Quantity</label>
                        <input type="number" class="form-control" id="quantity" placeholder="0" required>
                    </div>

                    <div class="col-md-4">
                        <label for="sku" class="form-label fw-semibold">SKU Code</label>
                        <input type="text" class="form-control" id="sku" placeholder="DP-XXXXX" required>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" rows="4" placeholder="Enter detailed product description..." required></textarea>
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">
                            <i class="bi bi-plus-lg me-2"></i> Save Product
                        </button>
                        <button type="reset" class="btn btn-light px-4 py-2 ms-2">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>
