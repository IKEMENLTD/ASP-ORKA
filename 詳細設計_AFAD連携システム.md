# CATS - AFAD ポストバック連携システム 詳細設計書

**バージョン:** 1.0
**作成日:** 2025-10-29
**対象ブランチ:** `claude/cats-afad-socket-integration-011CUZuJVtvVu9LFctZqVLqc`

---

## 目次

1. [概要](#1-概要)
2. [システムアーキテクチャ](#2-システムアーキテクチャ)
3. [データフロー](#3-データフロー)
4. [データベース設計](#4-データベース設計)
5. [実装設計](#5-実装設計)
6. [実装手順](#6-実装手順)
7. [テスト計画](#7-テスト計画)

---

## 1. 概要

### 1.1 目的

CATSアフィリエイトシステムとAFAD（Affiliate Advertising）システムをHTTPポストバック方式で連携し、以下を実現する：

- **AFADセッションIDの受け取り**（クリック発生時）
- **成果発生時のAFADへの通知**（HTTPポストバック）
- **広告別のAFAD連携設定管理**

### 1.2 AFAD仕様書の要件

#### クリック発生時の流れ

```
ユーザー → AFAD → CATS
               ↓
    URL: https://cats-domain/link.php?adwares=XXX&id=YYY&afad_sid=ZZZ
```

**AFADから渡されるパラメータ:**
- `afad_sid`: AFADセッションID（必須）

#### 成果発生時の流れ

```
CATS → AFAD
    ↓
URL: https://ac.afad-domain/xxxxx/ac/?gid=XX&af=[セッションID]&uid=[成果情報]
```

**CATSからAFADに送るパラメータ:**
- `gid` (必須): 広告グループID
- `af` (必須): AFADセッションID（クリック時に受け取ったもの）
- `uid` (任意): 注文番号、申込み番号、会員ID
- `uid2` (任意): 追加ユーザー識別ID
- `amount` (任意): 成果金額または売上合計金額
- `Status` (任意): 承認ステータス（1:承認待ち、2:承認、3:否認）

### 1.3 既存システムとの関係

**既存のCATSシステム:**
- `link.php`: 広告クリック処理 → **拡張が必要**
- `add.php`: 成果トラッキング → **拡張が必要**
- `access`テーブル: アクセスログ → **カラム追加が必要**

---

## 2. システムアーキテクチャ

### 2.1 全体構成

```
┌──────────┐          ┌──────────┐          ┌──────────┐
│          │  クリック  │          │  クリック  │          │
│  AFAD    │─────────→│  CATS    │─────────→│  広告主  │
│          │ +afad_sid │          │           │          │
└──────────┘          └──────────┘          └──────────┘
                            │
                            │ 成果発生時
                            │ HTTPポストバック
                            ↓
                      ┌──────────┐
                      │  AFAD    │
                      │ Postback │
                      │   API    │
                      └──────────┘
```

### 2.2 処理フロー概要

**【クリック発生時】**
1. ユーザーがAFADの広告をクリック
2. AFAD → CATS (`link.php?adwares=XXX&id=YYY&afad_sid=ZZZ`)
3. CATSがAFADセッションIDを`access`テーブルに保存
4. CATS → 広告主サイトへリダイレクト

**【成果発生時】**
1. 広告主サイトで成果発生
2. 広告主 → CATS (`add.php?aid=XXX&check=YYY`)
3. CATSが成果を記録し、`access`からAFADセッションIDを取得
4. CATS → AFAD (`HTTPポストバック送信`)

---

## 3. データフロー

### 3.1 クリック時のデータフロー

```
┌─────────────────────────────────────────────────────────┐
│ 1. ユーザーがAFAD広告をクリック                          │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 2. AFAD → CATS                                          │
│    GET /link.php?adwares=123&id=456&afad_sid=abc123     │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 3. link.php処理                                         │
│    - パラメータ検証                                     │
│    - クリック報酬処理（既存）                           │
│    - access.afad_session_id = 'abc123' を保存           │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 4. CATS → 広告主サイト                                  │
│    リダイレクト                                         │
└─────────────────────────────────────────────────────────┘
```

### 3.2 成果発生時のデータフロー

```
┌─────────────────────────────────────────────────────────┐
│ 1. 広告主サイトで成果発生                               │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 2. 広告主 → CATS                                        │
│    GET /add.php?aid=xxx&check=yyy&cost=1000             │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 3. add.php処理                                          │
│    - 認証パス検証                                       │
│    - アクセスレコード取得                               │
│    - access.afad_session_id を取得                      │
│    - 成果報酬処理（既存）                               │
│    - AFAD連携判定                                       │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 4. AFAD連携がONの場合                                   │
│    - AFADポストバックURL構築                            │
│    - HTTPリクエスト送信（非同期）                       │
│    GET https://ac.afad.jp/xxx/ac/?gid=XX&af=abc123&...  │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│ 5. AFAD側で成果受信                                     │
└─────────────────────────────────────────────────────────┘
```

---

## 4. データベース設計

### 4.1 accessテーブル拡張

**追加カラム:**

```sql
ALTER TABLE `access`
ADD COLUMN `afad_session_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'AFADセッションID' AFTER `cookie`,
ADD INDEX `idx_afad_session_id` (`afad_session_id`);
```

**カラム説明:**
- `afad_session_id`: AFADから受け取ったセッションID（クリック時に保存）

### 4.2 adwaresテーブル拡張

**追加カラム:**

```sql
ALTER TABLE `adwares`
ADD COLUMN `afad_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'AFAD連携有効フラグ (0:無効, 1:有効)' AFTER `url_over`,
ADD COLUMN `afad_postback_url` TEXT NULL DEFAULT NULL COMMENT 'AFADポストバックURL' AFTER `afad_enabled`,
ADD COLUMN `afad_gid` VARCHAR(100) NULL DEFAULT NULL COMMENT 'AFAD広告グループID' AFTER `afad_postback_url`,
ADD COLUMN `afad_param_name` VARCHAR(50) NOT NULL DEFAULT 'afad_sid' COMMENT 'AFADセッションIDパラメータ名' AFTER `afad_gid`;
```

**カラム説明:**
- `afad_enabled`: AFAD連携を有効にするか（広告ごと）
- `afad_postback_url`: AFADから提供されるポストバックURL
- `afad_gid`: AFAD広告グループID（ポストバックURL内に埋め込まれている場合は不要）
- `afad_param_name`: AFADセッションIDを受け取るパラメータ名（デフォルト: `afad_sid`）

### 4.3 afad_postback_logテーブル（新規作成）

**目的:** AFADへのポストバック送信ログを記録

```sql
CREATE TABLE `afad_postback_log` (
  `id` VARCHAR(32) NOT NULL COMMENT 'ログID',
  `pay_id` VARCHAR(32) NOT NULL COMMENT '成果ID (pay.id)',
  `access_id` VARCHAR(32) NOT NULL COMMENT 'アクセスID (access.id)',
  `afad_session_id` VARCHAR(255) NOT NULL COMMENT 'AFADセッションID',
  `postback_url` TEXT NOT NULL COMMENT '送信したポストバックURL',
  `http_status` INT(11) NULL DEFAULT NULL COMMENT 'HTTPステータスコード',
  `response_body` TEXT NULL DEFAULT NULL COMMENT 'レスポンスボディ',
  `error_message` TEXT NULL DEFAULT NULL COMMENT 'エラーメッセージ',
  `sent_at` INT(11) NOT NULL COMMENT '送信日時（UNIXタイムスタンプ）',
  `retry_count` INT(11) NOT NULL DEFAULT 0 COMMENT 'リトライ回数',
  PRIMARY KEY (`id`),
  INDEX `idx_pay_id` (`pay_id`),
  INDEX `idx_access_id` (`access_id`),
  INDEX `idx_afad_session_id` (`afad_session_id`),
  INDEX `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AFADポストバック送信ログ';
```

---

## 5. 実装設計

### 5.1 link.php の拡張

**ファイル:** `/home/user/ASP-ORKA/link.php`

**変更箇所:** `AddAccess()` 関数

**変更内容:**

```php
function AddAccess( $adwares_ )
{
    global $ACTIVE_NONE;

    $cookieID = GetCookieID();

    //アクセスレコードを登録する
    $access = new FactoryModel( 'access' );
    $access->setID( md5( time() . getenv( 'REMOTE_ADDR' ) ) );
    $access->setData( 'ipaddress'    , getenv( 'REMOTE_ADDR' ) );
    $access->setData( 'cookie'       , $cookieID );

    // ★★★ AFAD連携: セッションIDを保存 ★★★
    $afadSessionId = GetAFADSessionId( $adwares_ );
    if( $afadSessionId ) {
        $access->setData( 'afad_session_id' , $afadSessionId );
    }

    $access->setData( 'adwares_type' , $adwares_->getType() );
    $access->setData( 'adwares'      , $adwares_->getID() );
    $access->setData( 'owner'        , SafeString( $_GET[ 'id' ] ) );
    $access->setData( 'useragent'    , SafeString( getenv( 'HTTP_USER_AGENT' ) ) );
    $access->setData( 'referer'      , SafeString( getenv( 'HTTP_REFERER' ) ) );
    $access->setData( 'state'        , $ACTIVE_NONE );
    $access->setData( 'utn'          , MobileUtil::getMobileID() );

    $access = $access->register();

    UpdateCookie( $adwares_ , $cookieID );

    return $access;
}

/**
 * AFADセッションIDを取得する
 *
 * @param RecordModel $adwares_ 広告レコード
 * @return string|null AFADセッションID、存在しない場合はnull
 */
function GetAFADSessionId( $adwares_ )
{
    // AFAD連携が有効かチェック
    if( !$adwares_->getData( 'afad_enabled' ) ) {
        return null;
    }

    // パラメータ名を取得（デフォルト: afad_sid）
    $paramName = $adwares_->getData( 'afad_param_name' );
    if( !$paramName ) {
        $paramName = 'afad_sid';
    }

    // GETパラメータから取得
    if( isset( $_GET[ $paramName ] ) && !empty( $_GET[ $paramName ] ) ) {
        return SafeString( $_GET[ $paramName ] );
    }

    return null;
}
```

### 5.2 add.php の拡張

**ファイル:** `/home/user/ASP-ORKA/add.php`

**変更箇所:** `AddSuccessReward()` 関数の最後に追加

**変更内容:**

```php
function AddSuccessReward( $adwares_ , $access_ )
{
    global $terminal_type;
    global $ACTIVE_NONE;
    global $ACTIVE_ACTIVATE;
    global $ADWARES_AUTO_ON;

    $nUser  = new RecordModel( 'nUser' , $access_->getData( 'owner' ) );
    $sales  = GetSales( $_GET[ 'from' ] , $_GET[ 'from_sub' ] );
    $reward = GetReward( $adwares_ , $nUser , $sales );

    if( 'secretAdwares' == $adwares_->getType() )
    {
        $users = $adwares_->getData( 'open_user' );
        if( FALSE === strpos( $users , $nUser->getID() ) )
            return;
    }

    $pay = new FactoryModel( 'pay' );
    $pay->setData( 'access_id'    , $access_->getID() );
    $pay->setData( 'ipaddress'    , getenv( 'REMOTE_ADDR' ) );
    $pay->setData( 'cookie'       , $access_->getData( 'cookie' ) );
    $pay->setData( 'owner'        , $nUser->getID() );
    $pay->setData( 'adwares_type' , $adwares_->getType() );
    $pay->setData( 'adwares'      , $adwares_->getID() );
    $pay->setData( 'cost'         , $reward );
    $pay->setData( 'tier1_rate'   , SystemUtil::getSystemData( 'child_per' ) );
    $pay->setData( 'tier2_rate'   , SystemUtil::getSystemData( 'grandchild_per' ) );
    $pay->setData( 'tier3_rate'   , SystemUtil::getSystemData( 'greatgrandchild_per' ) );
    $pay->setData( 'sales'        , $sales );
    $pay->setData( 'froms'        , SafeString( $_GET[ 'from' ] ) );
    $pay->setData( 'froms_sub'    , SafeString( $_GET[ 'from_sub' ] ) );
    $pay->setData( 'state'        , 0 );
    $pay->setData( 'utn'          , SafeString( MobileUtil::GetMobileID() ) );
    $pay->setData( 'useragent'    , SafeString( getenv( 'HTTP_USER_AGENT' ) ) );
    $pay->setData( 'continue_uid' , SafeString( $_GET[ 'uid' ] ) );

    if( $ADWARES_AUTO_ON == $adwares_->getData( 'auto' ) )
    {
        $pay->setData( 'state' , $ACTIVE_ACTIVATE );
        $pay = $pay->register();

        $payDB = $pay->getDB();
        addPay( $nUser->getID() , $reward , $payDB , $pay->getRecord() , $tier );

        $currentReward = $adwares_->getData( 'money_count' );
        $currentClick  = $adwares_->getData( 'pay_count' );

        $adwares_->setData( 'money_count' , $currentReward + $reward );
        $adwares_->setData( 'pay_count' , $currentClick + 1 );
        $adwares_->update();

        sendPayMail( $pay->getRecord() , 'pay' );
    }
    else
    {
        $pay = $pay->register();
        sendDisabledPayMail( $pay->getRecord() , 'pay' );
    }

    if( !IsEnoughBudget( $adwares_ ) )
    {
        $adwares_->setData( 'open' , false );
        $adwares_->update();
    }

    updateRank( $nUser->getID() );

    // ★★★ AFAD連携: ポストバック送信 ★★★
    SendAFADPostback( $adwares_ , $access_ , $pay , $sales );
}

/**
 * AFADにポストバックを送信する
 *
 * @param RecordModel $adwares_ 広告レコード
 * @param RecordModel $access_ アクセスレコード
 * @param RecordModel $pay_ 成果レコード
 * @param int $sales_ 売上金額
 */
function SendAFADPostback( $adwares_ , $access_ , $pay_ , $sales_ )
{
    try {
        // AFAD連携が有効かチェック
        if( !$adwares_->getData( 'afad_enabled' ) ) {
            return;
        }

        // AFADセッションIDを取得
        $afadSessionId = $access_->getData( 'afad_session_id' );
        if( !$afadSessionId ) {
            return; // AFADセッションIDがない場合は送信しない
        }

        // ポストバックURLを取得
        $postbackUrl = $adwares_->getData( 'afad_postback_url' );
        if( !$postbackUrl ) {
            throw new RuntimeException( 'AFAD連携が有効ですがポストバックURLが設定されていません' );
        }

        // パラメータを構築
        $params = array();

        // gidがURLに含まれていない場合は追加
        if( strpos( $postbackUrl , 'gid=' ) === false ) {
            $gid = $adwares_->getData( 'afad_gid' );
            if( $gid ) {
                $params['gid'] = $gid;
            }
        }

        // 必須パラメータ
        $params['af'] = $afadSessionId;

        // 任意パラメータ
        if( $_GET['uid'] ) {
            $params['uid'] = $_GET['uid'];
        }
        if( $_GET['uid2'] ) {
            $params['uid2'] = $_GET['uid2'];
        }
        if( $sales_ > 0 ) {
            $params['amount'] = $sales_;
        }

        // 承認ステータス（自動承認の場合は「承認待ち」）
        global $ADWARES_AUTO_ON;
        if( $ADWARES_AUTO_ON == $adwares_->getData( 'auto' ) ) {
            $params['Status'] = 1; // 承認待ち
        }

        // URLを構築
        $separator = ( strpos( $postbackUrl , '?' ) === false ) ? '?' : '&';
        $fullUrl = $postbackUrl . $separator . http_build_query( $params );

        // HTTPリクエスト送信
        $result = SendHTTPRequest( $fullUrl );

        // ログに記録
        LogAFADPostback( $pay_->getID() , $access_->getID() , $afadSessionId , $fullUrl , $result );

    } catch( Exception $e ) {
        // エラーログ出力
        $errorManager = new ErrorManager();
        $errorMessage = 'AFAD Postback Error: ' . $errorManager->GetExceptionStr( $e );
        $errorManager->OutputErrorLog( $errorMessage );

        // エラーでも処理は継続（成果記録は成功しているため）
    }
}

/**
 * HTTPリクエストを送信する（非同期）
 *
 * @param string $url リクエストURL
 * @return array 結果 ['status' => HTTPステータスコード, 'body' => レスポンスボディ, 'error' => エラーメッセージ]
 */
function SendHTTPRequest( $url )
{
    $result = array(
        'status' => null,
        'body' => null,
        'error' => null
    );

    try {
        $ch = curl_init();

        curl_setopt( $ch , CURLOPT_URL , $url );
        curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $ch , CURLOPT_TIMEOUT , 10 );
        curl_setopt( $ch , CURLOPT_CONNECTTIMEOUT , 5 );
        curl_setopt( $ch , CURLOPT_FOLLOWLOCATION , true );
        curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , true );
        curl_setopt( $ch , CURLOPT_SSL_VERIFYHOST , 2 );

        $response = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
        $error = curl_error( $ch );

        curl_close( $ch );

        $result['status'] = $httpCode;
        $result['body'] = $response;

        if( $error ) {
            $result['error'] = $error;
        }

    } catch( Exception $e ) {
        $result['error'] = $e->getMessage();
    }

    return $result;
}

/**
 * AFADポストバック送信ログを記録する
 *
 * @param string $payId 成果ID
 * @param string $accessId アクセスID
 * @param string $afadSessionId AFADセッションID
 * @param string $postbackUrl 送信したURL
 * @param array $result SendHTTPRequest()の結果
 */
function LogAFADPostback( $payId , $accessId , $afadSessionId , $postbackUrl , $result )
{
    try {
        $db = GMlist::getDB( 'afad_postback_log' );
        $rec = $db->getNewRecord();

        $db->setData( $rec , 'id' , md5( time() . $payId . rand() ) );
        $db->setData( $rec , 'pay_id' , $payId );
        $db->setData( $rec , 'access_id' , $accessId );
        $db->setData( $rec , 'afad_session_id' , $afadSessionId );
        $db->setData( $rec , 'postback_url' , $postbackUrl );
        $db->setData( $rec , 'http_status' , $result['status'] );
        $db->setData( $rec , 'response_body' , substr( $result['body'] , 0 , 1000 ) );
        $db->setData( $rec , 'error_message' , $result['error'] );
        $db->setData( $rec , 'sent_at' , time() );
        $db->setData( $rec , 'retry_count' , 0 );

        $db->addRecord( $rec );

    } catch( Exception $e ) {
        // ログ記録失敗は無視（成果送信は完了しているため）
    }
}
```

### 5.3 CheckQuery() の拡張

**ファイル:** `/home/user/ASP-ORKA/link.php`

**変更箇所:** `CheckQuery()` 関数

**変更内容:**

```php
function CheckQuery()
{
    ConceptCheck::IsEssential( $_GET , Array( 'adwares' , 's_adwares' ) , 'or' );
    ConceptCheck::IsNotNull( $_GET , Array( 'adwares' , 's_adwares' ) , 'or' );
    ConceptCheck::IsScalar( $_GET , Array( 'adwares' , 'id' , 's_adwares' , 'url' , 'afad_sid' ) ); // ★ afad_sid追加
    ConceptCheck::IsScalar( $_COOKIE , Array( 'adwares_cookie' ) );
}
```

### 5.4 管理画面での設定項目追加

**対象:** 広告編集画面（adwares編集フォーム）

**追加する設定項目:**

1. **AFAD連携を有効にする** (チェックボックス) → `afad_enabled`
2. **AFADポストバックURL** (テキスト) → `afad_postback_url`
   - 例: `https://ac.afad-domain.jp/xxxxxxxxxxxxx/ac/`
3. **AFAD広告グループID** (テキスト) → `afad_gid`
   - ※ポストバックURLに含まれている場合は不要
4. **AFADセッションIDパラメータ名** (テキスト、デフォルト: `afad_sid`) → `afad_param_name`

---

## 6. 実装手順

### Phase 1: データベース準備

1. ✅ `access`テーブルに`afad_session_id`カラム追加
2. ✅ `adwares`テーブルにAFAD連携設定カラム追加
3. ✅ `afad_postback_log`テーブル作成

### Phase 2: link.php 拡張

1. ✅ `CheckQuery()` に `afad_sid` 追加
2. ✅ `GetAFADSessionId()` 関数実装
3. ✅ `AddAccess()` でAFADセッションID保存

### Phase 3: add.php 拡張

1. ✅ `SendAFADPostback()` 関数実装
2. ✅ `SendHTTPRequest()` 関数実装
3. ✅ `LogAFADPostback()` 関数実装
4. ✅ `AddSuccessReward()` にポストバック送信処理追加

### Phase 4: 管理画面

1. ✅ 広告編集フォームにAFAD連携設定項目追加
2. ✅ バリデーション実装

### Phase 5: テスト

1. ✅ クリック時のAFADセッションID保存テスト
2. ✅ 成果発生時のポストバック送信テスト
3. ✅ ログ記録確認

---

## 7. テスト計画

### 7.1 単体テスト

**テスト1: AFADセッションID保存**

```
入力:
GET /link.php?adwares=123&id=456&afad_sid=TEST_SESSION_123

期待結果:
- accessテーブルに新レコード作成
- access.afad_session_id = 'TEST_SESSION_123'
```

**テスト2: ポストバック送信**

```
前提条件:
- access.afad_session_id = 'TEST_SESSION_123'
- adwares.afad_enabled = 1
- adwares.afad_postback_url = 'https://example.com/postback'

入力:
GET /add.php?aid=XXX&check=YYY&cost=1000

期待結果:
- AFADへHTTPリクエスト送信
- URL: https://example.com/postback?af=TEST_SESSION_123&amount=1000&Status=1
- afad_postback_logテーブルにログ記録
```

### 7.2 統合テスト

**シナリオ: エンドツーエンド**

1. ユーザーがAFAD広告をクリック
   - `GET /link.php?adwares=123&id=456&afad_sid=REAL_SESSION_ABC`
2. CATSがAFADセッションIDを保存
3. 広告主サイトで成果発生
   - `GET /add.php?aid=XXX&check=YYY&cost=5000&uid=ORDER_999`
4. CATSがAFADにポストバック送信
   - `GET https://ac.afad.jp/xxx/ac/?gid=100&af=REAL_SESSION_ABC&amount=5000&uid=ORDER_999&Status=1`
5. AFAD側で成果受信確認

### 7.3 エラーハンドリングテスト

**テスト3: AFADセッションIDなしで成果発生**

```
前提条件:
- access.afad_session_id = NULL

期待結果:
- 成果は正常に記録される
- AFADへのポストバックは送信されない（スキップ）
```

**テスト4: AFADポストバックURL未設定**

```
前提条件:
- adwares.afad_enabled = 1
- adwares.afad_postback_url = NULL

期待結果:
- エラーログに警告出力
- 成果は正常に記録される
```

**テスト5: AFADサーバーエラー**

```
前提条件:
- AFADサーバーが500エラーを返す

期待結果:
- afad_postback_log.http_status = 500
- afad_postback_log.error_message に内容記録
- 成果は正常に記録される
```

---

## 8. 運用・監視

### 8.1 ログ確認

**AFADポストバック送信ログ確認:**

```sql
-- 最近の送信ログ
SELECT * FROM afad_postback_log
ORDER BY sent_at DESC
LIMIT 100;

-- エラーが発生した送信
SELECT * FROM afad_postback_log
WHERE http_status IS NULL OR http_status >= 400
ORDER BY sent_at DESC;

-- 特定のセッションIDの追跡
SELECT * FROM afad_postback_log
WHERE afad_session_id = 'XXX';
```

### 8.2 リトライ処理（将来の拡張）

現在の実装ではリトライ機能はありませんが、将来的に以下を実装可能：

1. `afad_postback_log.http_status IS NULL` のレコードを定期的に再送
2. `retry_count < 5` の場合のみリトライ
3. 指数バックオフ（2秒、4秒、8秒、16秒、32秒）

---

## 9. まとめ

### 実装のポイント

✅ **シンプル:** HTTPポストバック方式、既存コードを最小限の変更で拡張
✅ **堅牢:** エラーが発生しても成果記録は継続
✅ **追跡可能:** すべてのポストバック送信をログに記録
✅ **柔軟:** 広告ごとにAFAD連携のON/OFF可能

### 工数見積もり

- Phase 1 (DB): 0.5日
- Phase 2 (link.php): 0.5日
- Phase 3 (add.php): 1日
- Phase 4 (管理画面): 1日
- Phase 5 (テスト): 1日

**合計: 4日**

---

## 10. FAQ

**Q1: WebSocketは使わないのですか？**
A1: AFAD仕様書ではHTTPポストバック方式が定義されているため、WebSocketは不要です。

**Q2: AFADセッションIDがない場合はどうなりますか？**
A2: 従来通りの成果記録のみ行い、AFADへのポストバックはスキップされます。

**Q3: ポストバック送信に失敗した場合は？**
A3: 成果は正常に記録され、失敗ログが`afad_postback_log`に記録されます。将来的にリトライ機能を追加可能です。

**Q4: 複数のAFADと連携できますか？**
A4: はい。広告ごとに異なるポストバックURLを設定できます。

---

**以上**
