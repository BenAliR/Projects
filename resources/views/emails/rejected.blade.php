<!DOCTYPE html>
<html>
<head>
    <style>
        /* Add some CSS styling to improve the email layout */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.5;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #fff;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            max-width: 150px;
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
            text-align: center;
        }
        p {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }
        .cta-button {
            display: inline-block;
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #888;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="{{ asset('path/to/your/logo.png') }}" alt="YourApp Logo">
    </div>
    <h1>Demande d'inscription rejetée</h1>
    <p>Bonjour {!! $data['name'] !!},</p>
    <p>Nous regrettons de vous informer que votre demande d'inscription sur VotreApp a été rejetée.</p>
    <p>Nous avons pris en compte plusieurs facteurs lors de l'examen de votre demande, et malheureusement, nous ne pouvons pas approuver votre inscription pour le moment.</p>
    <p>Si vous avez des questions ou souhaitez obtenir des informations supplémentaires, n'hésitez pas à nous contacter. Nous serons heureux de vous aider.</p>
    <p>Cordialement,<br>L'équipe de ZenAcademy</p>
    <p class="footer">Cet email est généré automatiquement, merci de ne pas y répondre.</p>
</div>
</body>
</html>
