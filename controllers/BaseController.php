<?php

use Twig\Environment;

class BaseController
{
    protected Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    protected function renderTemplate(string $template, array $variables = []): void
    {
        $variables['currentUser'] = $variables['currentUser'] ?? $this->currentUser();

        echo $this->twig->render($template, $variables);
    }

    protected function authorizeUser(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect('/user/login');
        }
    }

    protected function currentUser(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $user = \RedBeanPHP\R::load('user', (int) $_SESSION['user_id']);

        if (!$user->id) {
            return null;
        }

        return $user->export();
    }

    public function getBeanById($typeOfBean, $queryStringKey)
    {
        $id = isset($_GET[$queryStringKey]) ? (int) $_GET[$queryStringKey] : null;

        if ($id === null || $id <= 0) {
            error(404, 'Item niet gevonden.');
        }

        $bean = \RedBeanPHP\R::load($typeOfBean, $id);

        if (!$bean->id) {
            error(404, 'Item niet gevonden.');
        }

        return $bean;
    }

    protected function redirectBack(string $fallback): void
    {
        $back = $_POST['back'] ?? '';

        if (is_string($back) && preg_match('#^/[A-Za-z0-9/_?=&-]*$#', $back) && !str_starts_with($back, '//')) {
            redirect($back);
        }

        redirect($fallback);
    }

    protected function exportPostsForDisplay(array $postBeans): array
    {
        $currentUserId = $_SESSION['user_id'] ?? null;
        $posts = [];

        foreach ($postBeans as $postBean) {
            $comments = [];

            foreach (\RedBeanPHP\R::find('comment', 'post_id = ? ORDER BY id ASC', [$postBean->id]) as $commentBean) {
                $comments[] = [
                    'id' => $commentBean->id,
                    'content' => $commentBean->content,
                    'created' => $commentBean->created,
                    'user' => $this->exportUserForDisplay($commentBean->user),
                ];
            }

            $forkedFrom = null;

            if (!empty($postBean->forked_from_id)) {
                $originalPost = \RedBeanPHP\R::load('post', (int) $postBean->forked_from_id);

                if ($originalPost->id) {
                    $forkedFrom = [
                        'id' => $originalPost->id,
                        'user' => $this->exportUserForDisplay($originalPost->user),
                    ];
                }
            }

            $posts[] = [
                'id' => $postBean->id,
                'code' => $postBean->code,
                'language' => $postBean->language,
                'theme' => $postBean->theme ?: PostController::DEFAULT_THEME,
                'caption' => $postBean->caption,
                'created' => $postBean->created,
                'user' => $this->exportUserForDisplay($postBean->user),
                'like_count' => \RedBeanPHP\R::count('postlike', 'post_id = ?', [$postBean->id]),
                'liked_by_me' => $currentUserId
                    ? !is_null(\RedBeanPHP\R::findOne('postlike', 'post_id = ? AND user_id = ?', [$postBean->id, (int) $currentUserId]))
                    : false,
                'comments' => $comments,
                'forked_from' => $forkedFrom,
            ];
        }

        return $posts;
    }

    protected function exportUserForDisplay(\RedBeanPHP\OODBBean $userBean): array
    {
        $user = $userBean->export();
        $user['display_name'] = $user['name'] ?: $user['username'];

        return $user;
    }
}