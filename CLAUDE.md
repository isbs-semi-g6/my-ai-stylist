# CLAUDE.md

このファイルは、このリポジトリで作業する際にClaude Code (claude.ai/code) に対するガイダンスを提供します。

## プロダクト概要

my-ai-stylistは、PHPとPostgreSQLを用いたAI駆動のファッションスタイリングアプリケーションです。ユーザーが所有している服の情報をデータベースに保管し、その服のデータと現在の位置情報、天気情報を元に、**Gemini API**を使用して最適な服装を提案します。

### 主な機能
- ワードローブ管理：ユーザーの手持ちの服を画像と詳細情報とともに登録
- AI服装提案：位置情報と天気情報を取得し、Gemini APIに問い合わせて今日の服装を決定
- 服装履歴：過去のAI提案を閲覧

技術スタック：PHPバックエンド、PostgreSQLデータベース、HTML/CSS/JavaScript（Semantic UIフレームワーク）フロントエンド

## データベースセットアップ

データベーススキーマは `schema/schema.sql` に定義されています。主要テーブル：

- `users` - ユーザー認証（username, email, password_hash）
- `images` - アップロードされた服の画像
- `clothes` - 詳細な服のメタデータ（garment_type, color, fabric, pattern, season, neckline, sleeve_length）
- `ask_result` - AI相談結果（天気/気温/用途のデータを含む）
- `ask_result_items` - 服装提案内の個別アイテム

**重要**: すべてのデータベーステーブルは `ai_stylist_` プレフィックスを使用します（例：`ai_stylist_users`, `ai_stylist_clothes`）。

PHPファイル全体で使用されるデータベース接続パターン：
```php
$dbconn = pg_connect("host=localhost dbname=xxx user=xxx password=xxx");
```

## アプリケーションフロー

1. **ユーザー登録** (`regform.html` → `regform.php`)
   - ユーザー名、メールアドレス、パスワード（確認用含む）の登録フォーム
   - パスワードは `password_hash()` でPASSWORD_BCRYPTを使ってハッシュ化
   - 登録前にユーザー名の重複チェックを実施

2. **ログイン** (`login.html` → `registar-clothes.php`)
   - ログインフォームは `registar-clothes.php` にPOST
   - `password_verify()` を使用した認証
   - セッション変数の設定：`$_SESSION['id']` と `$_SESSION['username']`

3. **服の登録** (`registar-clothes.php`)
   - ログインハンドラと服の登録ページを兼ねる
   - `./uploads/` ディレクトリへの画像アップロードに対応
   - トランザクションを使用した画像と服データのアトミックな挿入
   - 画像ファイルは `{time}_{pid}.{ext}` の命名規則で保存
   - ユーザーのアップロード画像をグリッド表示

4. **AI相談** (`question.html`)
   - ユーザー設定を収集するフロントエンドフォーム：性別、年齢、用途、スタイル
   - **実装予定**: 位置情報と天気情報を取得し、Gemini APIに送信して服装提案を取得
   - 現在はプレースホルダーのJavaScript；AI統合が必要
   - 結果は `ask_result` と `ask_result_items` テーブルに保存される必要あり

5. **服装履歴** (`outfit-history.html`)
   - 過去のAI服装提案を表示
   - 現在は空のデータ配列；バックエンド統合が必要
   - itemtype、color、material、shape、name、season、genreを含む詳細なアイテム情報を表示

6. **ホームページ** (`homepage1-1.html`, `homepage1-2.html`)
   - Weather Coordinateブランディングのマーケティング/ランディングページ
   - ピンク/パステルカラーの美観（#ffe8f3, #ffb7d9, #ff6fa8）

## Gemini API統合（実装予定）

服装提案機能では以下の流れを実装する必要があります：

1. ユーザーの位置情報を取得（JavaScriptのGeolocation API等）
2. 位置情報から天気情報を取得（天気APIを使用）
3. データベースからユーザーの服データを取得
4. 服のデータ、天気情報、気温、ユーザーの好み・用途をGemini APIに送信
5. Gemini APIからの提案を解析し、`ask_result` および `ask_result_items` テーブルに保存
6. 提案結果をユーザーに表示

## セキュリティ上の注意事項

- `registar-clothes.php:5` と `regform.php:23` にはプレースホルダーのデータベース認証情報が含まれており、設定が必要
- ほとんどのPHPコードはプリペアドステートメント（`pg_query_params`）を使用してSQLインジェクションを防止
- **重大**: `regform.php:22,30-31` は文字列連結でSQLクエリを構築しており、SQLインジェクションの脆弱性がある。`pg_query_params()` を使用するように変換する必要あり
- ファイルアップロードはMIMEタイプを検証しているが、ファイル拡張子も検証すべき
- アップロードファイルディレクトリには適切な権限が必要（現在は777を想定）
- Gemini APIキーは環境変数に保存し、コードにハードコードしない

## 開発上の注意点

- ビルドシステムや依存関係管理がない（package.json、composer.jsonなし）
- フロントエンドはCDNホスト版のSemantic UI（v2.4.2とv2.5.0）を使用
- PHP セッション管理を認証状態に使用
- PostgreSQL固有の関数を全体で使用（`pg_connect`, `pg_query_params`等）
- 画像アップロードは `./uploads/` ディレクトリの存在と書き込み権限を期待
- `registar-clothes.php` のトランザクション処理は明示的なBEGIN/COMMIT/ROLLBACKを使用

## ファイル構成

- ルートのHTMLファイル：異なるアプリケーションフローのフロントエンドページ
- `*.php`：PHPロジックとHTMLテンプレートを組み合わせたバックエンドハンドラ
- `schema/`：データベーススキーマ定義
- `uploads/`：ユーザーがアップロードした服の画像（手動で作成する必要あり）

## テスト

現在、リポジトリに自動テストはありません。手動テストのワークフロー：

1. PostgreSQLデータベースをセットアップし、`schema/schema.sql` を実行
2. PHPファイル内のデータベース認証情報を更新
3. 書き込み権限付きで `uploads/` ディレクトリを作成
4. 登録 → ログイン → 服のアップロード → AI相談のフローをテスト
5. ページ遷移間のセッション永続性を確認
6. 様々な画像フォーマットでファイルアップロードをテスト
7. （実装後）位置情報取得、天気API、Gemini APIの統合をテスト
