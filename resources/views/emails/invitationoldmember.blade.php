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
        .platform-signature {
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="{{ asset('path/to/your/logo.png') }}" alt="YourApp Logo">
    </div>
    <h1>Invitation pour participer à un projet sur ZenAcademy</h1>
    <p>Bonjour,</p>
    <p>Je souhaite vous inviter à participer à notre projet sur ZenAcademy. Cette plateforme offre un environnement collaboratif pour travailler sur des projets et propose divers outils et fonctionnalités pour faciliter la communication et la gestion de projet.</p>
    <p>Nous croyons que vos compétences et votre expertise seraient des contributions précieuses à notre projet. Votre implication nous aidera à atteindre nos objectifs et à obtenir un résultat réussi.</p>
    <p>Pour accepter cette invitation et rejoindre notre projet, veuillez cliquer sur le lien ci-dessous :</p>
    <p>connectez-vous à votre espace client pour accepter l'invitation</p>
    <p>Si vous avez des questions ou avez besoin d'informations supplémentaires sur le projet ou la plateforme, n'hésitez pas à me contacter. Je suis disponible pour discuter de tous les détails et vous fournir des conseils.</p>
    <p>Nous espérons que vous accepterez cette invitation et nous nous réjouissons de collaborer avec vous sur ce projet passionnant.</p>
    <p>Cordialement,<br>{!! $data['name'] !!}</p>
    <div class="platform-signature">
        <p>Cette invitation est envoyée via ZenAcademy.</p>
        <p>Pour en savoir plus sur ZenAcademy, veuillez visiter <a href="https://www.platformwebsite.com">www.ZenAcademy.com</a>.</p>
    </div>
    <p class="footer">Cet e-mail est généré automatiquement. Veuillez ne pas y répondre.</p>
</div>
</body>
</html>
