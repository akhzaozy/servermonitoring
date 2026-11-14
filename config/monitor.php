<?php

return [

    /*
    | Nama server tempat dashboard ini berjalan (server "lokal").
    */
    'server_name' => env('MONITOR_SERVER_NAME', 'Server 1'),

    // Override hostname (berguna di Docker, karena hostname container acak).
    'hostname' => env('MONITOR_HOSTNAME'),

    // Root filesystem host. Di Docker, mount host "/" ke /host (read-only) lalu isi MONITOR_HOST_ROOT=/host.
    'host_root' => env('MONITOR_HOST_ROOT', '/'),

    // Link Portainer untuk server lokal (opsional).
    'portainer_url' => env('PORTAINER_URL'),

    /*
    | Agent: dipakai ketika instance ini dimonitor oleh dashboard lain.
    | Dashboard utama memanggil /api/agent/* dengan header "Authorization: Bearer <AGENT_TOKEN>".
    */
    'agent' => [
        'token' => env('AGENT_TOKEN'),
        'allow_exec' => (bool) env('AGENT_ALLOW_EXEC', false),
    ],

    /*
    | Terminal web. Jika TERMINAL_SSH_HOST diisi, perintah dijalankan lewat SSH ke host tersebut
    | (berguna agar container Docker bisa mengakses shell host server).
    */
    'terminal' => [
        'enabled' => (bool) env('TERMINAL_ENABLED', true),
        'timeout' => (int) env('TERMINAL_TIMEOUT', 30),
        'max_output' => (int) env('TERMINAL_MAX_OUTPUT', 262144),
        'ssh' => [
            'host' => env('TERMINAL_SSH_HOST'),
            'port' => (int) env('TERMINAL_SSH_PORT', 22),
            'user' => env('TERMINAL_SSH_USER'),
            'key' => env('TERMINAL_SSH_KEY'),
        ],
    ],

    // Akun admin awal (dipakai `php artisan monitor:user --from-env`).
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
