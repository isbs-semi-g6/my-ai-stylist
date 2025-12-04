# my-ai-stylist

PHPとPostgreSQLを用いたAI駆動のファッションスタイリングアプリケーションです。ユーザーが所有している服の情報をデータベースに保管し、その服のデータと現在の位置情報、天気情報を元に、Gemini APIを使用して最適な服装を提案します。

## 主な機能

- **ワードローブ管理**: ユーザーの手持ちの服を画像と詳細情報とともに登録
- **AI服装提案**: 位置情報と天気情報を取得し、Gemini APIに問い合わせて今日の服装を決定
- **服装履歴**: 過去のAI提案を閲覧

## 技術スタック

- バックエンド: PHP
- データベース: PostgreSQL
- フロントエンド: HTML/CSS/JavaScript、Semantic UI
- AI: Google Gemini API
- 天気情報: OpenWeatherMap API

## セットアップ手順

### 1. 必要な環境

- PHP 7.4以上
- PostgreSQL 12以上
- Composer（推奨）
- Webサーバー（Apache/Nginx等）

### 2. データベースのセットアップ

```bash
# PostgreSQLに接続
psql -U postgres

# データベースを作成
CREATE DATABASE ai_stylist_db;

# ユーザーを作成（必要に応じて）
CREATE USER ai_stylist_user WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE ai_stylist_db TO ai_stylist_user;

# データベースに接続
\c ai_stylist_db

# スキーマをインポート
\i schema/schema.sql
```

### 3. 環境変数の設定

`.env.example`をコピーして`.env`ファイルを作成し、必要な情報を設定します。

```bash
cp .env.example .env
```

`.env`ファイルを編集：

```env
# データベース設定
DB_HOST=localhost
DB_NAME=ai_stylist_db
DB_USER=ai_stylist_user
DB_PASSWORD=your_password

# APIキー設定
GEMINI_API_KEY=your_gemini_api_key_here
WEATHER_API_KEY=your_openweathermap_api_key_here
```

### 4. APIキーの取得

#### Gemini API
1. [Google AI Studio](https://makersuite.google.com/app/apikey)にアクセス
2. APIキーを生成
3. `.env`の`GEMINI_API_KEY`に設定

#### OpenWeatherMap API
1. [OpenWeatherMap](https://openweathermap.org/api)にアクセス
2. 無料アカウントを作成
3. APIキーを取得
4. `.env`の`WEATHER_API_KEY`に設定

### 5. アップロードディレクトリの作成

```bash
mkdir -p uploads
chmod 755 uploads
```

### 6. Webサーバーの設定

#### Apache

`.htaccess`を作成（既に存在する場合は確認）：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # PHPエラー表示（開発環境のみ）
    php_flag display_errors On
    php_value error_reporting E_ALL
</IfModule>
```

#### Nginx

nginx設定例：

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/my-ai-stylist;
    index index.html index.php;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location /uploads/ {
        alias /path/to/my-ai-stylist/uploads/;
    }
}
```

### 7. アプリケーションの起動

開発環境の場合、PHPビルトインサーバーを使用できます：

```bash
php -S localhost:8000
```

ブラウザで`http://localhost:8000`にアクセスします。

## 使い方

### 1. 新規登録
- トップページから「新規会員登録」をクリック
- ユーザー名、メールアドレス、パスワードを入力して登録

### 2. ログイン
- ユーザー名とパスワードでログイン

### 3. 服の登録
- 「register」メニューから服を登録
- 画像と詳細情報（種類、色、材質、模様、季節、首元デザイン、袖丈）を入力

### 4. AI相談
- 「chat」メニューからAI相談ページへ
- 性別、年齢、目的、スタイルを入力
- 位置情報を許可すると、現在地の天気情報を自動取得
- 「AIに相談する」をクリック

### 5. 提案結果の確認
- AIが提案した服装とアドバイスを確認
- 履歴から過去の提案を閲覧可能

## ファイル構成

```
my-ai-stylist/
├── index.html              # ホームページ
├── login.html              # ログインページ
├── regform.html            # 新規登録フォーム
├── regform.php             # 新規登録処理
├── logout.php              # ログアウト処理
├── registar-clothes.php    # 服登録ページ
├── question.php            # AI相談ページ
├── result.php              # 提案結果表示ページ
├── outfit-history.php      # 服装履歴ページ
├── .env                    # 環境変数（要作成）
├── .env.example            # 環境変数サンプル
├── .gitignore              # Git除外設定
├── CLAUDE.md               # 開発ガイド
├── SPECIFICATION.md        # 実装仕様書
├── README.md               # このファイル
├── includes/               # 共通ファイル
│   ├── config.php         # 設定・DB接続
│   ├── auth.php           # 認証処理
│   ├── csrf.php           # CSRF対策
│   └── header.php         # 共通ヘッダー
├── schema/
│   └── schema.sql         # データベーススキーマ
└── uploads/               # 画像アップロード先
    └── .gitkeep
```

## トラブルシューティング

### データベース接続エラー
- `.env`ファイルのDB設定を確認
- PostgreSQLが起動しているか確認
- ユーザーの権限を確認

### 画像アップロードエラー
- `uploads/`ディレクトリの存在を確認
- ディレクトリの書き込み権限を確認（755または777）

### API呼び出しエラー
- `.env`ファイルのAPIキーを確認
- インターネット接続を確認
- APIの利用制限を確認

### 位置情報が取得できない
- ブラウザの位置情報許可を確認
- HTTPSでアクセスしているか確認（一部ブラウザで必要）

## セキュリティ注意事項

- `.env`ファイルは絶対にGitにコミットしないでください
- 本番環境ではHTTPSを使用してください
- データベースのパスワードは強固なものを使用してください
- 定期的にAPIキーをローテーションしてください

## ライセンス

このプロジェクトは教育目的で作成されました。

## 貢献

プルリクエストを歓迎します。大きな変更の場合は、まずissueを開いて変更内容を議論してください。

## サポート

問題が発生した場合は、GitHubのIssuesで報告してください。
