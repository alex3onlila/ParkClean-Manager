<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Auth fallback configuration
|--------------------------------------------------------------------------
| Utilisé si la base de données est indisponible ou vide
| Les mots de passe doivent être hashés (password_hash)
|--------------------------------------------------------------------------
*/

return [
    'users' => [

        // 🔑 utilisateur admin
        'admin' => password_hash('admin123', PASSWORD_DEFAULT),

        // 👤 utilisateur simple
        'demo' => password_hash('demo123', PASSWORD_DEFAULT),

        // ✉️ exemple avec email comme clé
        'test@exemple.com' => password_hash('secret123', PASSWORD_DEFAULT),

        /*
        // Exemple avancé (si tu veux évoluer plus tard)
        'alex' => [
            'email'    => 'alex@exemple.com',
            'password' => password_hash('alex123', PASSWORD_DEFAULT),
            'role'     => 'admin'
        ],
        */
    ],
];
