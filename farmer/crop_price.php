<?php
	session_start();
	include_once('../_functions.php');
  global $conn;
	if(!isset($_SESSION['user']))
	{
		header("location:../login");
		exit;
	}
	else
	{
		check_role($_SESSION['user']['role'],basename(__DIR__));

    $user_id = $_SESSION['user']['user_id'];
    $status_message = '';
    $error_message = '';
    $search_term = trim($_GET['search'] ?? '');
    $type_filter = trim($_GET['type'] ?? '');

    function safe_price_to_float($value)
    {
      return (float) str_replace([',', ' '], '', (string) $value);
    }

    function get_product_stock($conn, $pro_id)
    {
      $stmt = $conn->prepare("SELECT COALESCE(pro_price, 0) AS pro_price, COALESCE(pro_qty, 0) AS pro_qty FROM pro_inventory WHERE pro_id = ? AND is_active = 'Y' ORDER BY pro_inventory_id DESC LIMIT 1");
      $stmt->bind_param("i", $pro_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $stock = ['pro_price' => 0, 'pro_qty' => 0];
      if ($row = $result->fetch_assoc()) {
        $stock = $row;
      }
      $stmt->close();
      return $stock;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $action = $_POST['action'] ?? '';
      $product_id = intval($_POST['pro_id'] ?? 0);
      $quantity = max(1, intval($_POST['quantity'] ?? 1));

      if ($action === 'add_to_cart' && $product_id > 0) {
        $stock = get_product_stock($conn, $product_id);
        $available_qty = intval($stock['pro_qty']);

        $stmt = $conn->prepare("SELECT cart_id, pro_qty FROM user_cart WHERE user_id = ? AND pro_id = ? AND is_active = 'Y' LIMIT 1");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($cart_row = $result->fetch_assoc()) {
          $new_qty = intval($cart_row['pro_qty']) + $quantity;
          if ($available_qty > 0 && $new_qty > $available_qty) {
            $error_message = 'Requested quantity exceeds available stock.';
          } else {
            $update = $conn->prepare("UPDATE user_cart SET pro_qty = ?, modified_on = ?, modified_by = ? WHERE cart_id = ?");
            $update->bind_param("isii", $new_qty, $cur_datetime, $user_id, $cart_row['cart_id']);
            $update->execute();
            $update->close();
            $status_message = 'Item quantity updated in cart.';
          }
        } else {
          if ($available_qty > 0 && $quantity > $available_qty) {
            $error_message = 'Requested quantity exceeds available stock.';
          } else {
            $insert = $conn->prepare("INSERT INTO user_cart (pro_id, user_id, pro_qty, is_active, created_on, created_by) VALUES (?, ?, ?, 'Y', ?, ?)");
            $insert->bind_param("iiisi", $product_id, $user_id, $quantity, $cur_datetime, $user_id);
            $insert->execute();
            $insert->close();
            $status_message = 'Item added to cart.';
          }
        }
        $stmt->close();
      }

      if ($action === 'update_cart' && $product_id > 0) {
        $quantity = max(1, $quantity);
        $stock = get_product_stock($conn, $product_id);
        $available_qty = intval($stock['pro_qty']);
        if ($available_qty > 0 && $quantity > $available_qty) {
          $error_message = 'Requested quantity exceeds available stock.';
        } else {
          $update = $conn->prepare("UPDATE user_cart SET pro_qty = ?, modified_on = ?, modified_by = ? WHERE user_id = ? AND pro_id = ? AND is_active = 'Y'");
          $update->bind_param("isiii", $quantity, $cur_datetime, $user_id, $user_id, $product_id);
          $update->execute();
          $update->close();
          $status_message = 'Cart updated.';
        }
      }

      if ($action === 'remove_from_cart' && $product_id > 0) {
        $remove = $conn->prepare("UPDATE user_cart SET is_active = 'N', modified_on = ?, modified_by = ? WHERE user_id = ? AND pro_id = ? AND is_active = 'Y'");
        $remove->bind_param("siii", $cur_datetime, $user_id, $user_id, $product_id);
        $remove->execute();
        $remove->close();
        $status_message = 'Item removed from cart.';
      }

      if ($action === 'checkout') {
        $payment_method = trim($_POST['payment_method'] ?? 'Cash on Delivery');
        $items_stmt = $conn->prepare("SELECT uc.pro_id, uc.pro_qty, p.pro_name, COALESCE(pi.pro_price, 0) AS pro_price FROM user_cart uc INNER JOIN products p ON p.pro_id = uc.pro_id LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = 'Y' WHERE uc.user_id = ? AND uc.is_active = 'Y'");
        $items_stmt->bind_param("i", $user_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $cart_items = [];
        while ($item = $items_result->fetch_assoc()) {
          $cart_items[] = $item;
        }
        $items_stmt->close();

        if (empty($cart_items)) {
          $error_message = 'Your cart is empty.';
        } else {
          $transaction_id = 'TXN' . date('YmdHis') . $user_id;
          $total_amount = 0;
          foreach ($cart_items as $item) {
            $pro_id = intval($item['pro_id']);
            $pro_qty = intval($item['pro_qty']);
            $line_total = safe_price_to_float($item['pro_price']) * $pro_qty;
            $total_amount += $line_total;
            $purchase = $conn->prepare("INSERT INTO purchase_product (user_id, pro_id, pro_qty, total_amt, payment_method, transaction_id, created_by, created_on) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $purchase->bind_param("iiidssis", $user_id, $pro_id, $pro_qty, $line_total, $payment_method, $transaction_id, $user_id, $cur_datetime);
            $purchase->execute();
            $purchase->close();
          }
          $clear = $conn->prepare("UPDATE user_cart SET is_active = 'N', modified_on = ?, modified_by = ? WHERE user_id = ? AND is_active = 'Y'");
          $clear->bind_param("sii", $cur_datetime, $user_id, $user_id);
          $clear->execute();
          $clear->close();
          $status_message = 'Order placed successfully. Transaction ID: ' . $transaction_id;
        }
      }
    }

    $product_sql = "SELECT p.pro_id, p.pro_name, p.pro_image, p.pro_description, p.pro_uses, p.pro_contents, p.type, p.is_block, COALESCE(pi.pro_price, 0) AS pro_price, COALESCE(pi.pro_qty, 0) AS pro_qty, ROUND(COALESCE(AVG(pr.rating), 0), 1) AS avg_rating, COUNT(pr.pro_rating_id) AS rating_count FROM products p LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = 'Y' LEFT JOIN pro_rating pr ON pr.pro_id = p.pro_id WHERE p.is_active = 'Y'";
    $params = [];
    $types = '';

    if ($search_term !== '') {
      $product_sql .= " AND (p.pro_name LIKE ? OR p.pro_description LIKE ? OR p.type LIKE ?)";
      $search_like = '%' . $search_term . '%';
      $params[] = $search_like;
      $params[] = $search_like;
      $params[] = $search_like;
      $types .= 'sss';
    }

    if ($type_filter !== '') {
      $product_sql .= " AND p.type = ?";
      $params[] = $type_filter;
      $types .= 's';
    }

    $product_sql .= " GROUP BY p.pro_id ORDER BY p.created_on DESC, p.pro_name ASC";
    $product_stmt = $conn->prepare($product_sql);
    if (!empty($params)) {
      $product_stmt->bind_param($types, ...$params);
    }
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    $products = [];
    $available_types = [];
    while ($row = $product_result->fetch_assoc()) {
      $products[] = $row;
      if (!empty($row['type']) && !in_array($row['type'], $available_types, true)) {
        $available_types[] = $row['type'];
      }
    }
    $product_stmt->close();

    $cart_stmt = $conn->prepare("SELECT uc.cart_id, uc.pro_id, uc.pro_qty, p.pro_name, p.pro_image, COALESCE(pi.pro_price, 0) AS pro_price FROM user_cart uc INNER JOIN products p ON p.pro_id = uc.pro_id LEFT JOIN pro_inventory pi ON pi.pro_id = p.pro_id AND pi.is_active = 'Y' WHERE uc.user_id = ? AND uc.is_active = 'Y' ORDER BY uc.created_on DESC");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart_items = [];
    $cart_count = 0;
    $cart_total = 0;
    while ($row = $cart_result->fetch_assoc()) {
      $row['line_total'] = safe_price_to_float($row['pro_price']) * intval($row['pro_qty']);
      $cart_total += $row['line_total'];
      $cart_count += intval($row['pro_qty']);
      $cart_items[] = $row;
    }
    $cart_stmt->close();

    $order_stmt = $conn->prepare("SELECT COUNT(*) AS total_orders FROM purchase_product WHERE user_id = ?");
    $order_stmt->bind_param("i", $user_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $total_orders = 0;
    if ($row = $order_result->fetch_assoc()) {
      $total_orders = intval($row['total_orders']);
    }
    $order_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<base href="../">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crop Market</title>
  <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="assets/dist/css/custom.css">
  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <?php include_once('_header.php'); ?>
  <?php include_once('_sidebar.php'); ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Crop Market</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="farmer/dashboard">Home</a></li>
              <li class="breadcrumb-item active">Crop Market</li>
            </ol>
          </div>
          
        </div>
      </div>
    </div>
	<section class="content">
      <div class="container-fluid">

    <?php if (!empty($status_message)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($status_message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>

    <div class="row mb-3">
      <div class="col-md-3">
        <div class="small-box bg-info">
          <div class="inner"><h3><?php echo count($products); ?></h3><p>Available Products</p></div>
          <div class="icon"><i class="fas fa-store"></i></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="small-box bg-success">
          <div class="inner"><h3><?php echo $cart_count; ?></h3><p>Items in Cart</p></div>
          <div class="icon"><i class="fas fa-shopping-cart"></i></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="small-box bg-warning">
          <div class="inner"><h3>₹<?php echo number_format($cart_total, 2); ?></h3><p>Cart Total</p></div>
          <div class="icon"><i class="fas fa-rupee-sign"></i></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="small-box bg-danger">
          <div class="inner"><h3><?php echo $total_orders; ?></h3><p>Orders Placed</p></div>
          <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" class="row align-items-end">
          <div class="col-md-6 mb-2">
            <label>Search products</label>
            <input type="text" name="search" class="form-control" placeholder="Search by product, description, or type" value="<?php echo htmlspecialchars($search_term); ?>">
          </div>
          <div class="col-md-4 mb-2">
            <label>Filter by type</label>
            <select name="type" class="form-control">
              <option value="">All Types</option>
              <?php foreach ($available_types as $type_name): ?>
                <option value="<?php echo htmlspecialchars($type_name); ?>" <?php echo $type_filter === $type_name ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($type_name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2 mb-2">
            <button type="submit" class="btn btn-primary btn-block">Search</button>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8 mb-4">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Live Product Listings</h3>
            <span class="badge badge-primary"><?php echo count($products); ?> products</span>
          </div>
          <div class="card-body">
            <div class="row">
              <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                  <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                      <img src="<?php echo !empty($product['pro_image']) ? htmlspecialchars($product['pro_image']) : 'assets/dist/img/photo1.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['pro_name']); ?>" style="height: 200px; object-fit: cover;" onerror="this.src='assets/dist/img/photo1.png'">
                      <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <h5 class="card-title mb-0"><?php echo htmlspecialchars($product['pro_name']); ?></h5>
                          <span class="badge badge-info"><?php echo htmlspecialchars($product['type'] ?? 'Product'); ?></span>
                        </div>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['pro_description'] ?? ''); ?></p>
                        <div class="mb-2">
                          <strong>Price:</strong> ₹<?php echo number_format(safe_price_to_float($product['pro_price']), 2); ?><br>
                          <strong>Stock:</strong> <?php echo intval($product['pro_qty']); ?><br>
                          <strong>Rating:</strong> <?php echo number_format((float) $product['avg_rating'], 1); ?> (<?php echo intval($product['rating_count']); ?>)
                        </div>
                        <p class="small text-muted flex-grow-1"><?php echo htmlspecialchars($product['pro_uses'] ?? ''); ?></p>
                        <form method="POST" class="mt-auto">
                          <input type="hidden" name="action" value="add_to_cart">
                          <input type="hidden" name="pro_id" value="<?php echo intval($product['pro_id']); ?>">
                          <div class="form-row align-items-end">
                            <div class="col-5">
                              <label class="small">Qty</label>
                              <input type="number" name="quantity" class="form-control form-control-sm" min="1" max="<?php echo max(1, intval($product['pro_qty'])); ?>" value="1">
                            </div>
                            <div class="col-7">
                              <button type="submit" class="btn btn-success btn-sm btn-block" <?php echo intval($product['pro_qty']) <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i> Add to Cart
                              </button>
                            </div>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="col-12">
                  <div class="alert alert-info mb-0">No products found.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 mb-4">
        <div class="card sticky-top" style="top: 1rem; z-index: 1;">
          <div class="card-header bg-success text-white">
            <h3 class="card-title mb-0"><i class="fas fa-shopping-cart"></i> Your Cart</h3>
          </div>
          <div class="card-body">
            <?php if (!empty($cart_items)): ?>
              <div class="table-responsive mb-3">
                <table class="table table-sm table-borderless align-middle">
                  <?php foreach ($cart_items as $item): ?>
                    <tr>
                      <td style="width: 70px;">
                        <img src="<?php echo !empty($item['pro_image']) ? htmlspecialchars($item['pro_image']) : 'assets/dist/img/photo1.png'; ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:8px;" onerror="this.src='assets/dist/img/photo1.png'">
                      </td>
                      <td>
                        <strong><?php echo htmlspecialchars($item['pro_name']); ?></strong><br>
                        <small>₹<?php echo number_format(safe_price_to_float($item['pro_price']), 2); ?> x <?php echo intval($item['pro_qty']); ?></small><br>
                        <form method="POST" class="d-flex mt-1" style="gap:6px;">
                          <input type="hidden" name="action" value="update_cart">
                          <input type="hidden" name="pro_id" value="<?php echo intval($item['pro_id']); ?>">
                          <input type="number" name="quantity" class="form-control form-control-sm" min="1" value="<?php echo intval($item['pro_qty']); ?>" style="width: 90px;">
                          <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                          <button type="submit" name="action" value="remove_from_cart" class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                      </td>
                      <td class="text-right">
                        <strong>₹<?php echo number_format($item['line_total'], 2); ?></strong>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </table>
              </div>
              <div class="border-top pt-3 mb-3">
                <div class="d-flex justify-content-between"><span>Subtotal</span><strong>₹<?php echo number_format($cart_total, 2); ?></strong></div>
                <div class="d-flex justify-content-between"><span>Delivery</span><strong>Free</strong></div>
                <div class="d-flex justify-content-between mt-2"><span>Total</span><strong>₹<?php echo number_format($cart_total, 2); ?></strong></div>
              </div>
              <form method="POST">
                <input type="hidden" name="action" value="checkout">
                <div class="form-group">
                  <label>Payment Method</label>
                  <select name="payment_method" class="form-control">
                    <option value="Cash on Delivery">Cash on Delivery</option>
                    <option value="UPI">UPI</option>
                    <option value="Card">Card</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-success btn-block">Place Order</button>
              </form>
            <?php else: ?>
              <div class="alert alert-light border mb-0">Your cart is empty. Add products to place an order.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
	 </div>
    </section>
  </div>
  
	


</div>
<script src="assets/plugins/jquery/jquery.min.js"></script>
<script src="assets/plugins/jquery-ui/jquery-ui.min.js"></script>
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/dist/js/adminlte.js"></script>
<link href="assets/plugins/custom/animate/animate.min.css" rel="stylesheet" type="text/css" />
<link href="assets/plugins/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/sweetalert2/sweetalert2.js"></script>
<link href="assets/plugins/custom/enlarge/jquery.fancybox.min.css" rel="stylesheet" type="text/css" />
<script src="assets/plugins/custom/enlarge/jquery.fancybox.min.js"></script>
<script src="assets/dist/js/custom.js"></script>

<script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="assets/plugins/jszip/jszip.min.js"></script>
<script src="assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>


<script>
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
<?php
	}
?>