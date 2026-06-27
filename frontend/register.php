<?php
include '../backend/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_type = $_POST['account_type'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? ''; // Add phone field
    
    // Validation
    if (empty($email) || empty($password) || empty($phone)) {
        $error = 'Email, phone, and password are required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($account_type == 'customer') {
                $full_name = $_POST['full_name'] ?? '';
                
                if (empty($full_name)) {
                    $error = 'Full name is required.';
                } else {
                    // Start transaction
                    $conn->begin_transaction();
                    
                    try {
                        // 1. Insert into users table
                        $insert_user = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                        $role = 'customer';
                        $insert_user->bind_param("sss", $email, $hashed_password, $role);
                        
                        if (!$insert_user->execute()) {
                            throw new Exception('Error creating user account: ' . $conn->error);
                        }
                        
                        $user_id = $conn->insert_id;
                        
                        // 2. Insert into customers table
                        $insert_customer = $conn->prepare("INSERT INTO customers (user_id, full_name, phone) VALUES (?, ?, ?)");
                        $insert_customer->bind_param("iss", $user_id, $full_name, $phone);
                        
                        if (!$insert_customer->execute()) {
                            throw new Exception('Error creating customer profile: ' . $conn->error);
                        }
                        
                        $conn->commit();
                        $success = 'Customer account created! Redirecting...';
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = $e->getMessage();
                    }
                }
            } else if ($account_type == 'caterer') {
                $business_name = $_POST['business_name'] ?? '';
                
                if (empty($business_name)) {
                    $error = 'Business name is required.';
                } else {
                    // Start transaction for caterer registration
                    $conn->begin_transaction();
                    
                    try {
                        // 1. Insert into users table
                        $insert_user = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                        $role = 'caterer';
                        $insert_user->bind_param("sss", $email, $hashed_password, $role);
                        
                        if (!$insert_user->execute()) {
                            throw new Exception('Error creating user account: ' . $conn->error);
                        }
                        
                        $user_id = $conn->insert_id;
                        
                            // 2. Insert into caterers table
                        $business_permit = ''; // no permit uploaded yet
                        $insert_caterer = $conn->prepare("INSERT INTO caterers (user_id, business_name, business_permit, phone) VALUES (?, ?, ?, ?)");
                        $insert_caterer->bind_param("isss", $user_id, $business_name, $business_permit, $phone);
                        
                        if (!$insert_caterer->execute()) {
                            throw new Exception('Error creating caterer profile: ' . $conn->error);
                        }
                        
                        $conn->commit();
                        $success = 'Caterer account created! Redirecting...';
                        
                    } catch (Exception $e) {
                        $conn->rollback();
                        $error = $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - CaterAI</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }

        .register-header {
            padding: 40px 40px 20px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .tabs-container {
            display: flex;
            border-bottom: 2px solid #e8e8e8;
        }

        .tab-btn {
            flex: 1;
            padding: 16px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #999;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-btn:hover {
            color: #667eea;
        }

        .tab-btn.active {
            color: #667eea;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .form-content {
            padding: 40px;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .description {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input::placeholder {
            color: #bbb;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .signin-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 14px;
        }

        .signin-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .signin-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        @media (max-width: 480px) {
            .register-container {
                border-radius: 12px;
            }

            .register-header {
                padding: 30px 20px 15px;
            }

            .register-header h1 {
                font-size: 26px;
            }

            .form-content {
                padding: 25px;
            }

            .tab-btn {
                font-size: 14px;
                padding: 14px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
        </div>

        <div class="tabs-container">
            <button class="tab-btn active" onclick="switchTab('customer', event)">Customer</button>
            <button class="tab-btn" onclick="switchTab('caterer', event)">Caterer</button>
        </div>

        <div class="form-content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <script>
                    setTimeout(() => window.location.href = 'index.php', 2000);
                </script>
            <?php endif; ?>

            <!-- Customer Tab -->
            <div id="customer" class="tab-content active">
                <p class="description">Create a customer account to browse packages and book catering services.</p>
                <form method="POST">
                    <input type="hidden" name="account_type" value="customer">
                    <div class="form-group">
                        <label for="customer_name">Full Name</label>
                        <input 
                            type="text" 
                            id="customer_name" 
                            name="full_name" 
                            placeholder="John Doe" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="customer_email">Email</label>
                        <input 
                            type="email" 
                            id="customer_email" 
                            name="email" 
                            placeholder="you@example.com" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Phone Number</label>
                        <input 
                            type="tel" 
                            id="customer_phone" 
                            name="phone" 
                            placeholder="09XXXXXXXXX" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="customer_password">Password</label>
                        <input 
                            type="password" 
                            id="customer_password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
            </div>

            <!-- Caterer Tab -->
            <div id="caterer" class="tab-content">
                <p class="description">Register your catering business. You'll need to submit documents for verification.</p>
                <form method="POST">
                    <input type="hidden" name="account_type" value="caterer">
                    <div class="form-group">
                        <label for="business_name">Business Name</label>
                        <input 
                            type="text" 
                            id="business_name" 
                            name="business_name" 
                            placeholder="ABC Catering Services" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="caterer_email">Email</label>
                        <input 
                            type="email" 
                            id="caterer_email" 
                            name="email" 
                            placeholder="you@example.com" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="caterer_phone">Phone Number</label>
                        <input 
                            type="tel" 
                            id="caterer_phone" 
                            name="phone" 
                            placeholder="09XXXXXXXXX" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="caterer_password">Password</label>
                        <input 
                            type="password" 
                            id="caterer_password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit">Create Account</button>
                </form>
            </div>

            <div class="signin-link">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName, event) {
            event.preventDefault();

            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all buttons
            const tabBtns = document.querySelectorAll('.tab-btn');
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab content
            const selectedTab = document.getElementById(tabName);
            if (selectedTab) {
                selectedTab.classList.add('active');
            }

            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
