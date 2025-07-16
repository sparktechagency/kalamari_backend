<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>OTP Email Template</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <link rel="stylesheet" href="/style.css">
  <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        padding: 0;
        margin: 0;
      }
      .container-sec {
        background-color: white;
        border: 1px solid white;
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        max-width: 600px;
      }
      .otp-code {
        font-size: 24px;
        font-weight: bold;
        background-color: #f8f9fa;
        padding: 15px;
        text-align: center;
        border-radius: 8px;
        border: 1px dashed #ED6237;
        color: #ED6237;
      }
      .btn-verify {
        display: inline-block;
        padding: 10px 20px;
        color: #ffffff;
        background-color: #007bff;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
      }
      .footer-text {
        color: #6c757d;
        font-size: 14px;
        text-align: center;
        margin-top: 20px;
      }
      .footer-text a {
        color: #ED6237;
        text-decoration: none;
      }
      .otp-lock {
        color: #333;
        font-size: 80px;
      }
      .welcome-section {
        background: #ED6237;
        padding: 30px;
        border-radius: 4px;
        color: #fff;
        font-size: 20px;
        margin: 20px 0px;
      }
      .welcome-text {
        font-family: monospace;
      }
      .app-name {
        font-size: 25px;
        font-weight: 800;
        margin: 7px 0px;
      }
      .verify-text {
        margin-top: 25px;
        font-size: 25px;
        letter-spacing: 3px;
      }
      i.fas.fa-envelope-open {
        font-size: 35px !important;
        color: #ffffff;
      }

  </style>
</head>

<body>
  <div class="container-sec">
    <div class="text-center">
      <div><i class="fas fa-lock otp-lock"></i></div>
      <div class="welcome-section">
        <div class="app-name">
          Secure Your Account
        </div>
      </div>
      <h2>Hello, {{ $otp['userName'] }}</h2>
      <p>Your One-Time Password (OTP) for verification is:</p>
      <div class="otp-code">{{ $otp['otp'] }}</div>
      <p class="mt-4">Please using this OTP to complete your verification. The OTP is valid for the next {{ $otp['validity'] }}.</p>
    </div>
    <div class="footer-text">
      <p>If you did not request this OTP, please <a href="#">contact us</a> immediately.</p>
      <p>Thank you,<br>The Kalamari Developer Team</p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>
