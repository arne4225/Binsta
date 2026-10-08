<?php

use Twig\Environment;

function error($errorNumber, $errorMessage): void
{
    if (!isset($GLOBALS['twig']) || !($GLOBALS['twig'] instanceof Environment)) {
        http_response_code((int) $errorNumber);
        echo $errorMessage;
        exit;
    }

    http_response_code((int) $errorNumber);
    echo $GLOBALS['twig']->render('error.twig', [
        'errorNumber' => $errorNumber,
        'errorMessage' => $errorMessage,
    ]);
    exit;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
