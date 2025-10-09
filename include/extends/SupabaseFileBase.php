<?php
namespace Websquare\FileBase;

require_once __DIR__ . '/SupabaseStorageClient.php';

/**
 * Supabase Storage対応 FileBase実装
 *
 * 既存のFileBaseインターフェースと互換性を保ちつつ、
 * Supabase Storageにファイルを保存
 */
class SupabaseFileBase implements iFileBase
{
    private $storageClient;
    private $localCache = [];

    public function init($conf)
    {
        // 環境変数USE_SUPABASE_STORAGEで切り替え
        $useSupabase = getenv('USE_SUPABASE_STORAGE');

        if ($useSupabase === 'true' || $useSupabase === '1') {
            // Supabase Storage使用
            $bucket = getenv('SUPABASE_STORAGE_BUCKET') ?: 'affiliate-files';
            $this->storageClient = new \SupabaseStorageClient($bucket);
        }
    }

    /**
     * ファイルのパーミッション設定（Supabaseでは不要、互換性のため残す）
     */
    public function put($key, $resource = null)
    {
        // Supabase Storageではパーミッション設定不要
        return;
    }

    /**
     * ファイル内容を取得
     * @param string $key ファイルパス
     * @return string ファイル内容
     */
    public function get($key)
    {
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key);
            return $this->storageClient->download($remotePath);
        }

        return file_get_contents($key);
    }

    /**
     * ファイル名変更（移動）
     * @param string $key1 元のパス
     * @param string $key2 新しいパス
     * @return bool 成功/失敗
     */
    public function rename($key1, $key2)
    {
        if ($this->storageClient) {
            $remotePath1 = $this->getRemotePath($key1);
            $remotePath2 = $this->getRemotePath($key2);

            // Supabaseでは直接renameできないので、copy + delete
            $content = $this->storageClient->download($remotePath1);
            if ($content !== false) {
                // 一時ファイルに保存
                $tempFile = sys_get_temp_dir() . '/' . basename($key1);
                file_put_contents($tempFile, $content);

                // 新しい場所にアップロード
                $result = $this->storageClient->upload($tempFile, $remotePath2);

                // 元のファイルを削除
                if ($result) {
                    $this->storageClient->delete($remotePath1);
                }

                unlink($tempFile);
                return $result;
            }
            return false;
        }

        return rename($key1, $key2);
    }

    /**
     * ファイル削除
     * @param string $key ファイルパス
     * @return bool 成功/失敗
     */
    public function delete($key)
    {
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key);
            return $this->storageClient->delete($remotePath);
        }

        return unlink($key);
    }

    /**
     * ファイルコピー
     * @param string $key 元のパス
     * @param string $key2 コピー先パス
     * @return bool 成功/失敗
     */
    public function copy($key, $key2)
    {
        if ($this->storageClient) {
            $remotePath1 = $this->getRemotePath($key);
            $remotePath2 = $this->getRemotePath($key2);

            $content = $this->storageClient->download($remotePath1);
            if ($content !== false) {
                $tempFile = sys_get_temp_dir() . '/' . basename($key);
                file_put_contents($tempFile, $content);

                $result = $this->storageClient->upload($tempFile, $remotePath2);
                unlink($tempFile);
                return $result;
            }
            return false;
        }

        return copy($key, $key2);
    }

    /**
     * ディレクトリかどうか確認（Supabaseでは常にfalse）
     */
    public function is_dir($key)
    {
        if ($this->storageClient) {
            return false; // Supabase Storageにディレクトリの概念なし
        }

        return is_dir($key);
    }

    /**
     * ファイルかどうか確認
     */
    public function is_file($key)
    {
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key);
            return $this->storageClient->exists($remotePath);
        }

        return is_file($key);
    }

    /**
     * ファイルが存在するか確認
     */
    public function file_exists($key)
    {
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key);
            return $this->storageClient->exists($remotePath);
        }

        return file_exists($key);
    }

    /**
     * 画像サイズ取得
     */
    public function getimagesize($key)
    {
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key);
            $content = $this->storageClient->download($remotePath);

            if ($content !== false) {
                $tempFile = sys_get_temp_dir() . '/' . md5($key) . '.tmp';
                file_put_contents($tempFile, $content);
                $size = getimagesize($tempFile);
                unlink($tempFile);
                return $size;
            }
            return false;
        }

        return getimagesize($key);
    }

    /**
     * ファイル更新時刻取得（Supabaseでは現在時刻を返す）
     */
    public function filemtime($key)
    {
        if ($this->storageClient) {
            return time(); // Supabaseではメタデータ取得が複雑なので現在時刻
        }

        return filemtime($key);
    }

    /**
     * ファイルパス取得（Supabaseでは公開URL）
     */
    public function getfilepath($key)
    {
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key);
            return $this->storageClient->getPublicUrl($remotePath);
        }

        return $key;
    }

    /**
     * URL取得（getfilepathと同じ）
     */
    public function geturl($key)
    {
        return $this->getfilepath($key);
    }

    /**
     * ファイルアップロード
     * @param string $key 一時ファイルパス
     * @param string $key2 保存先パス
     * @return bool 成功/失敗
     */
    public function upload($key, $key2)
    {
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key2);
            return $this->storageClient->upload($key, $remotePath);
        }

        // ローカル保存
        if (file_exists($key) && $key == $key2) {
            return true;
        } else if (copy($key, $key2)) {
            unlink($key);
            return true;
        }
        return false;
    }

    /**
     * 画像の回転修正（EXIF Orientationに対応）
     */
    public function fixRotate($key)
    {
        // Supabaseの場合は一時ファイルにダウンロードして処理
        if ($this->storageClient) {
            $remotePath = $this->getRemotePath($key);
            $content = $this->storageClient->download($remotePath);

            if ($content === false) {
                return;
            }

            $tempFile = sys_get_temp_dir() . '/' . basename($key);
            file_put_contents($tempFile, $content);

            // 既存のfixRotateロジックを実行
            $this->fixRotateLocal($tempFile);

            // 処理後のファイルを再アップロード
            $this->storageClient->upload($tempFile, $remotePath);
            unlink($tempFile);
            return;
        }

        // ローカルファイルの場合
        $this->fixRotateLocal($key);
    }

    /**
     * ローカルファイルの回転修正処理
     */
    private function fixRotateLocal($key)
    {
        if (!file_exists($key)) {
            return;
        }

        $exif = @exif_read_data($key);
        if (!$exif || !isset($exif['Orientation'])) {
            return;
        }

        $flag = $exif['Orientation'];

        if ($flag < 2) {
            return; // 修正不要
        }

        $details = getimagesize($key);
        $width = $details[0];
        $height = $details[1];
        $fileType = $details[2];

        $resource = $this->getimageresource($fileType, $key);
        if (!$resource) {
            return;
        }

        // 回転処理
        if ($flag >= 7) {
            $resource = imagerotate($resource, 90, 0);
        } else if ($flag >= 5) {
            $resource = imagerotate($resource, -90, 0);
        } else if ($flag >= 3) {
            $resource = imagerotate($resource, 180, 0);
        }

        // 保存
        switch ($fileType) {
            case IMAGETYPE_GIF:
                imagegif($resource, $key);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($resource, $key, 100);
                break;
            case IMAGETYPE_PNG:
                imagepng($resource, $key, 0);
                break;
        }

        imagedestroy($resource);
    }

    /**
     * 画像リソースを取得
     */
    public function getimageresource($type, $key)
    {
        $resource = false;

        switch ($type) {
            case IMAGETYPE_GIF:
                $resource = imagecreatefromgif($key);
                break;
            case IMAGETYPE_JPEG:
                $resource = imagecreatefromjpeg($key);
                break;
            case IMAGETYPE_PNG:
                $resource = imagecreatefrompng($key);
                break;
        }

        return $resource;
    }

    /**
     * ローカルパスからSupabase Storageのリモートパスに変換
     * @param string $localPath ローカルパス（例: file/image/xxx.jpg）
     * @return string リモートパス（例: image/xxx.jpg）
     */
    private function getRemotePath($localPath)
    {
        // file/ プレフィックスを削除
        $remotePath = preg_replace('#^file/#', '', $localPath);
        return $remotePath;
    }
}
?>
