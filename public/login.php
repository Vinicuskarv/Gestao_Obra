<?php
session_start();
require_once '../src/Auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new Auth();
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($auth->login($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Login</title>
    <style>
        .login-container{
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100%;
            max-width: 400px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: linear-gradient(to right, #2c2c2d57, #38383847) !important;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-container h2{
            text-align: center;
            margin-bottom: 20px;
        }
        .login-container input{
            width: auto;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        .login-container button{
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .login-container button:hover{
            background-color: #218838;
        }
        .error{
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }
        .login-container form{
            display: flex;
            flex-direction: column;
            box-shadow: none !important;
            background: transparent !important;
            border: none !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            or 
            <a href="funcionarioLogin.php" style="margin-top: 10px; text-align: center; display: block; color: #007bff; text-decoration: none;">Login Funcionário</a>
        </form>
    </div>
</body>
</html>