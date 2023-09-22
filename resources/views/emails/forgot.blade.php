<!DOCTYPE html>
<html>
<head>
    <style>
        /* Ajouter du style CSS pour améliorer la mise en page de l'e-mail */
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
    <h1>Demande de Changement de Mot de Passe</h1>
    <p>Bonjour,</p>
    <p>Vous avez demandé une réinitialisation de votre mot de passe. Veuillez cliquer sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
    <p><a href="{{ url('/') }}/reset/{{ $token }}" class="cta-button">Réinitialiser le mot de passe</a></p>
    <p>Si vous n'avez pas effectué cette demande de changement de mot de passe, veuillez ignorer cet e-mail.</p>
    <p>Si vous avez des questions ou avez besoin d'une assistance supplémentaire, n'hésitez pas à nous contacter.</p>
    <p>Cordialement,<br>L'équipe de {{ $appName }}</p>
    <p class="footer">Cet e-mail est généré automatiquement. Veuillez ne pas y répondre.</p>
</div>
</body>
</html>


