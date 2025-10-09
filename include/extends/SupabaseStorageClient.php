<?php
/**
 * Supabase Storage API Client
 *
 * Supabase Storageへのファイルアップロード・ダウンロード・削除を提供
 */

class SupabaseStorageClient
{
    private $supabaseUrl;
    private $supabaseKey;
    private $bucket;

    /**
     * コンストラクタ
     * @param string $bucket バケット名（affiliate-images または affiliate-files）
     */
    public function __construct($bucket = 'affiliate-files')
    {
        $this->supabaseUrl = getenv('SUPABASE_URL');
        $this->supabaseKey = getenv('SUPABASE_ANON_KEY');
        $this->bucket = $bucket;

        if (!$this->supabaseUrl || !$this->supabaseKey) {
            throw new Exception('Supabase環境変数が設定されていません');
        }
    }

    /**
     * ファイルをアップロード
     * @param string $localPath ローカルファイルパス
     * @param string $remotePath リモートパス（バケット内のパス）
     * @return bool 成功/失敗
     */
    public function upload($localPath, $remotePath)
    {
        if (!file_exists($localPath)) {
            return false;
        }

        $fileContent = file_get_contents($localPath);
        $contentType = $this->getContentType($localPath);

        $url = $this->supabaseUrl . '/storage/v1/object/' . $this->bucket . '/' . $remotePath;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->supabaseKey,
            'apikey: ' . $this->supabaseKey,
            'Content-Type: ' . $contentType,
            'x-upsert: true' // 既存ファイルを上書き
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * ファイルをダウンロード
     * @param string $remotePath リモートパス
     * @return string|false ファイル内容（失敗時はfalse）
     */
    public function download($remotePath)
    {
        $url = $this->supabaseUrl . '/storage/v1/object/public/' . $this->bucket . '/' . $remotePath;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->supabaseKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $response;
        }

        return false;
    }

    /**
     * ファイルを削除
     * @param string $remotePath リモートパス
     * @return bool 成功/失敗
     */
    public function delete($remotePath)
    {
        $url = $this->supabaseUrl . '/storage/v1/object/' . $this->bucket . '/' . $remotePath;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->supabaseKey,
            'apikey: ' . $this->supabaseKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * ファイルが存在するか確認
     * @param string $remotePath リモートパス
     * @return bool 存在する/しない
     */
    public function exists($remotePath)
    {
        $url = $this->supabaseUrl . '/storage/v1/object/public/' . $this->bucket . '/' . $remotePath;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * 公開URLを取得
     * @param string $remotePath リモートパス
     * @return string 公開URL
     */
    public function getPublicUrl($remotePath)
    {
        return $this->supabaseUrl . '/storage/v1/object/public/' . $this->bucket . '/' . $remotePath;
    }

    /**
     * Content-Typeを判定
     * @param string $filePath ファイルパス
     * @return string Content-Type
     */
    private function getContentType($filePath)
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml'
        ];

        return isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
    }
}
?>
