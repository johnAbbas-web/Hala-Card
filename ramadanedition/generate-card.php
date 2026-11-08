<!DOCTYPE html>
<html>
<head>
    <title>Generating Your Card...</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .loading {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* The card that will be captured */
        #card-to-capture {
            width: 600px;
            height: 400px;
            margin: 20px auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
        }
        
        #card-to-capture::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
            transform: rotate(45deg);
        }
        
        .card-content {
            position: relative;
            z-index: 2;
            width: 80%;
        }
        
        .card-title {
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .card-name {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            word-wrap: break-word;
        }
        
        .card-footer {
            margin-top: 30px;
            font-size: 18px;
            opacity: 0.8;
        }
        
        .card-number {
            font-family: monospace;
            font-size: 20px;
            letter-spacing: 2px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid rgba(255,255,255,0.3);
        }
        
        /* Hidden form to send data back */
        .hidden-form {
            display: none;
        }
    </style>
    
    <!-- html2canvas library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
    <?php
    // Get data from URL
    $name = isset($_GET['name']) ? $_GET['name'] : 'Your Name';
    $email = isset($_GET['email']) ? $_GET['email'] : '';
    $timestamp = date('Y-m-d');
    $card_number = 'CARD-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    ?>
    
    <!-- Loading message -->
    <div class="loading" id="loading-message">
        <div class="spinner"></div>
        <h2>Generating your personalized card...</h2>
        <p>Please wait a moment</p>
    </div>
    
    <!-- The card to capture (hidden initially) -->
    <div id="card-to-capture" style="display: none;">
        <div class="card-content">
            <div class="card-title">Premium Membership Card</div>
            <div class="card-name"><?php echo htmlspecialchars($name); ?></div>
            <div class="card-footer">Member since <?php echo $timestamp; ?></div>
            <div class="card-number"><?php echo $card_number; ?></div>
        </div>
    </div>
    
    <!-- Hidden form to send data back to process.php -->
    <form id="data-form" class="hidden-form" action="process.php" method="POST">
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="hidden" name="card_image" id="card-image-data" value="">
        <input type="hidden" name="card_number" value="<?php echo $card_number; ?>">
    </form>
    
    <script>
    // Wait for everything to load
    window.onload = function() {
        // Show the card
        const card = document.getElementById('card-to-capture');
        card.style.display = 'flex';
        
        // Wait a tiny bit for rendering
        setTimeout(function() {
            // Capture the card
            html2canvas(card, {
                scale: 2, // Higher quality
                backgroundColor: null,
                allowTaint: true,
                useCORS: true,
                logging: false
            }).then(function(canvas) {
                // Convert to image data
                const imageData = canvas.toDataURL('image/png');
                
                // Put image data in hidden form
                document.getElementById('card-image-data').value = imageData;
                
                // Submit the form to process.php
                document.getElementById('data-form').submit();
            }).catch(function(error) {
                console.error('Error capturing card:', error);
                // If error, still submit without image
                document.getElementById('data-form').submit();
            });
        }, 500);
    };
    </script>
</body>
</html>