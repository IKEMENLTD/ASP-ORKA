# クイックスタートガイド

このガイドに従って、アフィリエイトシステムプロを Render + Supabase に移行します。

---

## 📋 準備済みの項目

### ✓ 完了したこと

- [x] **システムバックアップ作成**
  - 場所: `/mnt/c/Users/ooxmi/Downloads/affiliate-pro-backup-20251009-152154`
  - サイズ: 3.2M
  - ファイル数: 615

- [x] **ドキュメント作成**
  - `SYSTEM_ANALYSIS.md` - システム完全解析
  - `MIGRATION_PLAN.md` - 移行計画書
  - `TECHNICAL_GUIDE.md` - 技術実装ガイド
  - `PHASE0_SETUP.md` - Phase 0 セットアップガイド

- [x] **設定ファイル準備**
  - `.env.example` - 環境変数テンプレート
  - `.gitignore` - Git除外設定

---

## 🚀 次のステップ

### Phase 0: 準備 (今すぐ実行可能)

**所要時間**: 1-2時間

#### ステップ1: Supabase プロジェクト作成

1. https://supabase.com にアクセス
2. GitHubアカウントでサインアップ
3. 新しいプロジェクトを作成
4. 接続情報をメモ

**詳細手順**: `PHASE0_SETUP.md` を参照

#### ステップ2: SendGrid セットアップ

1. https://sendgrid.com にアクセス
2. アカウント作成
3. API キーを取得

**詳細手順**: `PHASE0_SETUP.md` を参照

#### ステップ3: Render アカウント作成

1. https://render.com にアクセス
2. GitHubアカウントでサインアップ

**詳細手順**: `PHASE0_SETUP.md` を参照

#### ステップ4: 環境変数設定

```bash
# .env ファイル作成
cp .env.example .env

# 環境変数を編集
notepad .env  # Windows
# または
nano .env     # Linux/WSL
```

**詳細手順**: `PHASE0_SETUP.md` を参照

---

### Phase 1: 文字コード変換

**開始前の確認**:
- [ ] Phase 0 完了
- [ ] バックアップ確認
- [ ] .env ファイル作成完了

**実行**:

```bash
# 文字コード変換スクリプトを作成
# (Claudeに "Phase 1を開始" と伝えてください)
```

---

## 📚 ドキュメント一覧

| ドキュメント | 内容 | 対象 |
|------------|------|------|
| `SYSTEM_ANALYSIS.md` | 既存システムの完全解析 | すべて |
| `MIGRATION_PLAN.md` | 移行計画・フェーズ詳細 | プロジェクトマネージャー |
| `TECHNICAL_GUIDE.md` | 技術実装ガイド | 開発者 |
| `PHASE0_SETUP.md` | Phase 0セットアップ手順 | 今すぐ実行 |
| `QUICK_START.md` | このファイル | すべて |

---

## 🎯 移行フェーズ概要

```
Phase 0: 準備 (1-2日)           ← 今ここ
  ↓
Phase 1: 文字コード変換 (2-3日)
  ↓
Phase 2: データベース移行 (3-5日)
  ↓
Phase 3: ファイルストレージ移行 (2-3日)
  ↓
Phase 4: 外部サービス統合 (2-3日)
  ↓
Phase 5: Render デプロイ (2-3日)
  ↓
Phase 6-7: テスト・本番移行 (4-6日)
```

**総所要期間**: 15-25日

---

## ✅ Phase 0 チェックリスト

作業を開始する前に、以下を確認してください:

### アカウント作成
- [ ] Supabase アカウント作成完了
- [ ] Supabase プロジェクト作成完了
- [ ] SendGrid アカウント作成完了
- [ ] SendGrid API キー取得完了
- [ ] Render アカウント作成完了

### 接続情報取得
- [ ] SUPABASE_URL
- [ ] SUPABASE_ANON_KEY
- [ ] SUPABASE_SERVICE_ROLE_KEY
- [ ] SUPABASE_DB_HOST
- [ ] SUPABASE_DB_PASS
- [ ] SENDGRID_API_KEY

### ローカル設定
- [ ] .env ファイル作成完了
- [ ] セキュリティキー生成完了
- [ ] .gitignore 確認完了

---

## 🆘 サポート

### よくある質問

**Q: Supabase の無料プランで十分ですか？**

A: 開発・テスト段階では十分です。本番運用時は以下を検討:
- 月間リクエスト数が多い場合: Pro プラン ($25/月)
- データベースサイズが8GB超: Pro プラン

**Q: Render の無料プランの制限は？**

A: 以下の制限があります:
- 15分間アクセスがないと自動スリープ
- 起動に数秒かかる
- 月750時間まで

本番運用時は Starter プラン ($7/月) を推奨

**Q: SendGrid の無料枠を超えたら？**

A: 月100通を超える場合:
- SendGrid 有料プラン ($19.95/月 for 40,000 emails)
- または Resend ($20/月 for 10,000 emails)

### トラブルシューティング

問題が発生した場合:

1. `TECHNICAL_GUIDE.md` のトラブルシューティング章を参照
2. `PHASE0_SETUP.md` の該当セクションを再確認
3. Claudeに質問してください

---

## 📞 次のアクション

Phase 0 のセットアップが完了したら:

**Claudeに以下のように伝えてください**:

```
Phase 0 完了しました。Phase 1を開始してください。
```

または

```
C
```

---

**最終更新**: 2025-01-XX
**現在のフェーズ**: Phase 0 - 準備
**次のフェーズ**: Phase 1 - 文字コード変換
