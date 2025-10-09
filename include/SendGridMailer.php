<?php
/**
 * SendGrid Mail API Client
 *
 * SendGrid Web API v3を使用したメール送信クライアント
 * 既存のMail.phpクラスと互換性を保ちつつSendGridを使用
 */

class SendGridMailer
{
    private $apiKey;
    private $apiEndpoint = 'https://api.sendgrid.com/v3/mail/send';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->apiKey = getenv('SENDGRID_API_KEY');

        if (!$this->apiKey) {
            throw new Exception('SENDGRID_API_KEY環境変数が設定されていません');
        }
    }

    /**
     * メール送信
     *
     * @param string $to 送信先メールアドレス
     * @param string $subject 件名
     * @param string $body 本文（プレーンテキスト）
     * @param string $from 送信元メールアドレス
     * @param string $fromName 送信元名（省略可）
     * @param array $ccs CCアドレス配列（省略可）
     * @param array $bccs BCCアドレス配列（省略可）
     * @return bool 成功/失敗
     */
    public function send($to, $subject, $body, $from, $fromName = null, $ccs = null, $bccs = null)
    {
        // SendGrid API リクエストボディ構築
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ]
                ]
            ],
            'from' => [
                'email' => $from,
                'name' => $fromName ?: $from
            ],
            'subject' => $subject,
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $body
                ]
            ]
        ];

        // CC追加
        if (!is_null($ccs) && is_array($ccs) && count($ccs) > 0) {
            $ccList = [];
            foreach ($ccs as $cc) {
                $ccList[] = [
                    'email' => isset($cc['mail']) ? $cc['mail'] : $cc,
                    'name' => isset($cc['name']) ? $cc['name'] : ''
                ];
            }
            $data['personalizations'][0]['cc'] = $ccList;
        }

        // BCC追加
        if (!is_null($bccs) && is_array($bccs) && count($bccs) > 0) {
            $bccList = [];
            foreach ($bccs as $bcc) {
                $bccList[] = [
                    'email' => isset($bcc['mail']) ? $bcc['mail'] : $bcc,
                    'name' => isset($bcc['name']) ? $bcc['name'] : ''
                ];
            }
            $data['personalizations'][0]['bcc'] = $bccList;
        }

        return $this->sendRequest($data);
    }

    /**
     * 添付ファイル付きメール送信
     *
     * @param string $to 送信先メールアドレス
     * @param string $subject 件名
     * @param string $body 本文
     * @param string $from 送信元メールアドレス
     * @param string $fromName 送信元名
     * @param string $attachmentPath 添付ファイルパス
     * @return bool 成功/失敗
     */
    public function sendWithAttachment($to, $subject, $body, $from, $fromName, $attachmentPath)
    {
        if (!file_exists($attachmentPath)) {
            return false;
        }

        $data = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $to]
                    ]
                ]
            ],
            'from' => [
                'email' => $from,
                'name' => $fromName ?: $from
            ],
            'subject' => $subject,
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $body
                ]
            ],
            'attachments' => [
                [
                    'content' => base64_encode(file_get_contents($attachmentPath)),
                    'filename' => basename($attachmentPath),
                    'type' => $this->getMimeType($attachmentPath),
                    'disposition' => 'attachment'
                ]
            ]
        ];

        return $this->sendRequest($data);
    }

    /**
     * SendGrid APIにリクエスト送信
     *
     * @param array $data リクエストデータ
     * @return bool 成功/失敗
     */
    private function sendRequest($data)
    {
        $ch = curl_init($this->apiEndpoint);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // ログ記録（デバッグ用）
        if ($httpCode >= 200 && $httpCode < 300) {
            // 成功
            return true;
        } else {
            // エラーログ
            error_log("SendGrid Error: HTTP {$httpCode}, Response: {$response}, Error: {$error}");
            return false;
        }
    }

    /**
     * ファイルのMIMEタイプを取得
     *
     * @param string $filePath ファイルパス
     * @return string MIMEタイプ
     */
    private function getMimeType($filePath)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip'
        ];

        return isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
    }
}
?>
