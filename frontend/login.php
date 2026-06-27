<?php
include '../backend/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_type = $_POST['account_type'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Prepare role mapping
        $role_map = [
            'customer' => 'customer',
            'caterer' => 'caterer',
            'admin' => 'admin'
        ];
        
        $role = $role_map[$account_type] ?? 'customer';
        
        // Query for user with specific role
        $query = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ? AND role = ?");
        $query->bind_param("ss", $email, $role);
        $query->execute();
        $result = $query->get_result();
        
        if ($result->num_rows == 0) {
            $error = 'Invalid email or password for ' . ucfirst($account_type) . '.';
        } else {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Store additional data based on role
                if ($user['role'] == 'customer') {
                    $customer = $conn->prepare("SELECT id, full_name FROM customers WHERE user_id = ?");
                    $customer->bind_param("i", $user['id']);
                    $customer->execute();
                    $cust_data = $customer->get_result()->fetch_assoc();
                    $_SESSION['customer_id'] = $cust_data['id'];
                    $_SESSION['full_name'] = $cust_data['full_name'];
                    
                    $success = 'Login successful! Redirecting to customer dashboard...';
                    header('refresh:2;url=dashboard/customer.php');
                    
                } else if ($user['role'] == 'caterer') {
                    $caterer = $conn->prepare("SELECT id, business_name, is_verified FROM caterers WHERE user_id = ?");
                    $caterer->bind_param("i", $user['id']);
                    $caterer->execute();
                    $cat_data = $caterer->get_result()->fetch_assoc();
                    $_SESSION['caterer_id'] = $cat_data['id'];
                    $_SESSION['business_name'] = $cat_data['business_name'];
                    $_SESSION['is_verified'] = $cat_data['is_verified'];

                    if ($cat_data['is_verified']) {
                        $success = 'Login successful! Redirecting to caterer dashboard...';
                        header('refresh:2;url=dashboard/caterer_dashboard.php');
                    } else {
                        $success = 'Login successful! Redirecting to caterer profile...';
                        header('refresh:2;url=dashboard/caterer.php');
                    }
                    
                } else if ($user['role'] == 'admin') {
                    $success = 'Login successful! Redirecting to admin dashboard...';
                    header('refresh:2;url=dashboard/admin.php');
                }
                
            } else {
                $error = 'Invalid email or password for ' . ucfirst($account_type) . '.';
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
    <title>Sign In - CaterAI</title>
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

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }

        .login-header {
            padding: 40px 40px 20px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: #666;
            font-size: 14px;
        }

        .tabs-container {
            display: flex;
            border-bottom: 2px solid #e8e8e8;
        }

        .tab-btn {
            flex: 1;
            padding: 16px 12px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 13px;
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

        .signup-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
            font-size: 14px;
        }

        .signup-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .signup-link a:hover {
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
            .login-container {
                border-radius: 12px;
            }

            .login-header {
                padding: 30px 20px 15px;
            }

            .login-header h1 {
                font-size: 26px;
            }

            .form-content {
                padding: 25px;
            }

            .tab-btn {
                font-size: 12px;
                padding: 14px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account to continue</p>
        </div>

        <div class="tabs-container">
            <button class="tab-btn active" onclick="switchTab('customer', event)">Customer</button>
            <button class="tab-btn" onclick="switchTab('caterer', event)">Caterer</button>
            <button class="tab-btn" onclick="switchTab('admin', event)">Super Admin</button>
        </div>

        <div class="form-content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Customer Tab -->
            <div id="customer" class="tab-content active">
                <form method="POST">
                    <input type="hidden" name="account_type" value="customer">
                    
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
                        <label for="customer_password">Password</label>
                        <input 
                            type="password" 
                            id="customer_password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit">Sign In</button>
                </form>
            </div>

            <!-- Caterer Tab -->
            <div id="caterer" class="tab-content">
                <form method="POST">
                    <input type="hidden" name="account_type" value="caterer">
                    
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
                        <label for="caterer_password">Password</label>
                        <input 
                            type="password" 
                            id="caterer_password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit">Sign In</button>
                </form>
            </div>

            <!-- Super Admin Tab -->
            <div id="admin" class="tab-content">
                <form method="POST">
                    <input type="hidden" name="account_type" value="admin">
                    
                    <div class="form-group">
                        <label for="admin_email">Email</label>
                        <input 
                            type="email" 
                            id="admin_email" 
                            name="email" 
                            placeholder="admin@example.com" 
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="admin_password">Password</label>
                        <input 
                            type="password" 
                            id="admin_password" 
                            name="password" 
                            placeholder="••••••••" 
                            required
                        >
                    </div>

                    <button type="submit" class="btn-submit">Sign In</button>
                </form>
            </div>

            <div class="signup-link">
                Don't have an account? <a href="register.php">Sign up</a>
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
