<?php
// details.php

$zip_code = isset($_GET['zip_code']) ? urldecode($_GET['zip_code']) : 'No zip_code';
$title = isset($_GET['title']) ? urldecode($_GET['title']) : 'No Title';
$city = isset($_GET['city']) ? urldecode($_GET['city']) : 'No City';
$address = isset($_GET['address']) ? urldecode($_GET['address']) : 'No Address';
$img = isset($_GET['img']) ? urldecode($_GET['img']) : 'default-image-url.jpg';
$price = isset($_GET['price']) ? urldecode($_GET['price']) : 'No Price';
$residence_services = isset($_GET['residence_services']) ? json_decode(urldecode($_GET['residence_services']), true) : [];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Residence Details</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }
        .residence-details {
            width: 70%;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 40px;
            display: flex;
            flex-direction: column;
            position: relative; /* for positioning back button */
            overflow: hidden;
        }
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }
        h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px; /* Added margin for spacing */
        }
        .details {
            display: flex;
            justify-content: space-between;
            align-items: center; /* Aligns text and image vertically */
            margin-top: 20px;
            font-size: 16px;
        }
        .services {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 18px;
        }
        .no-services {
            font-style: italic;
            color: #666;
        }
        img {
            width: 350px; /* Adjusted size for visibility */
            height: auto;
            border-radius: 4px;
            margin-top: 30px;
            
        }
    </style>
</head>
<body>
    <div class="residence-details">
        <a href="http://localhost:8080/?page_id=5" class="back-button">Back</a>
        <img src="<?php echo htmlspecialchars($img); ?>" alt="Image of <?php echo htmlspecialchars($title); ?>">
        <h2><?php echo htmlspecialchars($title); ?></h2>
        <div class="details">
        <span><?php echo htmlspecialchars($address) . ', ' . htmlspecialchars($city) . ' ' . htmlspecialchars_decode($zip_code); ?></span>
            <span>Starting from: <?php echo htmlspecialchars($price); ?></span>
        </div>
        <div class="services">
            <h3>Services:</h3>
            <?php if (!empty($residence_services)): ?>
                <ul>
                    <?php foreach ($residence_services as $service): ?>
                        <li><?php echo htmlspecialchars($service); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-services">No services available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
