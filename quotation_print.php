<?php
require_once 'root/config.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid Quotation ID");
}

// Fetch Quotation
$quotation = $ai_db->aiGetQuery("SELECT * FROM tbl_quotations WHERE id=$id AND is_deleted=0")[0] ?? null;
if (!$quotation) {
    die("Quotation not found");
}

// Fetch Items with HSN Code
$items = $ai_db->aiGetQuery("SELECT qi.*, p.hsn_code FROM tbl_quotation_items qi LEFT JOIN tbl_product p ON qi.product_id = p.id WHERE qi.quotation_id=$id");

// Group by HSN
$hsn_summary = [];
foreach ($items as $item) {
    $hsn = !empty($item['hsn_code']) ? $item['hsn_code'] : 'N/A';
    if (!isset($hsn_summary[$hsn])) {
        $hsn_summary[$hsn] = ['taxable' => 0, 'tax' => 0];
    }
    $hsn_summary[$hsn]['taxable'] += $item['taxable_amount'];
    $hsn_summary[$hsn]['tax'] += ($item['total_amount'] - $item['taxable_amount']);
}

// Fetch Customer
$customer = $ai_db->aiGetQuery("SELECT * FROM tbl_customer WHERE id=" . $quotation['customer_id'])[0] ?? null;

// Function to convert number to words
function numberToWords($number) {
    $number = floatval($number);
    $fraction = round(($number - floor($number)) * 100);
    $fullNumber = floor($number);
    
    $firstPart = convertToWords($fullNumber) . " Rupees";
    $secondPart = $fraction > 0 ? " and " . convertToWords($fraction) . " Paise" : "";
    
    return strtoupper($firstPart . $secondPart . " ONLY");
}

function convertToWords($number) {
    $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    if ($number == 0) return 'Zero';
    
    $words = '';
    
    if (floor($number / 10000000) > 0) {
        $words .= convertToWords(floor($number / 10000000)) . ' Crore ';
        $number %= 10000000;
    }
    
    if (floor($number / 100000) > 0) {
        $words .= convertToWords(floor($number / 100000)) . ' Lakh ';
        $number %= 100000;
    }
    
    if (floor($number / 1000) > 0) {
        $words .= convertToWords(floor($number / 1000)) . ' Thousand ';
        $number %= 1000;
    }
    
    if (floor($number / 100) > 0) {
        $words .= convertToWords(floor($number / 100)) . ' Hundred ';
        $number %= 100;
    }
    
    if ($number > 0) {
        if ($words !== '') $words .= 'and ';
        if ($number < 20) $words .= $units[$number];
        else {
            $words .= $tens[floor($number / 10)];
            if ($number % 10 > 0) $words .= '-' . $units[$number % 10];
        }
    }
    
    return trim($words);
}

$grand_total_words = numberToWords($quotation['total_amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - <?= $quotation['quotation_no'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; margin: 0; padding: 0; }
            .quotation-container { border: none !important; box-shadow: none !important; margin: 0 !important; width: 210mm !important; height: 297mm !important; overflow: hidden; }
        }
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #333; }
        .quotation-container {
            width: 210mm;
            height: auto;
            margin: 0;
            background: white;
            padding: 5mm 15mm 5mm 15mm;
            box-shadow: none;
            border: none;
        }
        .header-section { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 12px; }
        .company-name { font-size: 24px; font-weight: 800; color: #333; margin-bottom: 5px; }
        .company-details { font-size: 12px; line-height: 1.5; color: #555; }
        .logo-img { width: 100%; max-width: 350px; height: 100px; object-fit: contain; object-position: right; margin-right: 30px; }
        .title-section { text-align: center; margin: 15px 0; }
        .title-section h1 { font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin: 0; text-decoration: underline; }
        .info-row { margin-bottom: 15px; }
        .info-box { font-size: 13px; line-height: 1.6; }
        .info-label { font-weight: 700; color: #666; display: inline-block; width: 100px; }
        .address-box h6 { font-weight: 700; text-transform: uppercase; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; }
        .table thead th { background: #fdfdfd; border-bottom: 2px solid #333 !important; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #444; }
        .table tbody td { font-size: 13px; padding: 8px 8px; border-bottom: 1px solid #eee; }
        .item-name { font-weight: 700; display: block; color: #000; }
        .summary-section { margin-top: 15px; font-size: 12px; }
        .bank-details { line-height: 1.8; }
        .bank-label { display: inline-block; width: 90px; color: #666; }
        .total-box { border-left: 1px solid #eee; padding-left: 15px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .grand-total-row { display: flex; justify-content: space-between; margin-top: 10px; padding-top: 10px; border-top: 2px solid #333; font-weight: 800; font-size: 15px; }
        .signature-section { margin-top: 30px; text-align: right; }
        .signature-box { display: inline-block; text-align: center; min-width: 200px; }
        .signature-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; font-weight: 700; font-size: 13px; }
        .footer-gst { margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; font-size: 11px; }
        .footer-note { font-size: 10px; color: #999; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="no-print container text-center my-3">
        <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm">
            <i class="bi bi-printer me-2"></i> Print Quotation
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 ms-2">Close</button>
    </div>

    <div class="quotation-container">
        <!-- Header -->
        <div class="header-section">
            <div class="row align-items-center">
                <div class="col-7">
                    <div class="company-name">Dwarkesh Packaging</div>
                    <div class="company-details">
                        Jaynath Ind.-3, Lothada,<br>
                        Rajkot, Gujarat, 360024<br>
                        <strong>GST :</strong> 24AARFD0977E1ZC<br>
                        <strong>Phone :</strong> 9687009157<br>
                        <strong>Email :</strong> dwarkeshpackagingindustry@gmail.com
                    </div>
                </div>
                <div class="col-5 text-end">
                    <img src="assets/images/quotation_header_logo.png" class="logo-img" alt="Dwarkesh Packaging">
                </div>
            </div>
        </div>

        <!-- Title -->
        <div class="title-section">
            <h1>QUOTATION</h1>
        </div>

        <!-- Meta Info -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="address-box">
                    <h6>Billing & Shipping Address</h6>
                    <div class="fw-bold" style="font-size: 14px;"><?= htmlspecialchars($customer['contact_name'] ?? $quotation['customer_name']) ?></div>
                    <div class="text-muted small">
                        <?= nl2br(htmlspecialchars($customer['address'] ?? '')) ?><br>
                        <?= htmlspecialchars($customer['city_name'] ?? '') ?><br>
                        <strong>Phone :</strong> <?= htmlspecialchars($customer['phone_no'] ?? '') ?>
                    </div>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="info-box d-inline-block text-start">
                    <table class="w-100">
                        <tr>
                            <td class="info-label text-dark fw-bold" style="width: 110px;">Quotation No.</td>
                            <td class="fw-bold px-2">:</td>
                            <td class="fw-bold text-dark"><?= $quotation['quotation_no'] ?></td>
                        </tr>
                        <tr>
                            <td class="info-label text-dark fw-bold">Date</td>
                            <td class="fw-bold px-2">:</td>
                            <td class="fw-bold text-dark"><?= date('d-M-Y', strtotime($quotation['quotation_date'])) ?></td>
                        </tr>
                        <tr>
                            <td class="info-label text-dark fw-bold">Valid till</td>
                            <td class="fw-bold px-2">:</td>
                            <td class="fw-bold text-dark"><?= (!empty($quotation['valid_till']) && $quotation['valid_till'] !== '0000-00-00') ? date('d-M-Y', strtotime($quotation['valid_till'])) : '-' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table table-borderless">
            <thead>
                <tr>
                    <th width="50">No.</th>
                    <th>Item & Description</th>
                    <th class="text-center" width="80">Qty</th>
                    <th class="text-center" width="80">Unit</th>
                    <th class="text-end" width="100">Rate (₹)</th>
                    <th class="text-end" width="120">Taxable (₹)</th>
                    <th class="text-end" width="120">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $item) { ?>
                    <tr>
                        <td><?= $idx + 1 ?></td>
                        <td>
                            <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        </td>
                        <td class="text-center"><?= number_format($item['qty'], 2) ?></td>
                        <td class="text-center"><?= htmlspecialchars($item['unit']) ?></td>
                        <td class="text-end"><?= number_format($item['rate'], 2) ?></td>
                        <td class="text-end"><?= number_format($item['taxable_amount'], 2) ?></td>
                        <td class="text-end"><?= number_format($item['total_amount'], 2) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Summary Row 1: Words and Totals -->
        <div class="summary-section mt-4 border-top border-bottom py-3">
            <div class="row align-items-center">
                <div class="col-7 border-end px-4">
                    <div class="fw-bold mb-1 text-uppercase small" style="text-decoration: underline; font-size: 11px;">Total Amount in Words :</div>
                    <div class="fw-bold text-dark small mt-2"><?= $grand_total_words ?></div>
                </div>
                <div class="col-5 px-4 text-end">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-muted small">Total Amount before Tax (₹)</span>
                        <span class="fw-bold small"><?= number_format($quotation['total_taxable'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-2 border-dark mt-2">
                        <span class="fw-bold text-dark h6 mb-0">Grand Total (₹)</span>
                        <span class="fw-bold h4 mb-0"><?= number_format($quotation['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Row 2: Bank and Signature -->
        <div class="footer-section mt-4">
            <div class="row align-items-end">
                <div class="col-7">
                    <div class="bank-details">
                        <div class="fw-bold mb-1 text-uppercase small" style="text-decoration: underline; font-size: 11px;">Bank Details :</div>
                        <div class="small mt-2">
                            <strong>Bank Name :</strong> KOTAK MAHINDRA BANK<br>
                            <strong>Branch :</strong> JIMKHANA BRANCH<br>
                            <strong>Account No. :</strong> 9687009157<br>
                            <strong>IFSC :</strong> KKBK0002795
                        </div>
                    </div>
                </div>
                <div class="col-5 text-end">
                        <div class="signature-box d-inline-block text-center" style="width: 200px;">
                            <div class="small mb-1">For, Dwarkesh Packaging</div>
                            <div class="my-2">
                                <img src="assets/images/sign.png" alt="Signature" style="height: 70px; width: auto; object-fit: contain; mix-blend-mode: multiply;">
                            </div>
                            <div class="fw-bold small">Authorised Signatory</div>
                        </div>
                </div>
            </div>
        </div>

        <!-- Summary Row 3: GST Summary Table -->
        <div class="footer-gst mt-4 pt-3 border-top">
            <table class="table table-bordered table-sm m-0 text-center" style="font-size: 10px; border: 1.5px solid #333;">
                <thead>
                    <tr class="bg-light">
                        <th style="border: 1px solid #333;">HSN/SAC CODE</th>
                        <th style="border: 1px solid #333;">TAXABLE (₹)</th>
                        <th style="border: 1px solid #333;">CGST %</th>
                        <th style="border: 1px solid #333;">CGST (₹)</th>
                        <th style="border: 1px solid #333;">SGST %</th>
                        <th style="border: 1px solid #333;">SGST (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hsn_summary as $hsn => $totals) { 
                        $split_tax = $totals['tax'] / 2;
                    ?>
                        <tr>
                            <td style="border: 1px solid #333;"><?= htmlspecialchars($hsn) ?></td>
                            <td style="border: 1px solid #333;"><?= number_format($totals['taxable'], 2) ?></td>
                            <td style="border: 1px solid #333;">2.50%</td>
                            <td style="border: 1px solid #333;"><?= number_format($split_tax, 2) ?></td>
                            <td style="border: 1px solid #333;">2.50%</td>
                            <td style="border: 1px solid #333;"><?= number_format($split_tax, 2) ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="footer-note">
            This is a computer-generated quotation. E. & O. E.
        </div>
    </div>

    <script>
        // Auto print on load if needed
        // window.onload = () => { window.print(); };
    </script>
</body>
</html>
