<?php

require_once __DIR__ . '/vendor/autoload.php';

use RedBeanPHP\R;

$hostname = '127.0.0.1';
$username = 'root';
$password = '';
$database = 'Binsta';

try {
    $pdo = new PDO(
        "mysql:host=$hostname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    $pdo->exec(
        sprintf(
            'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $database
        )
    );
} catch (PDOException $e) {
    fwrite(STDERR, 'Database connection failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

R::setup("mysql:host=$hostname;dbname=$database;charset=utf8mb4", $username, $password);
R::freeze(false);

R::exec('SET FOREIGN_KEY_CHECKS = 0');
R::wipe('comment');
R::wipe('postlike');
R::wipe('post');
R::wipe('user');
R::exec('SET FOREIGN_KEY_CHECKS = 1');

$users = [
    [
        'id' => 1,
        'username' => 'arne',
        'password' => 'geheim123',
        'name' => 'Arne Veltman',
        'bio' => 'Backend developer. Koffie, PHP en af en toe wat Rust.',
    ],
    [
        'id' => 2,
        'username' => 'lisa_codes',
        'password' => 'geheim123',
        'name' => 'Lisa de Vries',
        'bio' => 'Frontend developer @ een startup. React, CSS en veel te veel tabs open.',
    ],
    [
        'id' => 3,
        'username' => 'devsander',
        'password' => 'geheim123',
        'name' => 'Sander Bakker',
        'bio' => 'Studeert software engineering. Bezig met Python en machine learning.',
    ],
    [
        'id' => 4,
        'username' => 'julia.dev',
        'password' => 'geheim123',
        'name' => 'Julia Jansen',
        'bio' => '',
    ],
    [
        'id' => 5,
        'username' => 'mo_scripts',
        'password' => 'geheim123',
        'name' => 'Mo El Amrani',
        'bio' => 'Bash scripts en homelab tinkering. Alles moet automatisch.',
    ],
];

foreach ($users as $userData) {
    $user = R::load('user', (int) $userData['id']);
    $user->username = $userData['username'];
    $user->password = password_hash($userData['password'], PASSWORD_DEFAULT);
    $user->name = $userData['name'];
    $user->bio = $userData['bio'];
    R::store($user);
}

$posts = [
    [
        'id' => 1,
        'user_id' => 1,
        'language' => 'php',
        'theme' => 'dark',
        'caption' => 'Kleine helper die ik steeds opnieuw typ, dus nu maar ergens neergezet.',
        'code' => "function str_slug(string \$text): string\n{\n    \$text = strtolower(trim(\$text));\n    \$text = preg_replace('/[^a-z0-9]+/', '-', \$text);\n\n    return trim(\$text, '-');\n}",
        'created' => '2026-09-10 09:12:00',
    ],
    [
        'id' => 2,
        'user_id' => 2,
        'language' => 'javascript',
        'theme' => 'monokai',
        'caption' => 'Debounce zonder lodash erbij te hoeven slepen.',
        'code' => "function debounce(fn, delay) {\n  let timer;\n  return (...args) => {\n    clearTimeout(timer);\n    timer = setTimeout(() => fn(...args), delay);\n  };\n}",
        'created' => '2026-09-11 14:45:00',
    ],
    [
        'id' => 3,
        'user_id' => 3,
        'language' => 'python',
        'theme' => 'dracula',
        'caption' => 'Eerste keer dat ik een decorator zelf schrijf, voelt als toveren.',
        'code' => <<<'PYTHON'
            import time

            def timed(fn):
                def wrapper(*args, **kwargs):
                    start = time.time()
                    result = fn(*args, **kwargs)
                    print(f"{fn.__name__} took {time.time() - start:.4f}s")
                    return result
                return wrapper
            PYTHON,
        'created' => '2026-09-12 10:05:00',
    ],
    [
        'id' => 4,
        'user_id' => 5,
        'language' => 'bash',
        'theme' => 'solarized',
        'caption' => 'Backupje voor mijn homelab, draait elke nacht via cron.',
        'code' => <<<'BASH'
            #!/bin/bash
            set -euo pipefail

            BACKUP_DIR="/mnt/backups/$(date +%F)"
            mkdir -p "$BACKUP_DIR"
            rsync -a --delete /srv/data/ "$BACKUP_DIR"
            echo "Backup klaar: $BACKUP_DIR"
            BASH,
        'created' => '2026-09-12 22:30:00',
    ],
    [
        'id' => 5,
        'user_id' => 4,
        'language' => 'css',
        'theme' => 'light',
        'caption' => 'Eindelijk die centreer-trucje onthouden zonder te googlen.',
        'code' => ".center {\n  display: grid;\n  place-items: center;\n  min-height: 100vh;\n}",
        'created' => '2026-09-13 16:00:00',
    ],
    [
        'id' => 6,
        'user_id' => 1,
        'language' => 'sql',
        'theme' => 'dark',
        'caption' => null,
        'code' => "SELECT u.username, COUNT(p.id) AS post_count\nFROM user u\nLEFT JOIN post p ON p.user_id = u.id\nGROUP BY u.id\nORDER BY post_count DESC;",
        'created' => '2026-09-14 08:20:00',
    ],
    [
        'id' => 7,
        'user_id' => 3,
        'language' => 'rust',
        'theme' => 'monokai',
        'caption' => 'Bezig met Rust leren, de borrow checker en ik zijn nog geen vrienden.',
        'code' => "fn longest<'a>(x: &'a str, y: &'a str) -> &'a str {\n    if x.len() > y.len() { x } else { y }\n}",
        'created' => '2026-09-14 19:10:00',
    ],
    [
        'id' => 8,
        'user_id' => 2,
        'language' => 'typescript',
        'theme' => 'dracula',
        'caption' => 'Kleine utility type die ik steeds nodig heb.',
        'code' => "type DeepPartial<T> = T extends object\n  ? { [K in keyof T]?: DeepPartial<T[K]> }\n  : T;",
        'created' => '2026-09-15 09:00:00',
    ],
    [
        'id' => 9,
        'user_id' => 4,
        'language' => 'php',
        'theme' => 'light',
        'caption' => 'Fork van arne z\'n slug helper, nu met ondersteuning voor underscores.',
        'code' => "function str_slug(string \$text): string\n{\n    \$text = strtolower(trim(\$text));\n    \$text = preg_replace('/[^a-z0-9_]+/', '-', \$text);\n\n    return trim(\$text, '-');\n}",
        'created' => '2026-09-15 10:30:00',
        'forked_from_id' => 1,
    ],
];

foreach ($posts as $postData) {
    $user = R::load('user', (int) $postData['user_id']);

    $post = R::load('post', (int) $postData['id']);
    $post->code = $postData['code'];
    $post->language = $postData['language'];
    $post->theme = $postData['theme'];
    $post->caption = $postData['caption'];
    $post->created = $postData['created'];
    $post->forked_from_id = $postData['forked_from_id'] ?? null;
    $post->user = $user;
    R::store($post);
}

$likes = [
    ['id' => 1, 'post_id' => 1, 'user_id' => 2],
    ['id' => 2, 'post_id' => 1, 'user_id' => 3],
    ['id' => 3, 'post_id' => 1, 'user_id' => 4],
    ['id' => 4, 'post_id' => 2, 'user_id' => 1],
    ['id' => 5, 'post_id' => 3, 'user_id' => 1],
    ['id' => 6, 'post_id' => 3, 'user_id' => 5],
    ['id' => 7, 'post_id' => 4, 'user_id' => 1],
    ['id' => 8, 'post_id' => 6, 'user_id' => 3],
    ['id' => 9, 'post_id' => 8, 'user_id' => 1],
    ['id' => 10, 'post_id' => 8, 'user_id' => 5],
];

foreach ($likes as $likeData) {
    $post = R::load('post', (int) $likeData['post_id']);
    $user = R::load('user', (int) $likeData['user_id']);

    $like = R::load('postlike', (int) $likeData['id']);
    $like->post = $post;
    $like->user = $user;
    R::store($like);
}

$comments = [
    [
        'id' => 1,
        'post_id' => 1,
        'user_id' => 3,
        'content' => 'Handig, ga ik zo overnemen!',
        'created' => '2026-09-10 09:30:00',
    ],
    [
        'id' => 2,
        'post_id' => 1,
        'user_id' => 5,
        'content' => 'preg_replace met een unicode-vlag erbij en je bent klaar voor de rest van de wereld.',
        'created' => '2026-09-10 11:02:00',
    ],
    [
        'id' => 3,
        'post_id' => 2,
        'user_id' => 4,
        'content' => 'Simpel en werkt precies zoals het moet.',
        'created' => '2026-09-11 15:00:00',
    ],
    [
        'id' => 4,
        'post_id' => 3,
        'user_id' => 1,
        'content' => 'Decorators zijn inderdaad magisch, wacht tot je async decorators nodig hebt.',
        'created' => '2026-09-12 10:20:00',
    ],
    [
        'id' => 5,
        'post_id' => 7,
        'user_id' => 5,
        'content' => 'De borrow checker wint altijd, geef het op :)',
        'created' => '2026-09-14 19:40:00',
    ],
];

foreach ($comments as $commentData) {
    $post = R::load('post', (int) $commentData['post_id']);
    $user = R::load('user', (int) $commentData['user_id']);

    $comment = R::load('comment', (int) $commentData['id']);
    $comment->content = $commentData['content'];
    $comment->created = $commentData['created'];
    $comment->post = $post;
    $comment->user = $user;
    R::store($comment);
}

echo 'Database seeding completed successfully.' . PHP_EOL;
echo 'Login met bijvoorbeeld: arne / geheim123' . PHP_EOL;
