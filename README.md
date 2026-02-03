# ✈️ CONNECT
## 〜旅の出会いを、もっと特別に〜

旅行計画を共有して、同じ目的地や興味を持つ旅仲間を見つけるWebアプリケーションです。

---

## 🚀 最初のセットアップ（5分で完了！）

### ステップ1: MAMPをインストール
- **Mac**: https://www.mamp.info/
- **Windows**: https://www.apachefriends.org/（XAMPP）

### ステップ2: ファイルを配置
ZIPを解凍し、`travel_match`フォルダを配置：

| 環境 | 配置場所 |
|------|----------|
| MAMP（Mac） | `/Applications/MAMP/htdocs/travel_match/` |
| XAMPP（Windows） | `C:\xampp\htdocs\travel_match\` |

### ステップ3: サーバーを起動
1. MAMP（またはXAMPP）を起動
2. 「Start Servers」をクリック
3. Apache と MySQL が緑色になればOK ✅

### ステップ4: セットアップを実行
ブラウザで以下にアクセス：
```
http://localhost:8888/travel_match/setup.php
```

> 💡 **XAMPPの場合**: setup.phpを開いて `$pass = '';` に変更してください

「🎉 セットアップ完了！」と表示されたら準備完了です！

### ステップ5: 使い始める
```
http://localhost:8888/travel_match/
```

---

## 📱 主な機能

### 🔐 ユーザー機能
- アカウント登録・ログイン
- プロフィール設定（写真、出身地、性別、年齢、好きなもの）

### 📝 旅行計画
- 旅行計画の作成・編集・削除
- 地図での場所選択（OpenStreetMap）
- 旅行の目的・やりたいこと（18種類）の設定
- **写真のアップロード**（任意）
- 他のユーザーの計画を閲覧（リスト表示・地図表示）

### 🔍 検索・フィルター
- 都道府県で絞り込み
- 旅行目的で絞り込み
- やりたいことで絞り込み
- 投稿者の出身地・性別・年齢で絞り込み

### 💕 マッチング
- 自分の計画と相性の良いユーザーを自動マッチング
- **マッチング条件設定**：
  - 相手の性別（男性のみ / 女性のみ / どちらでも）
  - 相手の年齢（±5歳 / ±10歳 / 気にしない）
  - 日程の近さ（1週間以内 / 1ヶ月以内 / 気にしない）
- **重視設定**：場所・目的・やりたいことの重要度を2倍に
- 相性スコア（%）の表示
- **2つの計画を混ぜたおすすめプラン**を自動提案

### 💬 コミュニケーション
- いいね機能
- コメント機能
- 1対1のチャット機能
- 通知バッジ（未読メッセージ・いいね）

### 🎮 仮想旅（独自機能）
マッチングした相手と「仮想の旅行」を体験！
- 10問の質問に答えて旅の価値観をチェック
- 相性度（%）を自動計算
- 回答の詳細比較が可能

---

## 🚀 セットアップ手順

### 必要なもの
- MAMP（Mac）または XAMPP（Windows）
- Webブラウザ

### 手順

#### 1. MAMPをインストール
- Mac: https://www.mamp.info/
- Windows: https://www.apachefriends.org/（XAMPPでもOK）

#### 2. ファイルを配置
ZIPを解凍し、`travel_match`フォルダを以下に配置：

**MAMP（Mac）の場合：**
```
/Applications/MAMP/htdocs/travel_match/
```

**XAMPP（Windows）の場合：**
```
C:\xampp\htdocs\travel_match\
```

#### 3. MAMPを起動
- MAMPアプリを起動
- 「Start Servers」をクリック
- Apache と MySQL が緑色になればOK

#### 4. データベースをセットアップ
ブラウザで以下にアクセス：
```
http://localhost:8888/travel_match/setup.php
```
※ポート番号は環境により異なる場合があります（80, 8080など）

「🎉 セットアップ完了！」と表示されればOK

#### 5. アプリを使い始める
```
http://localhost:8888/travel_match/
```

---

## 🔄 既存環境のアップデート

すでにCONNECTを使用していて、新機能を追加する場合：

```
http://localhost:8888/travel_match/migrate_photo.php
```

これで旅行計画への写真アップロード機能が有効になります。

---

## 📡 他のPCやスマホからアクセスする

同じWi-Fiネットワーク内の他のデバイスからもアクセスできます。

### 手順

1. **自分のPCのIPアドレスを確認**
   - Mac: システム環境設定 → ネットワーク → IPアドレス
   - Windows: コマンドプロンプトで `ipconfig` → IPv4アドレス

2. **他のデバイスからアクセス**
   ```
   http://[IPアドレス]:8888/travel_match/
   ```
   例: `http://192.168.1.5:8888/travel_match/`

※ファイアウォールでブロックされる場合は、設定で許可してください

---

## 🎮 仮想旅の遊び方

1. チャット画面で「🎮 仮想旅に誘う」をタップ
2. 相手が「参加する」をタップ
3. 各自10問の質問に回答（非同期でOK）
4. 両者完了で相性度が表示！
5. 「詳細を見る」で回答を比較

### 質問カテゴリ
- 🌅 旅の始まり（出発準備）
- 🚃 移動中
- 🏯 現地での行動
- ⚡ トラブル発生
- 🌇 旅の終わり

---

## 📷 プロフィール画像

| 項目 | 仕様 |
|------|------|
| 対応形式 | JPEG, PNG, GIF, WebP |
| 最大サイズ | 5MB |
| 保存場所 | `uploads/profiles/` |

---

## 🔧 トラブルシューティング

### 「データベース接続エラー」が出る
- MAMPが起動しているか確認
- MySQLサーバーが動作しているか確認
- `setup.php`内のパスワードを確認（MAMPは`root`、XAMPPは空文字）

### 画像がアップロードできない
- `uploads/profiles/`フォルダの書き込み権限を確認
- ファイルサイズが5MB以下か確認

### 他のPCからアクセスできない
- 同じWi-Fiに接続しているか確認
- ファイアウォールの設定を確認
- IPアドレスとポート番号が正しいか確認

---

## 📁 ファイル構成

```
travel_match/
├── index.php              # トップページ
├── setup.php              # データベースセットアップ
├── login.php              # ログイン
├── signup.php             # 新規登録
├── logout.php             # ログアウト
├── profile_setup.php      # 初回プロフィール設定
├── profile_edit.php       # プロフィール編集
├── plan_create.php        # 旅行計画作成・編集
├── plans.php              # 旅行計画一覧
├── plan_detail.php        # 旅行計画詳細
├── matching.php           # マッチング
├── chat_list.php          # チャット一覧
├── chat.php               # チャット
├── virtual_trip.php       # 仮想旅
├── virtual_trip_result.php # 仮想旅結果
├── api/                   # APIエンドポイント
├── css/                   # スタイルシート
├── includes/              # 共通ファイル
└── uploads/profiles/      # プロフィール画像
```

---

## 🎨 技術スタック

- **バックエンド**: PHP 7.x
- **データベース**: MySQL (MariaDB)
- **フロントエンド**: HTML5, CSS3, JavaScript
- **地図**: Leaflet + OpenStreetMap（無料）

---

## 📝 ライセンス

このプロジェクトは学習・個人利用を目的としています。

---

Created with ❤️ - CONNECT
