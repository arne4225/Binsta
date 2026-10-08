<?php

class FeedController extends BaseController
{
    public function index(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $posts = $this->exportPostsForDisplay(\RedBeanPHP\R::findAll('post', 'ORDER BY id DESC'));

        $this->renderTemplate('feed/index.twig', [
            'posts' => $posts,
            'error' => $error,
        ]);
    }
}
