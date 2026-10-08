<?php

class UserController extends BaseController
{
    public function login(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $this->renderTemplate('user/login.twig', [
            'error' => $error,
        ]);
    }

    public function loginPost(): void
    {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        $user = $username ? \RedBeanPHP\R::findOne('user', 'username = ?', [$username]) : null;

        if (is_null($user) || !password_verify($password, $user->password)) {
            $_SESSION['flash_error'] = 'Ongeldige gebruikersnaam of wachtwoord.';
            redirect('/user/login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;

        redirect('/feed/index');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();

        redirect('/');
    }

    public function profile(): void
    {
        $this->authorizeUser();

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $success = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);

        $this->renderTemplate('user/profile.twig', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function updateProfilePost(): void
    {
        $this->authorizeUser();

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';

        $user = \RedBeanPHP\R::load('user', (int) $_SESSION['user_id']);

        $user->name = $name;
        $user->bio = $bio;
        \RedBeanPHP\R::store($user);

        $_SESSION['flash_success'] = 'Je profiel is bijgewerkt.';
        redirect('/user/profile');
    }

    public function uploadPhotoPost(): void
    {
        $this->authorizeUser();

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Kies een geldige afbeelding om te uploaden.';
            redirect('/user/profile');
        }

        $file = $_FILES['photo'];

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);

        if (!isset($allowedTypes[$mimeType])) {
            $_SESSION['flash_error'] = 'Alleen JPG, PNG, GIF of WEBP afbeeldingen zijn toegestaan.';
            redirect('/user/profile');
        }

        $maxSize = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            $_SESSION['flash_error'] = 'De afbeelding mag maximaal 5MB zijn.';
            redirect('/user/profile');
        }

        $uploadDir = __DIR__ . '/../public/uploads/avatars/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'user_' . $_SESSION['user_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mimeType];

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $_SESSION['flash_error'] = 'Er is iets misgegaan bij het uploaden.';
            redirect('/user/profile');
        }

        $user = \RedBeanPHP\R::load('user', (int) $_SESSION['user_id']);

        if (!empty($user->profile_photo)) {
            $oldPath = __DIR__ . '/../public/' . $user->profile_photo;

            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        $user->profile_photo = 'uploads/avatars/' . $filename;
        \RedBeanPHP\R::store($user);

        $_SESSION['flash_success'] = 'Je profielfoto is bijgewerkt.';
        redirect('/user/profile');
    }

    public function changePasswordPost(): void
    {
        $this->authorizeUser();

        $currentPassword = isset($_POST['current_password']) ? (string) $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
        $newPasswordConfirmation = isset($_POST['new_password_confirmation']) ? (string) $_POST['new_password_confirmation'] : '';

        if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirmation === '') {
            $_SESSION['flash_error'] = 'Vul alle wachtwoordvelden in.';
            redirect('/user/profile');
        }

        $user = \RedBeanPHP\R::load('user', (int) $_SESSION['user_id']);

        if (!password_verify($currentPassword, $user->password)) {
            $_SESSION['flash_error'] = 'Je huidige wachtwoord is onjuist.';
            redirect('/user/profile');
        }

        if ($newPassword !== $newPasswordConfirmation) {
            $_SESSION['flash_error'] = 'De nieuwe wachtwoorden komen niet overeen.';
            redirect('/user/profile');
        }

        $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        \RedBeanPHP\R::store($user);

        $_SESSION['flash_success'] = 'Je wachtwoord is gewijzigd.';
        redirect('/user/profile');
    }

    public function register(): void
    {
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $old = $_SESSION['flash_old'] ?? [];
        unset($_SESSION['flash_old']);

        $this->renderTemplate('user/register.twig', [
            'error' => $error,
            'old' => $old,
        ]);
    }

    public function registerPost(): void
    {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $passwordConfirmation = isset($_POST['password_confirmation']) ? (string) $_POST['password_confirmation'] : '';

        $_SESSION['flash_old'] = ['username' => $username];

        if ($username === '' || $password === '' || $passwordConfirmation === '') {
            $_SESSION['flash_error'] = 'Vul alle velden in.';
            redirect('/user/register');
        }

        if ($password !== $passwordConfirmation) {
            $_SESSION['flash_error'] = 'De wachtwoorden komen niet overeen.';
            redirect('/user/register');
        }

        $existingUser = \RedBeanPHP\R::findOne('user', 'username = ?', [$username]);

        if (!is_null($existingUser)) {
            $_SESSION['flash_error'] = 'Deze gebruikersnaam is al in gebruik.';
            redirect('/user/register');
        }

        $user = \RedBeanPHP\R::dispense('user');
        $user->username = $username;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        \RedBeanPHP\R::store($user);

        unset($_SESSION['flash_old']);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;

        redirect('/feed/index');
    }

    public function show(): void
    {
        $user = $this->getBeanById('user', 'id');

        $posts = $this->exportPostsForDisplay(\RedBeanPHP\R::find('post', 'user_id = ? ORDER BY id DESC', [$user->id]));

        $this->renderTemplate('user/show.twig', [
            'profileUser' => $user->export(),
            'posts' => $posts,
        ]);
    }

    public function search(): void
    {
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $users = [];

        if ($query !== '') {
            $beans = \RedBeanPHP\R::find(
                'user',
                'username LIKE ? OR name LIKE ? ORDER BY username ASC',
                ['%' . $query . '%', '%' . $query . '%']
            );

            foreach ($beans as $bean) {
                $users[] = $this->exportUserForDisplay($bean);
            }
        }

        $this->renderTemplate('user/search.twig', [
            'users' => $users,
            'query' => $query,
        ]);
    }
}
