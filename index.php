<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Updated: send JSON POST to motor control endpoint when button is clicked
    $url = 'http://172.220.5.210:8080/move';
    $data = [
        'steps' => 200,
        'dir'   => 'ccw',
        'speed' => 80
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $result = 'Connection Error: ' . curl_error($ch);
        $result_type = 'error';
    } elseif ($httpCode < 200 || $httpCode >= 300) {
        $result = 'HTTP Error ' . $httpCode . ': ' . htmlspecialchars($response);
        $result_type = 'error';
    } else {
        // Try to decode JSON response
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            $result = 'Move command sent. Response: ' . htmlspecialchars(json_encode($decoded));
        } else {
            $result = 'Move command sent. Raw response: ' . htmlspecialchars($response);
        }
        $result_type = 'success';
    }
    curl_close($ch);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title>House Key - Smart Door Control</title>
    <style>
        /* Prevent iOS auto zoom adjustments */
        html { -webkit-text-size-adjust: 100%; }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;

            /* Also help suppress accidental zoom */
            touch-action: manipulation;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(120, 119, 198, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        .container {
            text-align: center;
            z-index: 10;
            position: relative;
        }

        .keypad-container {
            background: linear-gradient(145deg, #e8e8e8, #f5f5f5);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 
                20px 20px 60px rgba(0, 0, 0, 0.3),
                -20px -20px 60px rgba(255, 255, 255, 0.1);
            max-width: 400px;
            margin: 0 auto;
        }

        .keypad-display {
            background: #2c3e50;
            color: #00ff88;
            font-family: 'Courier New', monospace;
            font-size: 1.5rem;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            border: 2px inset #555;
            min-height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            letter-spacing: 0.5rem;
            text-shadow: 0 0 10px #00ff88;
        }

        .keypad-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .keypad-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(145deg, #f0f0f0, #d0d0d0);
            border: none;
            box-shadow: 
                5px 5px 15px rgba(0, 0, 0, 0.2),
                -5px -5px 15px rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            cursor: pointer;
            transition: all 0.1s ease;
            user-select: none;

            /* Prevent double-tap zoom and improve tap response */
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        .keypad-button:hover {
            transform: scale(0.95);
            box-shadow: 
                3px 3px 10px rgba(0, 0, 0, 0.2),
                -3px -3px 10px rgba(255, 255, 255, 0.8);
        }

        .keypad-button:active {
            transform: scale(0.9);
            box-shadow: 
                inset 3px 3px 10px rgba(0, 0, 0, 0.2),
                inset -3px -3px 10px rgba(255, 255, 255, 0.8);
        }

        .keypad-button.clear {
            background: linear-gradient(145deg, #ff6b6b, #e55555);
            color: white;
        }

        .keypad-button.enter {
            background: linear-gradient(145deg, #51cf66, #40c057);
            color: white;
        }

        .access-denied {
            color: #ff4757;
            margin-top: 1rem;
            font-weight: 600;
            animation: shake 0.5s ease-in-out;
        }

        .access-granted {
            color: #2ed573;
            margin-top: 1rem;
            font-weight: 600;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .hidden {
            display: none;
        }

        .title {
            color: white;
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            letter-spacing: 2px;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            margin-bottom: 3rem;
            font-weight: 300;
        }

        .door-button-container {
            position: relative;
            display: inline-block;
        }

        .door-button {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            border: none;
            box-shadow: 
                20px 20px 60px rgba(0, 0, 0, 0.3),
                -20px -20px 60px rgba(255, 255, 255, 0.1);
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;

            /* Prevent double-tap zoom and improve tap response */
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        .door-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: linear-gradient(145deg, #4facfe, #00f2fe);
            transition: all 0.6s ease;
            transform: translate(-50%, -50%);
            z-index: 0;
        }

        .door-button:hover::before {
            width: 100%;
            height: 100%;
        }

        .door-button:hover {
            transform: scale(1.05);
            box-shadow: 
                25px 25px 80px rgba(0, 0, 0, 0.4),
                -25px -25px 80px rgba(255, 255, 255, 0.15);
            color: white;
        }

        .door-button:active {
            transform: scale(0.95);
        }

        .button-text {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .door-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .status-message {
            margin-top: 2rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 500;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s ease-out;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .status-message.error {
            background: rgba(255, 99, 71, 0.2);
            border-color: rgba(255, 99, 71, 0.3);
        }

        .status-message.success {
            background: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .title {
                font-size: 2rem;
            }
            
            .door-button {
                width: 150px;
                height: 150px;
                font-size: 1rem;
            }
            
            .door-icon {
                font-size: 2rem;
            }
            
            .status-message {
                margin: 2rem 1rem 0;
                padding: 0.8rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Keypad Entry Screen -->
        <div id="keypadScreen" class="keypad-container">
            <div class="keypad-display" id="display">Enter Code</div>
            
            <div class="keypad-grid">
                <button class="keypad-button" onclick="addDigit('1')">1</button>
                <button class="keypad-button" onclick="addDigit('2')">2</button>
                <button class="keypad-button" onclick="addDigit('3')">3</button>
                <button class="keypad-button" onclick="addDigit('4')">4</button>
                <button class="keypad-button" onclick="addDigit('5')">5</button>
                
                <button class="keypad-button" onclick="addDigit('6')">6</button>
                <button class="keypad-button" onclick="addDigit('7')">7</button>
                <button class="keypad-button" onclick="addDigit('8')">8</button>
                <button class="keypad-button" onclick="addDigit('9')">9</button>
                <button class="keypad-button" onclick="addDigit('0')">0</button>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button class="keypad-button clear" onclick="clearCode()">CLR</button>
                <button class="keypad-button enter" onclick="checkCode()">ENT</button>
            </div>
            
            <div id="message"></div>
        </div>

        <!-- Door Control Screen (Hidden Initially) -->
        <div id="doorScreen" class="hidden">
            <div class="door-button-container">
                <form method="post">
                    <button type="submit" class="door-button">
                        <div class="button-text">
                            <div class="door-icon">🚪</div>
                            <div>Open Front Door</div>
                        </div>
                    </button>
                </form>
            </div>

            <?php if (!empty($result)): ?>
                <div class="status-message <?php echo $result_type; ?>">
                    <strong><?php echo $result; ?></strong>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentCode = '';
        const correctCode = '2331';

        function addDigit(digit) {
            if (currentCode.length < 6) {
                currentCode += digit;
                updateDisplay();
            }
        }

        function clearCode() {
            currentCode = '';
            updateDisplay();
            document.getElementById('message').innerHTML = '';
        }

        function updateDisplay() {
            const display = document.getElementById('display');
            if (currentCode.length === 0) {
                display.textContent = 'Enter Code';
            } else {
                display.textContent = '*'.repeat(currentCode.length);
            }
        }

        function checkCode() {
            const messageDiv = document.getElementById('message');
            
            if (currentCode === correctCode) {
                messageDiv.innerHTML = '<div class="access-granted">Access Granted</div>';
                
                setTimeout(() => {
                    document.getElementById('keypadScreen').classList.add('hidden');
                    document.getElementById('doorScreen').classList.remove('hidden');
                    
                    // Add interactive effects to door button
                    const doorButton = document.querySelector('.door-button');
                    if (doorButton) {
                        doorButton.addEventListener('mouseenter', function() {
                            this.style.boxShadow = '25px 25px 80px rgba(0, 0, 0, 0.4), -25px -25px 80px rgba(255, 255, 255, 0.15), 0 0 50px rgba(79, 172, 254, 0.3)';
                        });

                        doorButton.addEventListener('mouseleave', function() {
                            this.style.boxShadow = '20px 20px 60px rgba(0, 0, 0, 0.3), -20px -20px 60px rgba(255, 255, 255, 0.1)';
                        });
                    }
                }, 1000);
            } else {
                messageDiv.innerHTML = '<div class="access-denied">Access Denied</div>';
                currentCode = '';
                
                setTimeout(() => {
                    updateDisplay();
                    messageDiv.innerHTML = '';
                }, 2000);
            }
        }



        // Keyboard support
        document.addEventListener('keydown', function(event) {
            if (document.getElementById('keypadScreen').classList.contains('hidden')) {
                return;
            }
            
            const key = event.key;
            
            if (key >= '0' && key <= '9') {
                addDigit(key);
            } else if (key === 'Enter') {
                checkCode();
            } else if (key === 'Escape' || key === 'Backspace') {
                clearCode();
            }
        });

        // Auto-hide status messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const statusMessage = document.querySelector('.status-message');
            if (statusMessage) {
                setTimeout(() => {
                    statusMessage.style.opacity = '0';
                    statusMessage.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        statusMessage.style.display = 'none';
                    }, 300);
                }, 5000);
            }
        });
    </script>
</body>
</html>
