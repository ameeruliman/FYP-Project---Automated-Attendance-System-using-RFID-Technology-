<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f9f9f9;
        }
        header {
            background: #4CAF50;
            color: white;
            padding: 10px;
            text-align: center;
        }
        footer {
            margin-top: 20px;
            text-align: center;
            color: #888;
        }
        .content {
            margin-top: 20px;
        }
        .button {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <header>
        <h1>About Us</h1>
    </header>
    <div class="content">
        <p>This page provides more information about our website.</p>
        <a href="index.php" class="button">Go Back</a>
    </div>
    <footer>
        <p>&copy; <?php echo date("Y"); ?> My Website</p>
    </footer>
</body>
</html>
