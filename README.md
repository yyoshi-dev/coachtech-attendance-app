# Coachtech勤怠管理アプリ

## 環境構築

### Dockerビルド
1. リポジトリをクローン
    ```bash
    git clone git@github.com:yyoshi-dev/coachtech-attendance-app.git
    ```

2. ディレクトリに移動
    ```bash
    cd coachtech-attendance-app
    ```

3. コンテナを起動
    ```bash
    docker compose up -d --build
    ```

### Laravel環境構築
1. PHPコンテナに接続
    ```bash
    docker compose exec php bash
    ```

2. コンテナ内で依存関係をインストール
    ```bash
    composer install
    ```

3. `.env.example`をコピーして`.env`を作成
    ```bash
    cp .env.example .env
    ```

4. アプリケーションキーを生成
    ```bash
    php artisan key:generate
    ```

5.  マイグレーションを実行
    ```bash
    php artisan migrate
    ```

6.  ローカル開発用のシーディングを実行
    ```bash
    php artisan db:seed --class=LocalTestSeeder
    ```
    ※ LocalTestSeederはローカル開発専用

---

## テスト

### PHP Unitを用いたテスト
- 要件シートの「テストケース一覧」に記載されているテスト要件について、PHP Unitを用いて実装している
- 以下のコードを実行する事でテストを実施出来る
  1. PHPコンテナに接続
      ```bash
      docker compose exec php bash
      ```

  2. PHP Unitを用いたテストを実行
      ```bash
      php artisan test
      ```

### 手動テスト
#### ローカル開発用のダミーデータの準備
上記の環境構築を実施すると、以下のダミーデータが生成される


---

## 使用技術 (実行環境)
- PHP：8.4.18
- Laravel: 12.53.0
- laravel/fortify: 1.35.0
- MySQL: 8.0.40
- nginx: 1.27.2
- phpMyAdmin: 5.2.3
- mailhog: 1.0.1

---

## ER図
![ER図](docs/requirements/er_diagram.drawio.png)

※ 詳細要件は、[要件定義書](docs/requirements.md)に記載している

---

## URL (開発環境)
- 会員登録画面 (一般ユーザー): http://localhost/register
- ログイン画面 (一般ユーザー): http://localhost/login
- 勤怠登録画面 (一般ユーザー): http://localhost/attendance
- ログイン画面 (管理者): http://localhost/admin/login
- phpMyAdmin: http://localhost:8080/
- mailhog: http://localhost:8025/