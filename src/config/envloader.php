<?php
    require_once realpath(__DIR__ . "/../vendor/autoload.php");

    use Dotenv\Dotenv;

    static $environmentLoaded = false;

    if (!$environmentLoaded) {
        $candidatePaths = [
            dirname(__DIR__, 2),
            dirname(__DIR__),
        ];

        foreach ($candidatePaths as $path) {
            if (is_file($path . '/.env')) {
                Dotenv::createImmutable($path)->safeLoad();
                break;
            }
        }

        $processEnvironment = getenv();
        if (is_array($processEnvironment)) {
            foreach ($processEnvironment as $key => $value) {
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $value;
                }
            }
        }

        $environmentLoaded = true;
    }
?>