<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "invoice_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process search filter if submitted
$search_term = "";
if (isset($_GET['search']) {
    $search_term = $conn->real_escape_string($_GET['search']);
}

// Get grouped distributor products with highest prices
$distributor_query = "
    SELECT 
        MAX(id) as id,
        distributor_name,
        product_name,
        SUM(purchase_quantity) as total_purchase_quantity,
        unit,
        MAX(purchase_unit_price) as max_purchase_price,
        MAX(selling_price) as max_selling_price,
        MAX(loading_charge) as loading_charge
    FROM distributorproduct
    WHERE 
        product_name LIKE '%$search_term%' OR
        distributor_name LIKE '%$search_term%' OR
        unit LIKE '%$search_term%' OR
        purchase_unit_price LIKE '%$search_term%' OR
        selling_price LIKE '%$search_term%'
    GROUP BY product_name, unit, distributor_name
    ORDER BY distributor_name, product_name
";

$distributor_result = $conn->query($distributor_query);

// Get grouped invoice items (sold products)
$invoice_query = "
    SELECT 
        product_name,
        unit,
        SUM(quantity) as total_sold_quantity
    FROM invoice_items
    GROUP BY product_name, unit
";

$invoice_result = $conn->query($invoice_query);
$sold_products = [];
while ($row = $invoice_result->fetch_assoc()) {
    $key = $row['product_name'] . '|' . $row['unit'];
    $sold_products[$key] = $row['total_sold_quantity'];
}

// Calculate available quantities
$available_products = [];
while ($row = $distributor_result->fetch_assoc()) {
    $key = $row['product_name'] . '|' . $row['unit'];
    $sold = isset($sold_products[$key]) ? $sold_products[$key] : 0;
    $available = $row['total_purchase_quantity'] - $sold;
    
    if ($available > 0) {
        $row['available_quantity'] = $available;
        $available_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Products Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #4895ef;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #7209b7;
            --light: #f8f9fa;
            --dark: #212529;
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .dashboard-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .header {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .header h2 {
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .search-box {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .product-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .table thead {
            background: var(--gradient-primary);
            color: white;
        }

        .table th {
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: none;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .loading-yes {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .loading-no {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
            font-weight: 600;
        }

        .low-stock {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
                margin-top: 1rem;
                margin-bottom: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .header h2 {
                font-size: 1.5rem;
            }
            
            .search-box {
                padding: 1rem;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                width: 100%;
                display: block;
            }
            
            .table thead {
                display: none;
            }
            
            .table tbody {
                display: block;
                width: 100%;
            }
            
            .table tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                padding: 0.5rem;
            }
            
            .table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem;
                border: none;
                border-bottom: 1px solid #e3e6f0;
            }
            
            .table td::before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: 1rem;
                color: var(--primary);
            }
            
            .table td:last-child {
                border-bottom: none;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.4s ease-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-container fade-in">
            <div class="header text-center">
                <h2><i class="bi bi-box-seam me-2"></i>Available Products Inventory</h2>
            </div>
            
            <div class="search-box">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search by product, distributor, unit, or price..." value="<?= htmlspecialchars($search_term) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="?" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="product-table">
                <div class="table-responsive">
                    <table class="table table-hover" id="inventoryTable">
                        <thead>
                            <tr>
                                <th>SL No</th>
                                <th>Distributor</th>
                                <th>Product</th>
                                <th>Available Qty</th>
                                <th>Unit</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Loading Charge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($available_products) > 0): ?>
                                <?php $sl = 1; ?>
                                <?php foreach ($available_products as $product): ?>
                                    <tr>
                                        <td data-label="SL No"><?= $sl++ ?></td>
                                        <td data-label="Distributor"><?= htmlspecialchars($product['distributor_name']) ?></td>
                                        <td data-label="Product"><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td data-label="Available Qty" class="<?= $product['available_quantity'] < 10 ? 'low-stock' : '' ?>">
                                            <?= number_format($product['available_quantity'], 2) ?>
                                        </td>
                                        <td data-label="Unit"><?= strtoupper($product['unit']) ?></td>
                                        <td data-label="Purchase Price"><?= $product['max_purchase_price'] ? number_format($product['max_purchase_price'], 2) : 'N/A' ?></td>
                                        <td data-label="Selling Price"><?= $product['max_selling_price'] ? number_format($product['max_selling_price'], 2) : 'N/A' ?></td>
                                        <td data-label="Loading Charge" class="<?= $product['loading_charge'] ? 'loading-yes' : 'loading-no' ?>">
                                            <?= $product['loading_charge'] ? 'YES' : 'NO' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No available products found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize mobile data labels
            function updateMobileDataLabels() {
                $('#inventoryTable tbody tr').each(function() {
                    $(this).find('td').each(function() {
                        const label = $(this).attr('data-label');
                        if (!label) {
                            const headerText = $('#inventoryTable thead th').eq($(this).index()).text();
                            $(this).attr('data-label', headerText);
                        }
                    });
                });
            }
            
            // Run on page load and window resize
            updateMobileDataLabels();
            $(window).resize(updateMobileDataLabels);
        });
    </script>
</body>
</html>