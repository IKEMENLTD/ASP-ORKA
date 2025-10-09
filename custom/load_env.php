<?php
/**
 * .env ファイルから環境変数を読み込む
 *
 * 使用方法:
 *   custom/conf.php の先頭で require_once 'custom/load_env.php';
 */

function loadEnv($path = null) {
    if ($path === null) {
        $path = dirname(__DIR__) . '/.env';
    }

    if (!file_exists($path)) {
        // 本番環境では環境変数が既に設定されている想定
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // コメント行をスキップ
        if (strpos($line, '#') === 0) {
            continue;
        }

        // KEY=VALUE 形式をパース
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // クォートを除去
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // 既存の環境変数を上書きしない (Renderの環境変数を優先)
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// 環境変数読み込み
loadEnv();

?>
