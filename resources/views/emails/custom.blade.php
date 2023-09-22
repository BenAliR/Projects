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
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        li {
            margin-bottom: 5px;
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
        <img src="{{ asset('/uploads/default/1.jpg') }}" alt="YourApp Logo">
    </div>
    <h1>Bienvenue sur ZenAcademy</h1>
    <p>Merci de vous être inscrit(e) !</p>
    <p>Voici les détails de votre compte administrateur :</p>
    <ul>
        <li><strong>Nom d'utilisateur:</strong>{!! $data['name'] !!}</li>
        <li><strong>Email:</strong> {!! $data['email'] !!}</li>
        <li><strong>Mot de passe:</strong> {!! $data['password'] !!}</li>
    </ul>
    <p>Pour commencer à utiliser ZenAcademy, cliquez sur le bouton ci-dessous :</p>
    <p>
        <a href="{{ url('/') }}/login" class="cta-button">Commencer</a>
    </p>
    <p>Si vous avez des questions, n'hésitez pas à contacter notre équipe de support.</p>
    <p>Cordialement,<br>L'équipe ZenAcademy</p>
</div>
</body>
</html>














