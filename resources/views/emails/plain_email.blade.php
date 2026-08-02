<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>{{ $subject }}</title>
  <style>
    /* @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500&display=swap'); */

    body {
      font-family: 'Quicksand', sans-serif;
      background-color: #fff8f6;
      margin: 0;
      padding: 20px;
    }

    .email-wrapper {
      max-width: 600px;
      margin: auto;
      background-color: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      animation: fadeIn 0.8s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        transform: translateY(15px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .email-header {
      background-color: #E53935;
      color: #ffffff;
      padding: 10px;
      text-align: center;
      border-bottom: 4px solid #FB8C00;
    }

    .email-header h2 {
      margin: 0;
      font-size: 24px;
      letter-spacing: 0.5px;
    }

    .email-body {
      padding: 25px;
      font-size: 15px;
      line-height: 1.7;
      color: #3e2723;
    }

    .email-body p {
      margin: 0 0 20px;
    }

    .email-footer {
      background-color: #fbe9e7;
      font-size: 13px;
      text-align: center;
      padding: 15px 20px;
      color: #5d4037;
      border-top: 1px solid #ffccbc;
    }

    .email-footer span {
      font-weight: bold;
      color: #E53935;
    }

    @media (max-width: 600px) {
      .email-header,
      .email-body,
      .email-footer {
        padding: 20px;
      }

      .email-header h2 {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="email-wrapper">
    <div class="email-header">
      <h2>{{ $subject }}</h2>
    </div>
    <div class="email-body">
  <p>{!! nl2br($messageBody) !!}</p>
    </div>
    <div class="email-footer">
      &copy; {{ date('Y') }} <span>BigPet</span>. All rights reserved.
    </div>
  </div>
</body>
</html>
