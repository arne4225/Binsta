<?php

class PostController extends BaseController
{
    public const LANGUAGES = [
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'python' => 'Python',
        'php' => 'PHP',
        'java' => 'Java',
        'csharp' => 'C#',
        'cpp' => 'C++',
        'c' => 'C',
        'ruby' => 'Ruby',
        'go' => 'Go',
        'rust' => 'Rust',
        'html' => 'HTML',
        'css' => 'CSS',
        'sql' => 'SQL',
        'bash' => 'Bash',
        'json' => 'JSON',
    ];

    public const THEMES = [
        'dark' => 'Dark (Atom One)',
        'light' => 'Light (GitHub)',
        'dracula' => 'Dracula',
        'monokai' => 'Monokai',
        'solarized' => 'Solarized Light',
    ];

    public const DEFAULT_THEME = 'dark';

    public function create(): void
    {
        $this->authorizeUser();

        $this->renderTemplate('post/create.twig', [
            'languages' => self::LANGUAGES,
            'themes' => self::THEMES,
        ]);
    }

    public function fork(): void
    {
        $this->authorizeUser();

        $original = $this->getBeanById('post', 'id');

        $this->renderTemplate('post/create.twig', [
            'languages' => self::LANGUAGES,
            'themes' => self::THEMES,
            'forkedFrom' => [
                'id' => $original->id,
                'user' => $this->exportUserForDisplay($original->user),
            ],
            'prefill' => [
                'code' => $original->code,
                'language' => $original->language,
                'theme' => $original->theme ?: self::DEFAULT_THEME,
            ],
        ]);
    }

    public function createPost(): void
    {
        $this->authorizeUser();

        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $language = isset($_POST['language']) ? trim($_POST['language']) : '';
        $theme = isset($_POST['theme']) ? trim($_POST['theme']) : '';
        $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';

        if ($code === '' || $language === '' || $theme === '') {
            error(400, 'Vul de code, programmeertaal en het kleurenschema in.');
        }

        if (!array_key_exists($language, self::LANGUAGES)) {
            error(400, 'Ongeldige programmeertaal.');
        }

        if (!array_key_exists($theme, self::THEMES)) {
            error(400, 'Ongeldig kleurenschema.');
        }

        $forkedFromId = isset($_POST['forked_from_id']) ? (int) $_POST['forked_from_id'] : 0;
        $forkedFromPostId = null;

        if ($forkedFromId > 0) {
            $forkedFromPost = \RedBeanPHP\R::load('post', $forkedFromId);

            if ($forkedFromPost->id) {
                $forkedFromPostId = (int) $forkedFromPost->id;
            }
        }

        $post = \RedBeanPHP\R::dispense('post');
        $post->code = $code;
        $post->language = $language;
        $post->theme = $theme;
        $post->caption = $caption;
        $post->forked_from_id = $forkedFromPostId;
        $post->created = date('Y-m-d H:i:s');
        $post->user = \RedBeanPHP\R::load('user', (int) $_SESSION['user_id']);

        \RedBeanPHP\R::store($post);

        redirect('/feed/index');
    }

    public function likePost(): void
    {
        $this->authorizeUser();

        $post = $this->getBeanById('post', 'id');
        $userId = (int) $_SESSION['user_id'];

        $existingLike = \RedBeanPHP\R::findOne('postlike', 'post_id = ? AND user_id = ?', [$post->id, $userId]);

        if ($existingLike) {
            \RedBeanPHP\R::trash($existingLike);
        } else {
            $like = \RedBeanPHP\R::dispense('postlike');
            $like->post = $post;
            $like->user = \RedBeanPHP\R::load('user', $userId);
            \RedBeanPHP\R::store($like);
        }

        $this->redirectBack('/feed/index');
    }

    public function commentPost(): void
    {
        $this->authorizeUser();

        $post = $this->getBeanById('post', 'id');
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';

        if ($content === '') {
            $_SESSION['flash_error'] = 'Vul een reactie in.';
            $this->redirectBack('/feed/index');
        }

        $comment = \RedBeanPHP\R::dispense('comment');
        $comment->content = $content;
        $comment->created = date('Y-m-d H:i:s');
        $comment->post = $post;
        $comment->user = \RedBeanPHP\R::load('user', (int) $_SESSION['user_id']);

        \RedBeanPHP\R::store($comment);

        $this->redirectBack('/feed/index');
    }
}
