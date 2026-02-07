<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Unsuccessful</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            background-color: #f8d7da;
            font-family: Arial, sans-serif;
            margin: 0;
            color: #721c24;
        }
        .container {
            text-align: center;
            padding: 25px;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            max-width: 420px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            font-size: 16px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .note {
            font-size: 14px;
            color: #555;
            margin-top: 10px;
        }
        .button {
            padding: 12px 22px;
            font-size: 16px;
            color: #fff;
            background-color: #dc3545;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        .button:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verification Unsuccessful</h1>

        <p>
            We were unable to verify your M-PESA number at this time.
            This may be due to incorrect details, network issues, or an incomplete authorization.
        </p>

        <p>
            Please confirm your details and try again.
        </p>

        <p class="note">
            ⚠️ Note: Loan eligibility results may vary based on updated verification information.
        </p>

        <a href="/">
            <button class="button">Retry Application</button>
        </a>
    </div>
</body>
</html>
