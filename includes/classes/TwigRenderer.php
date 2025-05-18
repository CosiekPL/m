
<?php
require_once 'Twig/Loader/FilesystemLoader.php';
require_once 'Twig/Environment.php';

class TwigRenderer {
    public static function render($template, $context = []) {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../../templates');
        $twig = new \Twig\Environment($loader, [
            'cache' => false,
            'debug' => true,
        ]);
        return $twig->render($template, $context);
    }
}
