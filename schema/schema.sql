CREATE TABLE IF NOT EXISTS users (
		id BIGSERIAL PRIMARY KEY,
		username VARCHAR(50) UNIQUE NOT NULL,
		email VARCHAR(100) UNIQUE NOT NULL,
		password_hash VARCHAR(255) NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS images (
	id BIGSERIAL PRIMARY KEY,
	user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
	image_url TEXT NOT NULL,
	created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
)

CREATE TABLE IF NOT EXISTS clothes (
	id BIGSERIAL PRIMARY KEY,
	user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
	image_id INTEGER REFERENCES images(id) ON DELETE SET NULL,
	garment_type VARCHAR(20) NOT NULL,
	color VARCHAR(20) NOT NULL,
	fabric VARCHAR(20) NOT NULL,
	pattern VARCHAR(20),
	season VARCHAR(20),
	neckline VARCHAR(20),
	sleeve_length VARCHAR(20),
	created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
)

-- AIに問い合わせた結果を保存するテーブル
CREATE TABLE IF NOT EXISTS ask_result (
	id BIGSERIAL PRIMARY KEY,
	user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
	prompt TEXT NOT NULL, -- ユーザーがAIに送った質問内容（例：「デートに着ていく服」「オフィスカジュアル」）
	weather VARCHAR(50), -- 天候情報（例：「晴れ」「雨」）
	temperature DECIMAL(5,2), -- 気温（摂氏）
	occasion VARCHAR(100), -- シーン・用途（例：「ビジネス」「カジュアル」「フォーマル」）
	ai_response TEXT NOT NULL, -- AIからの提案内容（全体的なアドバイス）
	created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- AIが提案した服の組み合わせ（複数の服を組み合わせて1つのコーディネートを構成）
CREATE TABLE IF NOT EXISTS ask_result_items (
	id BIGSERIAL PRIMARY KEY,
	ask_result_id INTEGER REFERENCES ask_result(id) ON DELETE CASCADE,
	clothes_id INTEGER REFERENCES clothes(id) ON DELETE SET NULL,
	item_order INTEGER NOT NULL, -- 服の表示順序（トップス=1、ボトムス=2など）
	item_type VARCHAR(50), -- アイテムタイプ（例：「トップス」「ボトムス」「アウター」「シューズ」）
	ai_reason TEXT, -- この服を選んだAIの理由
	created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- インデックス作成（検索パフォーマンス向上）
CREATE INDEX IF NOT EXISTS idx_ask_result_user_id ON ask_result(user_id);
CREATE INDEX IF NOT EXISTS idx_ask_result_created_at ON ask_result(created_at);
CREATE INDEX IF NOT EXISTS idx_ask_result_items_ask_result_id ON ask_result_items(ask_result_id);
CREATE INDEX IF NOT EXISTS idx_ask_result_items_clothes_id ON ask_result_items(clothes_id);