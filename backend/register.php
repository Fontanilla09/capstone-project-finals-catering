<?php
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $account_type = $_POST['account_type'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($account_type == 'customer') {
                $full_name = $_POST['full_name'] ?? '';
                
                if (empty($full_name)) {
                    $error = 'Full name is required.';
                } else {
                    $insert = $conn->prepare("INSERT INTO users (full_name, email, password, user_type) VALUES (?, ?, ?, ?)");
                    $user_type = 'customer';
                    $insert->bind_param("ssss", $full_name, $email, $hashed_password, $user_type);
                    
                    if ($insert->execute()) {
                        $success = 'Account created successfully! You can now login.';
                    } else {
                        $error = 'Error creating account. Please try again.';
                    }
                }
            } else if ($account_type == 'caterer') {
                $business_name = $_POST['business_name'] ?? '';
                
                if (empty($business_name)) {
                    $error = 'Business name is required.';
                } else {
                    $insert = $conn->prepare("INSERT INTO users (business_name, email, password, user_type) VALUES (?, ?, ?, ?)");
                    $user_type = 'caterer';
                    $insert->bind_param("ssss", $business_name, $email, $hashed_password, $user_type);
                    
                    if ($insert->execute()) {
                        $success = 'Caterer account created successfully! You can now login.';
                    } else {
                        $error = 'Error creating account. Please try again.';
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
    <title>Register - CaterAI</title>
    <link rel="stylesheet" href="../frontend/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            font-size: 28px;
            color: #333;
            margin: 0;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            border: none;
            background: transparent;
            font-size: 16px;
            color: #666;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group input::placeholder {
            color: #999;
        }
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .submit-btn:active {
            transform: translateY(0);
        }
        .signin-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .signin-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .signin-link a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
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
        .description {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('customer')">Customer</button>
            <button class="tab" onclick="switchTab('caterer')">Caterer</button>
        </div>

        <!-- Customer Registration Form -->
        <div id="customer" class="tab-content active">
            <p class="description">Create a customer account to browse packages and book catering services.</p>
            <form method="POST">
                <input type="hidden" name="account_type" value="customer">
                
                <div class="form-group">
                    <label for="customer_name">Full Name</label>
                    <input type="text" id="customer_name" name="full_name" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="customer_email">Email</label>
                    <input type="email" id="customer_email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label for="customer_password">Password</label>
                    <input type="password" id="customer_password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="submit-btn">Create Account</button>
            </form>
        </div>

        <!-- Caterer Registration Form -->
        <div id="caterer" class="tab-content">
            <p class="description">Register your catering business. You'll need to submit documents for verification.</p>
            <form method="POST">
                <input type="hidden" name="account_type" value="caterer">
                
                <div class="form-group">
                    <label for="business_name">Business Name</label>
                    <input type="text" id="business_name" name="business_name" placeholder="ABC Catering Services" required>
                </div>

                <div class="form-group">
                    <label for="caterer_email">Email</label>
                    <input type="email" id="caterer_email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label for="caterer_password">Password</label>
                    <input type="password" id="caterer_password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="submit-btn">Create Account</button>
            </form>
        </div>

        <div class="signin-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });
            
            // Deactivate all tab buttons
            document.querySelectorAll('.tab').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tab).classList.add('active');
            
            // Activate selected tab button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
