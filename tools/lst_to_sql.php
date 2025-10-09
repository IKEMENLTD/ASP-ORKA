<?php
/**
 * LST定義ファイルをPostgreSQL DDL(CREATE TABLE)に変換
 *
 * 使用方法:
 *   php tools/lst_to_sql.php
 *
 * 出力先: migration/schema/
 */

// 出力ディレクトリ作成
$outputDir = __DIR__ . '/../migration/schema';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// 変換対象テーブル一覧
$tables = [
    'admin', 'nuser', 'adwares', 'access', 'pay',
    'click_pay', 'continue_pay', 'tier', 'sales',
    'log_pay', 'returnss', 'category', 'area',
    'prefectures', 'zenginkyo', 'blacklist',
    'invitation', 'multimail', 'system', 'template', 'page'
];

echo "========================================\n";
echo "  LST → PostgreSQL DDL 変換\n";
echo "========================================\n\n";

$totalTables = 0;
$totalColumns = 0;

foreach ($tables as $table) {
    $lstFile = __DIR__ . "/../lst/{$table}.csv";
    $sqlFile = "{$outputDir}/{$table}.sql";

    if (!file_exists($lstFile)) {
        echo "⚠ スキップ: {$table}.csv が見つかりません\n";
        continue;
    }

    echo "変換中: {$table}...\n";

    $sql = convertLstToSql($lstFile, $table, $totalColumns);

    file_put_contents($sqlFile, $sql);
    $totalTables++;

    echo "  ✓ {$sqlFile}\n";
}

echo "\n========================================\n";
echo "  変換完了\n";
echo "========================================\n";
echo "テーブル数: {$totalTables}\n";
echo "カラム総数: {$totalColumns}\n";
echo "\n次のステップ:\n";
echo "  1. migration/schema/*.sql を確認\n";
echo "  2. migration/001_create_all_tables.sql を実行\n";
echo "\n";

/**
 * LST定義を読み込んでPostgreSQL DDLに変換
 */
function convertLstToSql($lstFile, $tableName, &$totalColumns) {
    $lines = file($lstFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $sql = "-- {$tableName} テーブル\n";
    $sql .= "DROP TABLE IF EXISTS {$tableName} CASCADE;\n";
    $sql .= "CREATE TABLE {$tableName} (\n";

    $columns = [];
    $primaryKey = '';
    $indexes = [];
    $foreignKeys = [];

    foreach ($lines as $line) {
        $cols = str_getcsv($line);
        if (count($cols) < 2) continue;

        $colName = trim($cols[0]);
        if (empty($colName)) continue;

        $colType = trim($cols[1]);
        $colSize = isset($cols[2]) ? trim($cols[2]) : '';
        $inputRule = isset($cols[3]) ? trim($cols[3]) : '';
        $updateRule = isset($cols[4]) ? trim($cols[4]) : '';

        // PostgreSQL型にマッピング
        $pgType = mapTypeToPostgreSQL($colType, $colSize);

        // 制約
        $constraints = [];

        // NOT NULL制約
        if (strpos($inputRule, 'Null') !== false || strpos($updateRule, 'Null') !== false) {
            if (strpos($inputRule, 'Null/') !== false || strpos($updateRule, 'Null/') !== false) {
                // Null/... の形式は複合ルール。厳密には解析が必要だが、基本的にはNOT NULL
                $constraints[] = 'NOT NULL';
            } elseif ($inputRule === 'Null' || $updateRule === 'Null') {
                $constraints[] = 'NOT NULL';
            }
        }

        // PRIMARY KEY
        if ($colName === 'id') {
            $primaryKey = $colName;
        }

        // UNIQUE制約 (メールアドレス等)
        if (strpos($inputRule, 'MailDup') !== false || strpos($updateRule, 'MailDup') !== false) {
            if ($colName === 'mail') {
                $constraints[] = 'UNIQUE';
            }
        }

        // DEFAULT値
        if (strpos($pgType, 'BOOLEAN') !== false) {
            $constraints[] = 'DEFAULT FALSE';
        } elseif (strpos($pgType, 'INTEGER') !== false && in_array($colName, ['activate', 'pay', 'tier', 'money_count', 'pay_count', 'click_money_count', 'continue_money_count'])) {
            $constraints[] = 'DEFAULT 0';
        }

        $columnDef = "  {$colName} {$pgType}";
        if (!empty($constraints)) {
            $columnDef .= ' ' . implode(' ', $constraints);
        }

        $columns[] = $columnDef;
        $totalColumns++;

        // 外部キー検出
        if (in_array($colName, ['parent', 'grandparent', 'greatgrandparent']) && $tableName === 'nuser') {
            $foreignKeys[] = "  FOREIGN KEY ({$colName}) REFERENCES nuser(id) ON DELETE SET NULL";
        }
        if ($colName === 'access_id' && in_array($tableName, ['pay', 'click_pay'])) {
            $foreignKeys[] = "  FOREIGN KEY ({$colName}) REFERENCES access(id) ON DELETE SET NULL";
        }
        if ($colName === 'owner' && in_array($tableName, ['pay', 'click_pay', 'continue_pay', 'returnss', 'invitation'])) {
            $foreignKeys[] = "  FOREIGN KEY ({$colName}) REFERENCES nuser(id) ON DELETE CASCADE";
        }

        // インデックス検出
        if (in_array($colName, ['cookie', 'owner', 'adwares', 'parent', 'mail'])) {
            $indexes[] = "CREATE INDEX idx_{$tableName}_{$colName} ON {$tableName}({$colName});";
        }
        if ($colName === 'state' && in_array($tableName, ['pay', 'click_pay', 'continue_pay'])) {
            $indexes[] = "CREATE INDEX idx_{$tableName}_{$colName} ON {$tableName}({$colName});";
        }
    }

    // カラム定義を結合
    $sql .= implode(",\n", $columns);

    // PRIMARY KEY追加
    if ($primaryKey) {
        $sql .= ",\n  PRIMARY KEY ({$primaryKey})";
    }

    // 外部キー追加
    if (!empty($foreignKeys)) {
        $sql .= ",\n" . implode(",\n", $foreignKeys);
    }

    $sql .= "\n);\n\n";

    // インデックス追加
    if (!empty($indexes)) {
        $sql .= "-- インデックス\n";
        $sql .= implode("\n", $indexes) . "\n\n";
    }

    return $sql;
}

/**
 * LST型をPostgreSQL型にマッピング
 */
function mapTypeToPostgreSQL($phpType, $size) {
    switch ($phpType) {
        case 'char':
            return $size ? "CHAR({$size})" : "VARCHAR(255)";

        case 'varchar':
            return $size ? "VARCHAR({$size})" : "VARCHAR(255)";

        case 'string':
        case 'text':
            return "TEXT";

        case 'int':
            return "INTEGER";

        case 'double':
            return "DOUBLE PRECISION";

        case 'boolean':
            return "BOOLEAN";

        case 'timestamp':
            // Unixタイムスタンプ(INTEGER)として保存
            return "BIGINT";

        case 'image':
        case 'file':
            // ファイルパス/URLを保存
            return "VARCHAR(255)";

        default:
            return "TEXT";
    }
}

?>
