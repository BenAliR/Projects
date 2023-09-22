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
        <img src="{{ asset('/uploads/default/1.jpg') }}" alt="YourApp Logo">
    </div>
    <h1>Mise à jour de l'adresse e-mail</h1>
    <p>Bonjour {!! $data['name'] !!},</p>
    <p>Nous vous informons que votre adresse e-mail a été mise à jour avec succès. Votre adresse e-mail est désormais {!! $data['email'] !!}}.</p>
    <p>Si vous n'avez pas effectué cette modification, veuillez nous contacter immédiatement.</p>
    <p>Si vous avez des questions ou avez besoin d'assistance supplémentaire, n'hésitez pas à nous contacter.</p>
    <p>Cordialement,<br>L'équipe de ZenAcademy</p>
    <p class="footer">Cet e-mail est généré automatiquement. Veuillez ne pas y répondre.</p>
</div>
</body>
</html>
