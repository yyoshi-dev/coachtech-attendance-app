# 基本設計書

---

## Route及びController

| 画面名称                           | パス                                                              | メソッド  | ルート先コントローラー                                      | アクション                          | 認証必須 | 説明                                              |
| ---------------------------------- | ----------------------------------------------------------------- | --------- | ----------------------------------------------------------- | ----------------------------------- | -------- | ------------------------------------------------- |
| 会員登録画面（一般ユーザー）       | /register                                                         | GET, POST | Fortify (内部)                                              | register                            |          | 会員登録機能                                      |
| ログイン画面（一般ユーザー）       | /login                                                            | GET, POST | Fortify (内部)                                              | login                               |          | 一般ユーザーのログイン機能                        |
| 出勤登録画面（一般ユーザー）       | /attendance                                                       | GET       | User/AttendanceController                                   | index                               | 〇       | 出退勤登録画面表示機能                            |
| 勤怠一覧画面（一般ユーザー）       | /attendance/list                                                  | GET       | User/AttendanceController                                   | list                                | 〇       | 勤怠一覧画面 (一般ユーザー)表示機能               |
| 勤怠詳細画面（一般ユーザー）       | /attendance/detail/{id}                                           | GET, POST | User/AttendanceController, User/CorrectionRequestController | detail, storeCorrection             | 〇       | 勤怠詳細画面 (一般ユーザー)表示機能、勤怠修正機能 |
| 申請一覧画面（一般ユーザー）       | /stamp_correction_request/list                                    | GET       | CorrectionRequestEntryController                            | index                               | 〇       | 申請一覧画面 (一般ユーザー)表示機能               |
| ログイン画面（管理者）             | /admin/login                                                      | GET, POST | Fortify (内部)                                              | login                               |          | 管理者のログイン機能                              |
| 勤怠一覧画面（管理者）             | /admin/attendance/list                                            | GET       | Admin/AttendanceController                                  | dailyList                           | 〇       | 勤怠一覧画面 (管理者)表示機能                     |
| 勤怠詳細画面（管理者）             | /admin/attendance/{id}                                            | GET, PUT  | Admin/AttendanceController                                  | detail, update                      | 〇       | 勤怠詳細画面 (管理者)表示機能、勤怠更新機能       |
| スタッフ一覧画面（管理者）         | /admin/staff/list                                                 | GET       | Admin/StaffController                                       | list                                | 〇       | スタッフ一覧画面 (管理者)表示機能                 |
| スタッフ別勤怠一覧画面（管理者）   | /admin/attendance/staff/{id}                                      | GET       | Admin/AttendanceController                                  | staffMonthlyList                    | 〇       | スタッフ別勤怠一覧画面 (管理者)表示機能           |
| 申請一覧画面（管理者）             | /stamp_correction_request/list                                    | GET       | CorrectionRequestEntryController                            | index                               | 〇       | 申請一覧画面 (管理者)表示機能                     |
| 修正申請承認画面（管理者）         | /stamp_correction_request/approve/{attendance_correct_request_id} | GET, PUT  | Admin/CorrectionRequestController                           | detailCorrection, approveCorrection | 〇       | 修正申請承認画面表示機能、修正申請承認機能        |
| メール認証誘導画面（一般ユーザー） | /email/verify                                                     | GET       | 匿名ルート                                                  |                                     | 〇       | メール認証誘導画面の表示機能                      |
| mailhog画面                        | /email/verify/mailhog                                             | GET       | 匿名ルート                                                  |                                     | 〇       | mailhog画面表示機能 (テスト用にルートを設定)      |
| ログアウト機能（一般ユーザー）     | /logout                                                           | POST      | Fortify (内部)                                              | logout                              | 〇       | 一般ユーザーのログアウト機能                      |
| ログアウト機能（管理者）           | /admin/logout                                                     | POST      | Fortify (内部)                                              | logout                              | 〇       | 管理者のログアウト機能                            |
| 出勤機能（一般ユーザー）           | /attendance/clock-in                                              | POST      | User/AttendanceController                                   | clockIn                             | 〇       | 出勤登録機能                                      |
| 退勤機能（一般ユーザー）           | /attendance/clock-out                                             | POST      | User/AttendanceController                                   | clockOut                            | 〇       | 退勤登録機能                                      |
| 休憩入機能（一般ユーザー）         | /attendance/break-start                                           | POST      | User/AttendanceController                                   | breakStart                          | 〇       | 休憩入登録機能                                    |
| 休憩戻機能（一般ユーザー）         | /attendance/break-end                                             | POST      | User/AttendanceController                                   | breakEnd                            | 〇       | 休憩戻登録機能                                    |
| csv出力                            | /admin/attendance/staff/{id}/export                               | GET       | Admin/AttendanceController                                  | export                              | 〇       | csvファイル出力機能                               |

---

## Model

| モデルファイル名                 | 説明                                           |
| -------------------------------- | ---------------------------------------------- |
| Attendance                       | attendancesテーブル用                          |
| AttendanceBreak                  | attendance_breaksテーブル用                    |
| AttendanceCorrectionRequest      | attendance_correction_requestsテーブル用       |
| AttendanceCorrectionRequestBreak | attendance_correction_request_breaksテーブル用 |
| User                             | usersテーブル用                                |

---

## View

| 画面名称                         | bladeファイル名                          | 画面専用cssファイル名 |
| -------------------------------- | ---------------------------------------- | --------------------- |
| 会員登録画面（一般ユーザー）     | user/auth/register.blade.php             | auth.css              |
| ログイン画面（一般ユーザー）     | user/auth/login.blade.php                | auth.css              |
| 出勤登録画面（一般ユーザー）     | user/attendance/index.blade.php          | attendance.css        |
| 勤怠一覧画面（一般ユーザー）     | user/attendance/list.blade.php           | attendance-list.css   |
| 勤怠詳細画面（一般ユーザー）     | user/attendance/detail.blade.php         | attendance-detail.css |
| 申請一覧画面（一般ユーザー）     | user/request/list.blade.php              | request-list.css      |
| ログイン画面（管理者）           | admin/auth/login.blade.php               | auth.css              |
| 勤怠一覧画面（管理者）           | admin/attendance/list.blade.php          | attendance-list.css   |
| 勤怠詳細画面（管理者）           | admin/attendance/detail.blade.php        | attendance-detail.css |
| スタッフ一覧画面（管理者）       | admin/staff/list.blade.php               | staff.css             |
| スタッフ別勤怠一覧画面（管理者） | admin/attendance/staff-monthly.blade.php | attendance-list.css   |
| 申請一覧画面（管理者）           | admin/request/list.blade.php             | request-list.css      |
| 修正申請承認画面（管理者）       | admin/request/approve.blade.php          | attendance-detail.css |
| メール認証誘導画面               | user/auth/verify-email.blade.php         | auth.css              |

---

## バリデーション

| バリデーションファイル名             | フォーム         | ルール                                                                               | メッセージ                                                                                                                                           |
| ------------------------------------ | ---------------- | ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| RegisterRequest.php                  | ユーザー名       | 入力必須                                                                             | お名前を入力してください                                                                                                                             |
|                                      | メールアドレス   | 入力必須、メール形式                                                                 | メールアドレスを入力してください、メールアドレスはメール形式で入力してください                                                                       |
|                                      | パスワード       | 入力必須、8文字以上                                                                  | パスワードを入力してください、パスワードは8文字以上で入力してください                                                                                |
|                                      | 確認用パスワード | 「パスワード」との一致のみ可                                                         | パスワードと一致しません                                                                                                                             |
| LoginRequest.php                     | メールアドレス   | 入力必須、メール形式                                                                 | メールアドレスを入力してください、メールアドレスはメール形式で入力してください                                                                       |
|                                      | パスワード       | 入力必須、入力情報の誤り                                                             | パスワードを入力してください、ログイン情報が登録されていません                                                                                       |
| StoreAttendanceCorrectionRequest.php | 出勤時間         | 出勤時間<=退勤時間、入力必須                                                         | 出勤時間もしくは退勤時間が不適切な値です、出勤時間を入力してください                                                                                 |
|                                      | 退勤時間         | 退勤時間>=出勤時間、入力必須                                                         | 出勤時間もしくは退勤時間が不適切な値です、退勤時間を入力してください                                                                                 |
|                                      | 休憩開始時間     | 休憩開始時間>=出勤時間、休憩開始時間<=退勤時間、休憩開始時間<=休憩終了時間、入力必須 | 休憩時間が不適切な値です、休憩時間が不適切な値です、休憩開始時間もしくは休憩終了時間が不適切な値です、休憩開始時間を入力してください                 |
|                                      | 休憩終了時間     | 休憩終了時間<=退勤時間、休憩終了時間>=休憩開始時間、休憩終了時間>=出勤時間、入力必須 | 休憩時間もしくは退勤時間が不適切な値です、休憩開始時間もしくは休憩終了時間が不適切な値です、休憩時間が不適切な値です、休憩終了時間を入力してください |
|                                      | 備考欄           | 入力必須                                                                             | 備考を記入してください                                                                                                                               |

---
