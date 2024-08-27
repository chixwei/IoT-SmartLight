<?php
include("connection.php");
$email = $password ="";

session_start();

// Check if the username session variable is set
if (isset($_SESSION['username'])) {
    // User is already logged in, redirect to dashboard or authorized page
    header("Location: streetlight.php");
    exit();
}

// Check if the login form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql= "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result= mysqli_query($conn, $sql);

    if (mysqli_num_rows($result)>0) {
        $row = mysqli_fetch_assoc($result);
        $email = $row['email'];
        // Set a session to mark the user as logged in
        session_start();
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        header("Location: streetlight.php"); // Redirect to the dashboard page after successful login
        exit();
    } else {
        $error = "Invalid username or password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
            flex-direction: column;
        }

        .card {
            width: 300px;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
            text-align: center;
            background-color: white;
        }

        .logo {
            display: flex;
            align-items: center;
            margin-top: 2%;
            margin-bottom: 8%;
            
        }

        .logo img{
            width: 100px;

        }

        .logo h1 {
            padding: 20px;
            font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            font-size: 35px;
            
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group input[type="text"],
        .input-group input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 20px;
        }

        .input-group input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            border-radius: 50px;
            font-weight: bold;
            font-family:'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif;
            font-size: 18px;
        }

        .input-group input[type="submit"]:hover {
            background-color: #45a049;
        }


        .card h2 {
            font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
            color: #003366;
        }
    </style>
</head>
<body>
    <div class="logo">
        <img src="img/logo.png">
        <h1>SmartLight</h1>
    </div>
    <div class="card">
        <h2>Sign In</h2>
        <!-- $_SERVER["PHP_SELF"] to refer to the current script.  -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="input-group">
                <input type="text" id="username" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="input-group">
                <input type="submit" value="Login">
            </div>
        </form>

        <!-- show error message -->
        <?php if (!empty($error)) : ?>
            <div style="color: red;"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>

    
</body>
</html>