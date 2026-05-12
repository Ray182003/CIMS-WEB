<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Church Management System</title>
    <link href="<?php echo BASE_URL; ?>/public/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/fontawesome-local.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .access-denied-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            margin: 20px;
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            color: white;
        }
        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="error-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        
        <h1 class="error-title">Access Denied</h1>
        
        <?php if (isset($_SESSION['username'])): ?>
        <div class="user-info">
            <strong>Current User:</strong> <?php echo getUserDisplayName(); ?><br>
            <strong>Role:</strong> <?php echo getUserRole(); ?>
        </div>
        <?php endif; ?>
        
        <div class="error-message">
            You don't have permission to access this page or perform this action. 
            Please contact your system administrator if you believe this is an error.
        </div>
        
        <a href="<?php echo BASE_URL; ?>/dashboard/" class="btn-home">
            <i class="fas fa-home"></i> Back to Dashboard
        </a>
        
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                This incident has been logged for security purposes.
            </small>
        </div>
    </div>
</body>
</html>
