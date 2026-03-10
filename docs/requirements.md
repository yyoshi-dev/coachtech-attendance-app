# 要件定義書

本ドキュメントは、本プロジェクトにおける要件全体を統合的に整理したトップレベル文書とする。
機能要件、UI要件、データ要件、テスト要件等の詳細は、各専用ドキュメントに分割して管理する。

---

## 1. 概要

本アプリケーションは、一般ユーザーおよび管理者ユーザーの勤怠管理を目的としたWebアプリケーションである。
一般ユーザーは、会員登録・ログイン・勤怠打刻 (出勤 / 休憩入 / 休憩戻 / 退勤)・勤怠一覧・勤怠詳細・修正申請などの機能を利用できる。
管理者ユーザーは、全ユーザーの勤怠情報確認、勤怠詳細の修正、修正申請の承認、スタッフ一覧の確認などを行うことができる。

概要および開発プロセスは以下にまとめる。

- [プロジェクト概要](requirements/overview.md)
- [開発プロセス](requirements/development_process.md)

---

## 2. 機能要件

ユーザーストーリーに基づく機能要件は以下にまとめる。

- [機能要件](requirements/functional_requirements.md)

主な機能カテゴリは以下である。

### ● 一般ユーザー向け機能
- 会員登録 (Fortify / メール認証)
- ログイン / ログアウト
- 勤怠打刻 (出勤 / 休憩入 / 休憩戻 / 退勤)
- 勤怠一覧 (月次)
- 勤怠詳細 (確認 / 修正申請)
- 修正申請一覧 (承認待ち / 承認済み)

### ● 管理者ユーザー向け機能
- 管理者ログイン / ログアウト
- 日次勤怠一覧 (全ユーザー)
- 勤怠詳細 (確認 / 修正)
- スタッフ一覧
- スタッフ別月次勤怠一覧
- 修正申請一覧 (承認待ち / 承認済み)
- 修正申請承認機能

---

## 3. 画面設計

画面仕様およびUIデザインは以下にまとめる。

- [画面設計](requirements/ui_design.md)

---

## 4. テーブル設計

テーブル仕様書およびER図は以下にまとめる。

- [テーブル設計](requirements/database_schema.md)
- ER図
    <img src="requirements/er_diagram.drawio.png" alt="ロゴ" width="600">

---

## 5. 基本設計

アプリケーションの基本設計 (ルーティング、コントローラ、モデル、ビュー、バリデーション、ダミーデータ等)は以下にまとめる。

- [基本設計](requirements/basic_design.md)

---

## 6. テスト仕様書

テストケース一覧は以下にまとめる。

- [テストケース一覧](requirements/test_cases.md)

---

## 7. 要件ドキュメント構造
```
docs/
├── requirements.md
└── requirements/
    ├── basic_design.md
    ├── database_schema.md
    ├── development_process.md
    ├── er_diagram.drawio.png
    ├── functional_requirements.md
    ├── overview.md
    ├── test_cases.md
    ├── ui_design.md
    ├── fig/
```




