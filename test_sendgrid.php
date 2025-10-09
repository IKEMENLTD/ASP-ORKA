<?php
/**
 * SendGrid メール送信テスト
 *
 * 使用方法:
 *   1. .envファイルでSendGrid設定
 *   2. このスクリプトを実行: php test_sendgrid.php
 */

echo "========================================\n";
echo "  SendGrid メール送信テスト\n";
echo "========================================\n\n";

// 環境変数読み込み
require_once 'custom/load_env.php';

// Mail.phpとSendGridMailer.phpを読み込み
require_once 'include/Mail.php';

// 設定確認
echo "1. 環境変数確認...\n";
$sendgridApiKey = getenv('SENDGRID_API_KEY');
$useSendGrid = getenv('USE_SENDGRID');

echo "  SENDGRID_API_KEY: " . ($sendgridApiKey ? substr($sendgridApiKey, 0, 20) . '...' : '未設定') . "\n";
echo "  USE_SENDGRID: " . ($useSendGrid ? $useSendGrid : 'false') . "\n";
echo "\n";

if (!$sendgridApiKey) {
    echo "❌ エラー: SENDGRID_API_KEYが設定されていません\n";
    echo "\n";
    echo "SendGrid API Keyの取得方法:\n";
    echo "  1. https://sendgrid.com にアクセス\n";
    echo "  2. Settings → API Keys をクリック\n";
    echo "  3. Create API Key をクリック\n";
    echo "  4. Full Access を選択\n";
    echo "  5. 生成されたAPI Keyを .env に設定:\n";
    echo "     SENDGRID_API_KEY=SG.xxxxxxxx...\n";
    echo "     USE_SENDGRID=true\n";
    echo "\n";
    exit(1);
}

// テスト送信先メールアドレス入力
echo "2. テスト送信先メールアドレスを入力してください:\n";
echo "   (例: your-email@example.com)\n";
echo "   → ";

$to = trim(fgets(STDIN));

if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "❌ エラー: 有効なメールアドレスを入力してください\n";
    exit(1);
}

echo "\n";
echo "3. テストメール送信中...\n";

// テストメール送信
$from = getenv('MAIL_FROM') ?: 'noreply@affiliate-system.com';
$fromName = getenv('MAIL_FROM_NAME') ?: 'アフィリエイトシステム';
$subject = 'SendGrid テストメール - ' . date('Y-m-d H:i:s');
$body = "これはSendGridのテストメールです。\n\n";
$body .= "送信時刻: " . date('Y-m-d H:i:s') . "\n";
$body .= "送信元: " . $fromName . " <" . $from . ">\n";
$body .= "送信先: " . $to . "\n\n";
$body .= "このメールが届いていれば、SendGrid統合は正常に動作しています。\n\n";
$body .= "---\n";
$body .= "アフィリエイトシステムプロ\n";
$body .= "Powered by SendGrid\n";

try {
    // USE_SENDGRID=true の場合はSendGrid、false の場合はmb_send_mail
    if ($useSendGrid === 'true' || $useSendGrid === '1') {
        echo "  SendGrid API経由で送信します...\n";
    } else {
        echo "  mb_send_mail（PHP標準）で送信します...\n";
        echo "  ⚠️  SendGridを使用する場合は、.env で USE_SENDGRID=true に設定してください\n";
    }

    Mail::sendString($subject, $body, $from, $to, $fromName);

    echo "\n";
    echo "✅ メール送信完了\n";
    echo "\n";
    echo "送信先メールボックスを確認してください:\n";
    echo "  - 受信トレイ\n";
    echo "  - 迷惑メールフォルダ\n";
    echo "\n";

    if ($useSendGrid === 'true' || $useSendGrid === '1') {
        echo "SendGridダッシュボードで配信ステータスを確認:\n";
        echo "  https://app.sendgrid.com/email_activity\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "\n";
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "\n";

    if ($useSendGrid === 'true' || $useSendGrid === '1') {
        echo "トラブルシューティング:\n";
        echo "  1. SENDGRID_API_KEYが正しいか確認\n";
        echo "  2. SendGridアカウントがアクティブか確認\n";
        echo "  3. 送信元メールアドレスが認証済みか確認\n";
        echo "     https://app.sendgrid.com/settings/sender_auth\n";
        echo "\n";
    }

    exit(1);
}

echo "========================================\n";
echo "  テスト完了\n";
echo "========================================\n";
?>
