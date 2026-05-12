<?php
session_start();
include_once('../_functions.php');

if (!isset($_SESSION['user'])) {
    header('location:../login');
    exit;
}

check_role($_SESSION['user']['role'], basename(__DIR__));

global $conn;

$user_id = intval($_SESSION['user']['user_id']);
$message = '';
$message_type = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    // Validation
    if ($rating < 1 || $rating > 5) {
        $message = 'Please select a valid rating (1-5 stars)';
        $message_type = 'error';
    } elseif (strlen($comment) < 10) {
        $message = 'Comment must be at least 10 characters long';
        $message_type = 'error';
    } else {
        // Submit feedback to database
        $sql = "INSERT INTO feedback_reports (user_id, feedback_type, target_user_id, rating, comment, created_on, created_by) 
                VALUES (?, 'vendor', NULL, ?, ?, NOW(), ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iisi", $user_id, $rating, $comment, $user_id);
            if ($stmt->execute()) {
                $message = 'Thank you! Your feedback has been submitted successfully.';
                $message_type = 'success';
                // Clear form
                $_POST = [];
            } else {
                $message = 'Failed to submit feedback. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = 'Failed to submit feedback. Please try again.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Feedback - Vendor Dashboard</title>
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <link rel="stylesheet" href="assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="assets/dist/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
	
    <style>
        .star-rating {
            font-size: 2rem;
            letter-spacing: 0.5rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .feedback-card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            background: #fff;
        }
        .feedback-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }
    </style>
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
                        <h1 class="m-0"><i class="fas fa-comments"></i> Send Feedback</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="vendor/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Feedback</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <!-- Message Display -->
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Feedback Form -->
                        <div class="feedback-card">
                            <div class="feedback-header">
                                <h3 class="m-0"><i class="fas fa-star"></i> We Value Your Feedback</h3>
                                <p class="mt-2 mb-0">Help us improve by sharing your experience with our platform</p>
                            </div>

                            <form method="POST" action="">
                                <!-- Rating Section -->
                                <div class="form-group mt-4">
                                    <label for="rating" class="form-label font-weight-bold">Rate Your Experience *</label>
                                    <p class="text-muted small mb-3">How would you rate your overall experience?</p>
                                    <div class="star-rating" id="ratingContainer">
                                        <input type="radio" id="star5" name="rating" value="5" required>
                                        <label for="star5"><i class="fas fa-star"></i></label>

                                        <input type="radio" id="star4" name="rating" value="4">
                                        <label for="star4"><i class="fas fa-star"></i></label>

                                        <input type="radio" id="star3" name="rating" value="3">
                                        <label for="star3"><i class="fas fa-star"></i></label>

                                        <input type="radio" id="star2" name="rating" value="2">
                                        <label for="star2"><i class="fas fa-star"></i></label>

                                        <input type="radio" id="star1" name="rating" value="1">
                                        <label for="star1"><i class="fas fa-star"></i></label>
                                    </div>
                                    <div id="ratingText" class="mt-2 text-muted"></div>
                                </div>

                                <!-- Comment Section -->
                                <div class="form-group mt-4">
                                    <label for="comment" class="form-label font-weight-bold">Your Feedback *</label>
                                    <p class="text-muted small mb-2">Please share your thoughts, suggestions, or concerns (minimum 10 characters)</p>
                                    <textarea 
                                        class="form-control" 
                                        id="comment" 
                                        name="comment" 
                                        rows="6" 
                                        placeholder="Tell us what you think about our platform..."
                                        required
                                        minlength="10"><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted mt-2">
                                        <span id="charCount">0</span>/1000 characters
                                    </small>
                                </div>

                                <!-- Info Box -->
                                <div class="alert alert-info mt-4" role="alert">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Your Privacy:</strong> Your feedback is important to us and will be reviewed by our admin team to help improve the platform.
                                </div>

                                <!-- Buttons -->
                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Submit Feedback
                                    </button>
                                    <a href="vendor/dashboard" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- FAQ Section -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="m-0"><i class="fas fa-question-circle"></i> FAQ</h5>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="faqAccordion">
                                    <div class="card">
                                        <div class="card-header" id="headingOne">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseOne">
                                                    <i class="fas fa-question"></i> What will happen to my feedback?
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapseOne" class="collapse" data-parent="#faqAccordion">
                                            <div class="card-body">
                                                Your feedback will be reviewed by our admin team and used to improve our platform and services.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header" id="headingTwo">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseTwo">
                                                    <i class="fas fa-question"></i> Can I modify my feedback?
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapseTwo" class="collapse" data-parent="#faqAccordion">
                                            <div class="card-body">
                                                Once submitted, feedback cannot be modified. However, you can submit additional feedback anytime.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header" id="headingThree">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapseThree">
                                                    <i class="fas fa-question"></i> Is my feedback anonymous?
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapseThree" class="collapse" data-parent="#faqAccordion">
                                            <div class="card-body">
                                                Your feedback is associated with your account but only shared with the admin team for review purposes.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- jQuery -->
<script src="assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap JS -->
<script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="assets/dist/js/adminlte.min.js"></script>

<script>
    // Update rating text and character count
    document.querySelectorAll('input[name="rating"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const ratingTexts = {
                '1': '😞 Poor',
                '2': '😐 Fair',
                '3': '🙂 Good',
                '4': '😊 Very Good',
                '5': '😍 Excellent'
            };
            document.getElementById('ratingText').textContent = ratingTexts[this.value] || '';
        });
    });

    // Character counter
    document.getElementById('comment').addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
        if (this.value.length > 1000) {
            this.value = this.value.substring(0, 1000);
            document.getElementById('charCount').textContent = '1000';
        }
    });
<?php include_once('../_chat_widget.php'); ?>
</body>
</html>
