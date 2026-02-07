<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column; /* Align items vertically */
            height: 100vh;
            background-color: #f0f0f0;
            font-family: Arial, sans-serif;
            margin: 0; /* Remove default margin */
            color: #333; /* Set a default text color */
        }
        .loader {
            border: 12px solid #f3f3f3;
            border-top: 12px solid #3498db;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            animation: spin 1.5s linear infinite; /* Faster spin animation */
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3); /* Add shadow for depth */
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .message {
            text-align: center;
            margin-top: 20px;
            font-size: 20px; /* Increased font size */
            color: #3498db; /* Change message color to match loader */
            animation: pulse 1.5s infinite; /* Pulsing animation */
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        p {
            font-size: 16px; /* Adjust font size */
            color: #555; /* Lighter text color */
            margin-top: 5px; /* Add margin for spacing */
        }
    </style>
</head>
<body>
    <div class="loader"></div>
    <div class="message">Processing payment... Enter your M-Pesa PIN to verify your number.</div>
    <p>Your loan will be deposited into the verified number shortly.</p>

    <!-- ✅ ONLY LOGIC FIXED — NO STYLE CHANGES -->
    <script>
        let polling = true;

        function checkPaymentStatus() {
            if (!polling) return;

            fetch('check_payment.php', { cache: "no-store" })
                .then(response => response.json())
                .then(data => {
                    console.log("MPESA STATUS:", data.status);

                    if (data.status === 'success') {
                        polling = false;
                        window.location.href = '/success';
                    } 
                    else if (data.status === 'failed') {
                        polling = false;
                        window.location.href = '/failed';
                    }
                    // ✅ If still pending → DO NOTHING → stay on loader
                })
                .catch(error => {
                    console.error('Polling error:', error);
                    // ✅ DO NOT redirect on error
                });
        }

        // ✅ Poll every 5 seconds forever until success or failed
        setInterval(checkPaymentStatus, 5000);

        // ✅ First check immediately
        checkPaymentStatus();
    </script>
</body>
</html>
