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
    <h1>Demande d'inscription acceptée</h1>
    <p>Bonjour {!! $data['name'] !!},</p>
    <p>Nous sommes ravis de vous informer que votre demande d'inscription sur VotreApp a été acceptée.</p>
    <p>Vous pouvez maintenant accéder à votre compte en utilisant les informations suivantes :</p>
    <ul>
        <li><strong>Nom d'utilisateur :</strong>{!! $data['name'] !!}</li>
        <li><strong>Email :</strong> {!! $data['email'] !!}</li>
        <li><strong>Mot de passe :</strong> {!! $data['password'] !!}</li>
    </ul>
    <p>Vous pouvez changer votre mot de passe à tout moment en accédant à la page de gestion de votre compte.</p>
    <p>Pour demander un changement de mot de passe, veuillez cliquer sur le lien ci-dessous :</p>
    <p><a href="{{ url('/') }}/reset-password/{!! $data['token'] !!}" class="cta-button">Changer le mot de passe</a></p>
    <p>Si vous avez des questions ou avez besoin d'aide, n'hésitez pas à nous contacter. Nous sommes là pour vous aider.</p>
    <p>Cordialement,<br>L'équipe de ZenAcademy</p>
    <p class="footer">Cet email est généré automatiquement, merci de ne pas y répondre.</p>
</div>
</body>
</html>
