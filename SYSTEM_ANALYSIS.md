# アフィリエイトシステムプロ - 完全システム解析書

## システム概要

**バージョン**: 1.6.25
**開発年**: 2010年頃
**総行数**: 38,509行以上
**PHPファイル数**: 138ファイル
**文字エンコーディング**: Shift-JIS
**データベース対応**: SQLite / MySQL / PostgreSQL (抽象化済み)

## アーキテクチャ全体像

### 3層アーキテクチャ

```
┌─────────────────────────────────────┐
│   プレゼンテーション層               │
│   - 22個のルートPHPファイル          │
│   - ccProc テンプレートエンジン      │
│   - Template管理システム             │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│   ビジネスロジック層                 │
│   - 10個のLogicクラス                │
│   - SystemUtil (global.php)          │
│   - アフィリエイト報酬計算           │
│   - 3段階ティア報酬システム          │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│   データアクセス層                   │
│   - GUIManager (ORM的役割)           │
│   - FactoryModel / RecordModel       │
│   - TableModel                       │
│   - SQLDatabase抽象化 (2,960行)      │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│   データ層                           │
│   - 21個のCSVテーブル (tdb/)         │
│   - LST定義ファイル (lst/)           │
│   - SQLite/MySQL/PostgreSQL対応      │
└─────────────────────────────────────┘
```

---

## ファイル構造

### ルートディレクトリ (22個のPHPファイル)

| ファイル名 | 行数 | 主要機能 |
|-----------|------|---------|
| tool.php | 2,222 | データベース管理ツール (CSV/SQLインポート・エクスポート) |
| link.php | 554 | クリック追跡・リダイレクト・報酬記録 |
| add.php | 603 | コンバージョン記録 (広告主トリガー型) |
| continue.php | 264 | 成果報酬追跡 (売上ベース) |
| regist.php | 176 | ユーザー登録 (多段階フォーム) |
| edit.php | 236 | レコード編集 |
| report.php | 222 | CSVレポート生成 |
| reminder.php | 218 | パスワードリマインダー |
| return.php | 192 | 報酬返金処理 |
| search.php | 116 | 検索機能 |
| login.php | 107 | 認証・ログイン |
| quick.php | 99 | クイック操作 |
| info.php | 89 | 詳細情報表示 |
| multimail_send.php | 84 | 一斉メール送信 |
| activate.php | 79 | アクティベーション処理 |
| other.php | 79 | その他機能 |
| api.php | 69 | API エンドポイント |
| unlock.php | 69 | アカウントロック解除 |
| page.php | 65 | ページ表示 |
| log_delete.php | 58 | ログ削除 |
| index.php | 50 | トップページ |
| report_api.php | 31 | レポートAPI |

### フレームワークコア (include/)

| ファイル名 | 行数 | 役割 |
|-----------|------|------|
| ccProc.php | 2,250 | テンプレートエンジン本体 |
| GUIManager.php | 1,100+ | ORM・UIレンダリング |
| Util.php | 775 | システムユーティリティ |
| Template.php | 168 | テンプレートファイル管理 |
| Mail.php | 249 | メール送信 |
| Command.php | 200 | テンプレートコマンド実装 |
| GMList.php | 48 | GUIManagerキャッシュ |

### データベース抽象化層 (include/base/)

| ファイル名 | 行数 | 機能 |
|-----------|------|------|
| SQLDatabase.php | 2,960 | SQL抽象化 (MySQL/PostgreSQL/SQLite) |
| Database.php | 298 | データベースインターフェース |
| Initialize.php | - | 初期化処理 |
| SystemBase.php | - | システム基底クラス |
| WebAPIConnection.php | - | Web API接続 |

### カスタムロジック (custom/logic/)

| ファイル名 | 役割 |
|-----------|------|
| AccessLogic.php | アクセス追跡ロジック |
| AdwaresLogic.php | 広告管理ロジック |
| NUserLogic.php | ユーザー管理ロジック |
| PayLogic.php | 報酬支払いロジック |
| ReturnssLogic.php | 返金処理ロジック |
| SalesLogic.php | 売上ランク管理 |
| MailLogic.php | メール処理 |
| SecretAdwaresLogic.php | 非公開広告ロジック |
| AutoLoginLogic.php | 自動ログイン |
| TableLogic.php | テーブル操作ロジック |

### カスタムモデル (custom/model/)

- **FactoryModel.php**: モデルファクトリー
- **RecordModel.php**: レコード操作モデル
- **TableModel.php**: テーブル操作モデル

### 設定ファイル (custom/)

| ファイル名 | 内容 |
|-----------|------|
| conf.php | システム定数定義 |
| global.php | グローバル関数・ティア報酬計算 (605行) |
| version.php | バージョン情報 (1.6.25) |

### 拡張設定 (custom/extends/)

- sqlConf.php - SQL接続設定
- mobileConf.php - モバイル対応設定
- tableConf.php - テーブル設定
- formConf.php - フォーム設定
- systemConf.php - システム設定
- sslConf.php - SSL設定
- その他10ファイル

---

## データモデル (21テーブル)

### コアテーブル

#### 1. **nuser** (ユーザーマスタ)
```
id, name, zip1, zip2, adds, add_sub, tel, fax, url, mail,
bank_code, bank, branch_code, branch, bank_type, number, bank_name,
parent, grandparent, greatgrandparent, pass, terminal,
activate, pay, tier, rank, personal_rate, magni,
mail_reception, is_mobile, limits, regist, logout
```
**機能**: アフィリエイター情報・銀行口座・3段階親子関係・報酬累計

#### 2. **admin** (管理者マスタ)
```
id, name, mail, pass, rank, regist, update, login, logout
```
**機能**: 管理者アカウント

#### 3. **adwares** (広告マスタ)
```
id, comment, ad_text, category, banner, banner2, banner3,
banner_m, banner_m2, banner_m3, url, url_m, url_over, url_users,
name, money, ad_type, click_money, continue_money, continue_type,
limits, limit_type, money_count, pay_count, click_money_count,
continue_money_count, span, span_type, use_cookie_interval,
pay_span, pay_span_type, auto, click_auto, continue_auto,
check_type, open, regist
```
**機能**:
- クリック報酬・成果報酬設定
- PC/モバイル別バナー (3種類ずつ)
- 予算上限管理
- Cookie間隔設定
- 自動承認設定

#### 4. **access** (アクセスログ)
```
id, ipaddress, cookie, adwares_type, adwares, owner,
useragent, referer, state, utn, regist
```
**機能**: クリック追跡・Cookie記録・端末ID記録

#### 5. **pay** (成果報酬テーブル)
```
id, access_id, ipaddress, cookie, owner, adwares_type, adwares,
cost, tier1_rate, tier2_rate, tier3_rate, sales, froms, froms_sub,
state, is_notice, utn, useragent, continue_uid, regist
```
**機能**:
- コンバージョン記録
- 3段階ティア報酬率記録
- 承認/非承認状態管理
- メール通知フラグ

#### 6. **click_pay** (クリック報酬テーブル)
```
id, access_id, owner, adwares_type, adwares, cost,
tier1_rate, tier2_rate, tier3_rate, state, is_notice, regist
```
**機能**: クリック課金型報酬記録

#### 7. **continue_pay** (継続報酬テーブル)
```
id, pay_id, owner, adwares_type, adwares, cost,
tier1_rate, tier2_rate, tier3_rate, sales, state, is_notice, regist
```
**機能**: 継続課金・サブスクリプション報酬

#### 8. **tier** (ティア報酬履歴)
```
id, tier_id, owner, tier, adwares, cost,
tier1, tier2, tier3, regist
```
**機能**: 3段階の紹介報酬履歴

#### 9. **sales** (ランクマスタ)
```
id, name, rate, lot, sales
```
**機能**: 売上ランク定義・報酬倍率設定

#### 10. **log_pay** (報酬変更ログ)
```
id, pay_type, pay_id, nuser_id, operator, cost, state, action, regist
```
**機能**: 報酬承認/拒否/返金のログ

#### 11. **returnss** (返金テーブル)
```
id, owner, cost, state, regist
```
**機能**: 報酬返金管理

### マスタデータテーブル

#### 12. **category** (カテゴリマスタ)
```
id, name, regist
```

#### 13. **area** (地域マスタ)
```
id, name
```

#### 14. **prefectures** (都道府県マスタ)
```
id, area_id, name, name_kana
```

#### 15. **zenginkyo** (全銀協マスタ)
```
id, name_kana, bank_code, bank_name_kana,
branch_code, branch_name_kana, bank_type, number
```
**機能**: 銀行・支店コード検索

### セキュリティ・管理テーブル

#### 16. **blacklist** (ブラックリスト)
```
id, blacklist_mode, blacklist_value, memo
```
**機能**: IP/Cookie/リファラーブロック

#### 17. **invitation** (招待メール)
```
id, owner, mail, message, regist
```

#### 18. **multimail** (一斉メール)
```
id, sub, main, receive_id
```

### システムテーブル

#### 19. **system** (システム設定)
```
id, uuid, home, mail_address, mail_name, login_id_manage,
site_title, keywords, description, main_css,
child_per, grandchild_per, greatgrandchild_per,
users_returnss, exchange_limit, nuser_default_activate,
nuser_accept_admin, adwares_pass, sales_auto,
send_mail_admin, send_mail_nuser, send_mail_status,
access_limit, parent_limit, parent_limit_url, regist
```
**機能**:
- サイト基本設定
- ティア報酬率 (子10%、孫5%、曾孫3%)
- 承認フロー設定
- メール通知設定

#### 20. **template** (テンプレート管理)
```
id, user_type, target_type, activate, owner, label, file, regist
```
**機能**: ユーザータイプ別テンプレート管理

#### 21. **page** (ページ管理)
```
id, name, authority, open, regist
```

---

## コア機能詳細

### 1. アフィリエイトトラッキングシステム

#### クリック追跡フロー (link.php:554行)

```php
// link.php の処理フロー
1. CheckQuery() - クエリパラメータ検証
2. IsEnoughBudget() - 予算残高確認
3. IsPassageWait() - Cookie間隔チェック
4. IsThroughBlackList() - ブラックリスト判定
5. AddAccess() - accessテーブルに記録
   - IPアドレス
   - Cookie ID
   - 広告ID
   - アフィリエイターID
   - UserAgent
   - Referer
   - 端末ID (ガラケー対応)
6. AddClickReward() - click_payテーブルに報酬記録
7. UpdateCookie() - Cookie更新
8. DoRedirect() - リダイレクト実行
```

**Cookie設計**:
```php
// Cookie ID生成
$cookieID = md5(time() . getenv('REMOTE_ADDR') . rand());
// 期限: 30日間
setcookie($name, $value, time() + 60*60*24*30, '/');
```

#### コンバージョン追跡フロー (add.php:603行)

```php
// add.php の処理フロー
1. GetAccess() - Cookie/端末IDからアクセス記録検索
2. HasPay() - 既存成果の重複チェック
3. IsPassageWait() - 成果承認待機時間チェック
4. MatchReceptionMode() - 承認モード判定
5. AddSuccessReward() - payテーブルに成果記録
6. UpdateBudget() - 広告予算残高更新
7. AddTierReward() - 3段階ティア報酬計算・記録
```

### 2. 3段階ティア報酬システム

#### 報酬計算ロジック (global.php:addPay関数)

```php
/**
 * ティア報酬計算フロー
 *
 * 例: アフィリエイターDが1000円の成果発生
 * D の親: C (tier1: 10%)
 * D の祖父母: B (tier2: 5%)
 * D の曾祖父母: A (tier3: 3%)
 */
function addPay($user_id, $pay, &$pay_db, $pay_rec, &$_tierValue) {
    global $gm;

    // 1. 本人に報酬追加
    $ndb->setCalc($rec, 'pay', '+', $pay); // D に 1000円

    // 2. 親子関係取得
    $p = $ndb->getData($rec, 'parent');        // C
    $g = $ndb->getData($rec, 'grandparent');   // B
    $gg = $ndb->getData($rec, 'greatgrandparent'); // A

    // 3. 3段階それぞれに報酬配分
    for ($i = 0; $i < 3; $i++) {
        $per = $pay_db->getData($pay_rec, $pers[$i]); // tier1_rate, tier2_rate, tier3_rate
        $tpay = floor($pay * $per / 100);

        // 親に報酬追加
        $ndb->setCalc($trec, 'pay', '+', $tpay);   // C: +100円, B: +50円, A: +30円
        $ndb->setCalc($trec, 'tier', '+', $tpay); // ティア報酬専用カラムにも記録

        // tierテーブルに履歴記録
        $tdb->addRecord($tier_rec);
    }

    // 4. 売上ランク自動更新
    updateRank($user_id);
}
```

### 3. カスタムテンプレートエンジン (ccProc)

#### テンプレート構文

```html
<!--# value データ名 #-->
    データ表示

<!--# convert データ名 変換関数名 #-->
    データ変換表示 (例: nl2br, htmlspecialchars)

<!--# if 条件式 #-->
    条件が真の場合
<!--# else #-->
    条件が偽の場合
<!--# endif #-->

<!--# switch データ名 #-->
<!--# case 値 #-->
    ケース処理
<!--# endswitch #-->

<!--# readhead パーツ名 #-->
    繰り返し部分のテンプレート
<!--# readend パーツ名 #-->

<!--# command カスタムコマンド パラメータ #-->
    独自コマンド実行
```

#### テンプレート処理フロー (ccProc.php:2,250行)

```php
class ccProc {
    // 1. ファイル読み込み
    function loadFile($path) {
        $html = file_get_contents($path);
        return mb_convert_encoding($html, 'UTF-8', 'SJIS');
    }

    // 2. コマンド解析
    function commandComment($html, $rec, $partkey) {
        // <!--# コマンド パラメータ #--> を正規表現で抽出
        preg_match_all('/<!--#\s*(\w+)\s+(.+?)\s*#-->/', $html, $matches);

        // 3. コマンド実行
        foreach ($matches as $match) {
            $cmd = $match[1]; // コマンド名
            $param = explode(' ', $match[2]); // パラメータ配列

            // コマンドメソッド呼び出し
            $result = $this->{"base_".$cmd}($param, $rec);

            // 置換
            $html = str_replace($match[0], $result, $html);
        }

        return $html;
    }

    // 4. データバインディング
    function base_value($param, $rec) {
        $db = $this->getDB();
        return $db->getData($rec, $param[0]);
    }
}
```

### 4. GUIManager (ORM的役割)

#### データバインディング例

```php
// 使用例: ユーザー情報表示
$gm = SystemUtil::getGM();
$ndb = $gm['nUser']->getDB();

// レコード取得
$rec = $ndb->selectRecord('USER001');

// テンプレートにバインド
$html = $gm['nUser']->getString('user_detail.html', $rec);
print $html;
```

#### LST定義ファイルの役割

```csv
// lst/nuser.csv
id,char,8,,Const,
name,varchar,32,Null,Null,
mail,varchar,128,Null/Mail/MailDup,Null/Mail/MailDup,
pass,varchar,128,Null/ChangeFlag:ConfirmInput:pass_confirm,Null/ChangeFlag:ConfirmInput:pass_confirm,
```

**カラム定義**:
- カラム名, データ型, サイズ, 入力検証ルール, 更新検証ルール, 正規表現

**検証ルール**:
- `Null`: 必須
- `Mail`: メールアドレス形式
- `MailDup`: メール重複チェック
- `ChangeFlag:ConfirmInput:field`: 確認入力必須
- `Const`: 変更不可

### 5. データベース抽象化層

#### SQLDatabase.php (2,960行) の機能

```php
// 共通インターフェース
interface DatabaseBase {
    function getRecord($table, $index);
    function selectRecord($id);
    function addRecord(&$rec);
    function updateRecord($rec);
    function deleteRecord(&$rec);
    function searchTable(&$tbl, $name, $opp, $val);
    function sortTable(&$tbl, $name, $asc);
    function getSum($name, $table);
    // ... 他30メソッド
}

// MySQL実装
class MySQLDatabase extends SQLDatabaseBase {
    // MySQL固有のSQL生成
}

// SQLite実装
class SQLiteDatabase extends SQLDatabaseBase {
    // SQLite固有のSQL生成
}

// PostgreSQL実装
class PostgreSQLDatabase extends SQLDatabaseBase {
    // PostgreSQL固有のSQL生成
}
```

**設定切り替え** (custom/extends/sqlConf.php):
```php
$SQL_MASTER = 'MySQLDatabase';  // MySQL使用
// $SQL_MASTER = 'SQLiteDatabase';  // SQLite使用
// $SQL_MASTER = 'PostgreSQLDatabase';  // PostgreSQL使用
```

### 6. モバイル対応 (ガラケー)

#### 端末ID取得 (link.php)

```php
// docomo
if (isset($_SERVER['HTTP_X_DCMGUID'])) {
    $utn = $_SERVER['HTTP_X_DCMGUID'];
}
// au
else if (isset($_SERVER['HTTP_X_UP_SUBNO'])) {
    $utn = $_SERVER['HTTP_X_UP_SUBNO'];
}
// SoftBank
else if (isset($_SERVER['HTTP_X_JPHONE_UID'])) {
    $utn = $_SERVER['HTTP_X_JPHONE_UID'];
}
```

### 7. セキュリティ機能

#### ブラックリスト判定 (link.php:IsThroughBlackList)

```php
function IsThroughBlackList($adwares_) {
    $bdb = GMList::getDB('blacklist');
    $btable = $bdb->getTable();

    // IPアドレスチェック
    $ip_table = $bdb->searchTable($btable, 'blacklist_mode', '==', 'ipaddress');
    foreach ($ip_table as $brec) {
        $ip = $bdb->getData($brec, 'blacklist_value');
        if (strpos(getenv('REMOTE_ADDR'), $ip) !== false) {
            return false; // ブロック
        }
    }

    // Cookieチェック
    // Refererチェック
    // UserAgentチェック

    return true; // 通過
}
```

#### アカウントロック (custom/logic/AutoLoginLogic.php)

- 連続ログイン失敗でアカウントロック
- unlock.php で解除

### 8. レポート機能

#### CSV出力 (report.php)

```php
// mod_report クラスで実装
- 日付範囲指定
- ユーザー別集計
- 広告別集計
- ティア報酬集計
- CSVダウンロード
```

### 9. 管理ツール (tool.php:2,222行)

**機能**:
1. CSV ↔ SQL インポート/エクスポート
2. バックアップ/リストア
3. スキーマ変更
4. データ一括操作
5. ログイン: 別パスワード保護

---

## 設定ファイル詳細

### conf.php (主要定数)

```php
// パッケージID
define("WS_PACKAGE_ID", "affiliate_pro2");

// アクティベート状態
$ACTIVE_NONE = 1;      // 未アクティベート
$ACTIVE_ACTIVATE = 2;  // アクティベート済み
$ACTIVE_ACCEPT = 4;    // 承認済み
$ACTIVE_DENY = 8;      // 拒否

// 報酬タイプ
$PAY_TYPE_CLICK = 1;    // クリック報酬
$PAY_TYPE_NOMAL = 2;    // 成果報酬
$PAY_TYPE_CONTINUE = 4; // 継続報酬

// パス設定
$template_path = "template/pc/";
$tdb_path = "tdb/";
$lst_path = "lst/";

// 文字コード
$SYSTEM_CHARACODE = "SJIS";
```

### sqlConf.php (データベース設定)

```php
$SQL = true;
$SQL_MASTER = 'MySQLDatabase';
$SQL_SERVER = 'localhost';
$DB_NAME = 'affiliate';
$SQL_ID = 'root';
$SQL_PASS = '';
```

---

## 重要な処理フロー

### ユーザー登録フロー

```
1. regist.php?type=nUser
2. Template::getTemplate('nobody', 1, 'nUser', 'REGIST_FORM')
3. フォーム入力
4. regist.php?type=nUser&step=confirm
5. 検証 (LST定義に基づく)
6. Template::getTemplate('nobody', 1, 'nUser', 'REGIST_CHECK')
7. 確認画面
8. regist.php?type=nUser&step=complete
9. FactoryModel('nUser')->register()
   - parent/grandparent/greatgrandparent 自動設定
   - アクティベーションメール送信
10. activate.php?id=xxx&key=xxx
11. アクティベート完了
```

### 報酬発生フロー

```
1. アフィリエイターがリンク設置
   <a href="link.php?id=USER001&adwares=AD001">広告</a>

2. ユーザークリック
   → link.php 実行
   → access テーブルに記録
   → Cookie 設定
   → click_pay テーブルに報酬記録 (クリック報酬の場合)
   → リダイレクト

3. ユーザーがコンバージョン
   広告主サイトで購入完了

4. 広告主がコンバージョンタグ発火
   <img src="add.php?adwares=AD001&price=10000">

   → add.php 実行
   → Cookie/端末IDから access 検索
   → pay テーブルに成果記録
   → state = $ACTIVE_NONE (未承認)

5. 管理者が成果承認
   edit.php?type=pay&id=PAY001
   → state を $ACTIVE_ACCEPT に変更
   → addPay() 実行
     - 本人に報酬加算
     - 親に tier1 報酬加算 (10%)
     - 祖父母に tier2 報酬加算 (5%)
     - 曾祖父母に tier3 報酬加算 (3%)
   → tier テーブルに履歴記録
   → ランク自動更新

6. メール通知
   → sendPayMail() 実行
   → アフィリエイターに通知
```

---

## 技術的特徴

### 1. 完全な DB抽象化
- CSV/SQLite/MySQL/PostgreSQL を同じコードで扱える
- LST定義ファイルでスキーマ管理
- tool.php で CSV ↔ SQL 双方向変換

### 2. 柔軟なテンプレートシステム
- ユーザータイプ別テンプレート
- アクティベート状態別テンプレート
- 権限別テンプレート
- テンプレートキャッシュ

### 3. 堅牢なバリデーション
- LST定義ベースの自動検証
- 正規表現サポート
- 重複チェック
- 参照整合性チェック

### 4. 詳細なログ記録
- ADD_LOG: 新規追加ログ
- UPDATE_LOG: 更新ログ
- DELETE_LOG: 削除ログ
- log_pay: 報酬変更履歴

### 5. モバイル完全対応
- docomo/au/SoftBank 端末ID取得
- モバイル専用テンプレート
- Cookie非対応端末への対応

---

## 移行時の考慮事項

### データベース
- **現状**: MySQL (localhost)
- **移行先**: Supabase PostgreSQL
- **対応**: sqlConf.php で `$SQL_MASTER = 'PostgreSQLDatabase'` に変更可能
- **注意**: カスタム関数の互換性確認必要

### ファイルストレージ
- **現状**: ローカルファイルシステム (file/image/)
- **移行先**: Supabase Storage
- **対応**: FileBase.php の改修必要

### セッション管理
- **現状**: PHP セッション
- **移行先**: JWT or Supabase Auth
- **対応**: SystemUtil::login/logout の改修必要

### メール送信
- **現状**: PHP mail() 関数
- **移行先**: SendGrid or Resend
- **対応**: Mail.php の改修必要

### Cron処理
- **現状**: なし (手動実行想定)
- **移行先**: Render Cron Jobs
- **対応**: バッチ処理スクリプト作成

### 文字エンコーディング
- **現状**: Shift-JIS
- **移行先**: UTF-8
- **対応**: 全ファイルの文字コード変換必要

---

## まとめ

このシステムは2010年頃に開発された高度なアフィリエイトシステムで、以下の特徴を持つ:

### 強み
1. 完全なDB抽象化により PostgreSQL 移行が容易
2. 3段階ティア報酬システムの実装が完成している
3. 詳細なログ記録とトラッキング機能
4. モバイル (ガラケー) 完全対応
5. 柔軟なテンプレートシステム

### 課題
1. Shift-JIS エンコーディング (UTF-8変換必要)
2. レガシーなモバイル対応コード (現代では不要)
3. セッション管理の現代化が必要
4. ファイルアップロードをクラウドストレージに移行
5. メール送信を外部サービスに移行

### 移行戦略
1. まず文字コードをUTF-8に統一
2. sqlConf.php で PostgreSQL に切り替え
3. Supabase にデータ移行
4. 認証・セッション管理を Supabase Auth に移行
5. ファイルストレージを Supabase Storage に移行
6. メール送信を SendGrid/Resend に移行
7. Render にデプロイ

---

**作成日**: 2025-01-XX
**システムバージョン**: 1.6.25
**解析対象**: アフィリエイトシステムプロ＿システム本体003CSS未タッチ
