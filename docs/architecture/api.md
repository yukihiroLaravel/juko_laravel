# API エンドポイント

現在のエンドポイントの一覧である。振る舞いの詳細は `docs/specs/` を参照し、ここでは経路と対応する仕様の対応づけだけを扱う。

一覧の正は `scripts/dc php artisan route:list --except-vendor` である。リポジトリ直下の `api.json` は生成時点のもので古い場合がある。

## 前提

| 項目 | 内容 |
|---|---|
| 接頭辞 | `api/v1`（ログインとログアウトを除く） |
| 認証 | Sanctum のステートフル認証。要求ごとにロールのミドルウェアで区分を判定する |
| 応答形式 | 常に JSON |
| 共通の振る舞い | `docs/specs/conventions.md` |

パス設計には既存の経緯が残っている。一覧取得に `/index` が付き、更新に `POST` を使い、削除の対象を本文で受け取るものがある。新規のエンドポイントは `docs/architecture/coding-standards.md` のルーティング規約に従う。

## 認証

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| POST | `login` | `login` | 受講生のログイン | AC-ACCOUNT-020 |
| POST | `login/instructor` | `instructor.login` | 講師のログイン | AC-ACCOUNT-020 |
| POST | `logout` | `logout` | 受講生のログアウト | AC-ACCOUNT-025 |
| POST | `logout/instructor` | `instructor.logout` | 講師のログアウト | AC-ACCOUNT-025 |
| GET | `api/user` | なし | 認証中の利用者と区分の取得 | AC-COMMON-004 |

## 認証不要

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| POST | `api/v1/students` | `student.store` | 受講生の仮登録 | AC-ACCOUNT-001 |
| POST | `api/v1/students/verification/{token}` | `student.verify-code` | 受講生の本登録 | AC-ACCOUNT-010 |
| POST | `api/v1/instructors` | `instructor.register` | 講師の仮登録 | AC-ACCOUNT-001 |
| POST | `api/v1/instructors/verification/{token}` | `instructor.verify-code` | 講師の本登録 | AC-ACCOUNT-010 |

`POST api/v1/students` は認証不要の仮登録であり、`GET api/v1/students` は受講生の認証を必要とする自身の情報の取得である。同じパスでメソッドによって認証の要否が変わる。

## 受講生向け

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/students` | `student.show` | 自身の情報の取得 | AC-ACCOUNT-026 |
| POST | `api/v1/students/update` | `student.update` | 自身の情報の更新 | AC-ACCOUNT-026 |
| GET | `api/v1/students/learning-history` | `student.learning-history.index` | 直近30日の学習履歴 | AC-ANALYTICS-034 |
| GET | `api/v1/students/login-streak` | `student.login-streak` | 連続ログイン日数 | AC-ANALYTICS-039 |
| GET | `api/v1/attendances/index` | `student.attendances.index` | 受講一覧 | AC-ENROLL-024 |
| GET | `api/v1/attendances/{attendance_id}` | `student.attendances.show` | 受講詳細 | AC-ENROLL-029 |
| GET | `api/v1/attendances/{attendance_id}/progress` | `student.attendances.progress` | 受講中の講座の進捗 | AC-PROG-012 |
| PUT | `api/v1/attendances/{attendance_id}/complete` | `student.attendances.complete-all-chapters` | 講座単位でまとめて完了にする | AC-PROG-010 |
| PUT | `api/v1/attendances/{attendance_id}/chapters/{chapter_id}/complete` | `student.attendances.complete-all-lessons` | チャプター単位でまとめて完了にする | AC-PROG-009 |
| GET | `api/v1/attendances/{attendance_id}/stuck-points` | `student.attendances.stuck-points` | つまずき箇所 | AC-ANALYTICS-016 |
| PATCH | `api/v1/lesson-attendances/{lesson_attendance_id}` | `student.lesson-attendances.patch-status` | 受講状況を1件更新する | AC-PROG-005 |
| GET | `api/v1/notifications/index` | `student.notifications.index` | お知らせ一覧 | AC-NOTIF-014 |
| GET | `api/v1/notifications/{notification_id}` | `student.notifications.show` | お知らせ詳細 | AC-NOTIF-019 |
| POST | `api/v1/notifications/mark-read` | `student.notifications.mark-read` | お知らせの確認済みの記録 | AC-NOTIF-023 |

## 講師向け

接頭辞は `api/v1/instructor` である。

### 自身の情報

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/instructor` | `instructor.show` | 自身の情報の取得 | AC-ACCOUNT-027 |
| POST | `api/v1/instructor/update` | `instructor.update` | 自身の情報の更新 | AC-ACCOUNT-027 |

### 講座

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/instructor/courses/index` | `instructor.courses.index` | 講座一覧 | AC-COMMON-006 |
| POST | `api/v1/instructor/courses` | `instructor.courses.store` | 講座の作成 | AC-AUTHOR-001 |
| GET | `api/v1/instructor/courses/{course_id}` | `instructor.courses.show` | 講座詳細 | AC-AUTHZ-001 |
| POST | `api/v1/instructor/courses/{course_id}` | `instructor.courses.update` | 講座の更新 | AC-AUTHOR-008 |
| DELETE | `api/v1/instructor/courses/{course_id}` | `instructor.courses.delete` | 講座の削除 | AC-AUTHOR-015 |
| DELETE | `api/v1/instructor/courses` | `instructor.courses.bulk-delete` | 講座の一括削除 | AC-AUTHOR-018 |
| PUT | `api/v1/instructor/courses/status` | `instructor.courses.put-status` | 公開状態の一括変更 | AC-AUTHOR-012 |
| PUT | `api/v1/instructor/courses/capacity` | `instructor.courses.put-capacity` | 定員の一括変更 | AC-ENROLL-018 |
| PATCH | `api/v1/instructor/courses/capacity/clear` | `instructor.courses.capacity.clear` | 選択した講座の定員の取り消し | AC-ENROLL-020 |
| PATCH | `api/v1/instructor/courses/capacity/clear-all` | `instructor.courses.capacity.clear-all` | 全講座の定員の取り消し | AC-ENROLL-021 |
| PATCH | `api/v1/instructor/courses/deadline` | `instructor.courses.deadline.bulk-update` | 受講期限の一括変更 | AC-ENROLL-015 |
| GET | `api/v1/instructor/courses/tags/index` | `instructor.courses.tags.index` | タグごとの講座一覧 | AC-TAG-008 |

### チャプター

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| POST | `api/v1/instructor/courses/{course_id}/chapters` | `instructor.chapters.store` | チャプターの作成 | AC-AUTHOR-019 |
| POST | `api/v1/instructor/courses/{course_id}/chapters/sort` | `instructor.chapters.sort` | チャプターの並び替え | AC-AUTHOR-021 |
| PUT | `api/v1/instructor/courses/{course_id}/chapters/status` | `instructor.chapters.put-status` | 講座の全チャプターの公開状態の変更 | AC-AUTHOR-022 |
| PATCH | `api/v1/instructor/courses/{course_id}/chapters/status` | `instructor.chapters.patch-status` | 選択したチャプターの公開状態の変更 | AC-AUTHOR-022 |
| DELETE | `api/v1/instructor/courses/{course_id}/chapters` | `instructor.chapters.bulk-delete` | 選択したチャプターの削除 | AC-AUTHOR-023 |
| DELETE | `api/v1/instructor/courses/{course_id}/chapters/all` | `instructor.chapters.delete-all` | 講座の全チャプターの削除 | AC-AUTHOR-023 |
| GET | `api/v1/instructor/chapters/{chapter_id}` | `instructor.chapters.show` | チャプター詳細 | AC-AUTHZ-004 |
| PUT | `api/v1/instructor/chapters/{chapter_id}` | `instructor.chapters.put` | チャプター名の更新 | AC-AUTHOR-020 |

### レッスン

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| POST | `api/v1/instructor/chapters/{chapter_id}/lessons` | `instructor.chapters.lessons.store` | レッスンの作成 | AC-AUTHOR-025 |
| POST | `api/v1/instructor/chapters/{chapter_id}/lessons/sort` | `instructor.chapters.lessons.sort` | レッスンの並び替え | AC-AUTHOR-032 |
| PUT | `api/v1/instructor/chapters/{chapter_id}/lessons/status` | `instructor.chapters.lessons.put-status` | 選択したレッスンの公開状態の変更 | AC-AUTHOR-029 |
| DELETE | `api/v1/instructor/chapters/{chapter_id}/lessons` | `instructor.chapters.lessons.bulk-delete` | 選択したレッスンの削除 | AC-AUTHOR-030 |
| DELETE | `api/v1/instructor/chapters/{chapter_id}/lessons/all` | `instructor.chapters.lessons.delete-all` | チャプターの全レッスンの削除 | AC-AUTHOR-030 |
| PUT | `api/v1/instructor/lessons/{lesson_id}` | `instructor.lessons.put` | レッスンの更新 | AC-AUTHOR-026 |
| DELETE | `api/v1/instructor/lessons/{lesson_id}` | `instructor.lessons.delete` | レッスンの削除 | AC-AUTHOR-030 |
| PATCH | `api/v1/instructor/lessons/{lesson_id}/status` | `instructor.lessons.update-status` | レッスンの公開状態の変更 | AC-AUTHOR-027 |
| PATCH | `api/v1/instructor/lessons/{lesson_id}/title` | `instructor.lessons.update-title` | レッスン名の更新 | AC-AUTHOR-026 |

### 受講

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| POST | `api/v1/instructor/attendances` | `instructor.attendances.store` | 受講の登録 | AC-ENROLL-001 |
| GET | `api/v1/instructor/attendances/{attendance_id}` | `instructor.attendances.show` | 受講状況の詳細 | AC-ANALYTICS-013 |
| DELETE | `api/v1/instructor/attendances/{attendance_id}` | `instructor.attendances.delete` | 受講の削除 | AC-ENROLL-022 |
| GET | `api/v1/instructor/attendances/{attendance_id}/status` | `instructor.attendances.status` | 受講している講座の構成の取得 | AC-AUTHZ-004 |

### 集計

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/instructor/courses/{course_id}/attendances/{period}` | `instructor.courses.attendances.login-rate` | ログイン率 | AC-ANALYTICS-004 |
| GET | `api/v1/instructor/courses/{course_id}/attendances/status/{period}` | `instructor.courses.attendances.show-status` | 完了件数・平均進捗率・修了率 | AC-ANALYTICS-007 |
| GET | `api/v1/instructor/courses/{course_id}/attendances/follow-up` | `instructor.courses.attendances.follow-up` | 要フォロー受講生 | AC-ANALYTICS-024 |
| GET | `api/v1/instructor/courses/{course_id}/attendances/expiring` | `instructor.courses.attendances.expiring` | 近日期限切れの受講生 | AC-ANALYTICS-028 |
| GET | `api/v1/instructor/courses/{course_id}/attendances/stuck-points` | `instructor.courses.attendances.stuck-points` | つまずき箇所 | AC-ANALYTICS-016 |

`{period}` に指定できる値は経路によって異なる。

| 経路 | 指定できる値 |
|---|---|
| ログイン率 | `week` / `month` / `year` |
| 完了件数の集計 | `today` / `month` |

### 受講生

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/instructor/students/index` | `instructor.students.index` | 受講生一覧 | AC-AUTHZ-016 |
| GET | `api/v1/instructor/students/{student_id}` | `instructor.students.show` | 受講生詳細 | AC-AUTHZ-001 |
| POST | `api/v1/instructor/students` | `instructor.students.store` | 受講生の代理登録と受講登録 | AC-ENROLL-008 |

### お知らせ

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/instructor/notifications/index` | `instructor.notifications.index` | お知らせ一覧 | AC-NOTIF-027 |
| POST | `api/v1/instructor/courses/{course_id}/notifications` | `instructor.courses.notifications.store` | お知らせの作成 | AC-NOTIF-001 |
| GET | `api/v1/instructor/notifications/{notification_id}` | `instructor.notifications.show` | お知らせ詳細 | AC-AUTHZ-004 |
| PUT | `api/v1/instructor/notifications/{notification_id}` | `instructor.notifications.put` | お知らせの更新 | AC-NOTIF-008 |
| DELETE | `api/v1/instructor/notifications/{notification_id}` | `instructor.notifications.delete` | お知らせの削除 | AC-NOTIF-013 |
| DELETE | `api/v1/instructor/notifications` | `instructor.notifications.bulk-delete` | お知らせの一括削除 | AC-NOTIF-009 |
| PUT | `api/v1/instructor/notifications/type` | `instructor.notifications.update-type` | 選択したお知らせの種類の変更 | AC-NOTIF-009 |
| PUT | `api/v1/instructor/notifications/type/all` | `instructor.notifications.update-type-all` | 全お知らせの種類の変更 | AC-NOTIF-010 |
| PUT | `api/v1/instructor/notifications/status` | `instructor.notifications.put-status` | 選択したお知らせの公開状態の変更 | AC-NOTIF-009 |
| PUT | `api/v1/instructor/notifications/status/all` | `instructor.notifications.put-status-all` | 全お知らせの公開状態の変更 | AC-NOTIF-010 |

### タグ

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/instructor/tags/index` | `instructor.tags.index` | タグ一覧 | AC-TAG-007 |
| POST | `api/v1/instructor/tags` | `instructor.tags.store` | タグの作成 | AC-TAG-001 |
| GET | `api/v1/instructor/tags/{tag_id}` | `instructor.tags.show` | タグ詳細 | AC-TAG-011 |
| PUT | `api/v1/instructor/tags/{tag_id}` | `instructor.tags.put` | タグの更新 | AC-TAG-004 |
| DELETE | `api/v1/instructor/tags/{tag_id}` | `instructor.tags.delete` | タグの削除 | AC-TAG-005 |

## マネージャー向け

接頭辞は `api/v1/manager` であり、講師認証の内側にある。

| メソッド | パス | ルート名 | 内容 | 仕様 |
|---|---|---|---|---|
| GET | `api/v1/manager/instructors/index` | `manager.instructors.index` | 配下の講師一覧 | AC-ANALYTICS-043 |
| POST | `api/v1/manager/instructors` | `manager.instructors.store` | 配下に入る講師の仮登録 | AC-ACCOUNT-017 |
| GET | `api/v1/manager/instructors/{instructor_id}` | `manager.instructors.show` | 講師詳細 | AC-AUTHZ-005 |
| POST | `api/v1/manager/instructors/{instructor_id}` | `manager.instructors.update` | 講師情報の更新 | AC-ACCOUNT-030 |
| GET | `api/v1/manager/instructors/{instructor_id}/courses/index` | `manager.instructors.courses.index` | 講師ごとの講座一覧 | AC-AUTHZ-002 |
| GET | `api/v1/manager/instructors/{instructor_id}/total-current-attendance-count` | `manager.instructors.total-current-attendance-count` | 講師ごとの受講中の受講生数 | AC-ANALYTICS-045 |
| GET | `api/v1/manager/courses/index` | `manager.courses.index` | 講座一覧 | AC-COMMON-006 |
| POST | `api/v1/manager/courses` | `manager.courses.store` | 講座の作成 | AC-AUTHOR-001 |
| GET | `api/v1/manager/courses/{course_id}` | `manager.courses.show` | 講座詳細 | AC-AUTHZ-002 |
| POST | `api/v1/manager/courses/{course_id}` | `manager.courses.update` | 講座の更新 | AC-AUTHOR-008 |
| PUT | `api/v1/manager/courses/status` | `manager.courses.put-status` | 公開状態の一括変更（対象の指定なし） | AC-AUTHOR-013 |
| POST | `api/v1/manager/courses/deadline/clear-selected` | `manager.courses.deadline.clear-selected` | 選択した講座の受講期限の取り消し | AC-ENROLL-016 |
| GET | `api/v1/manager/courses/tags/index` | `manager.courses.tags.index` | タグごとの講座一覧 | AC-TAG-009 |
| GET | `api/v1/manager/tags/{tag_id}` | `manager.tags.show` | タグ詳細 | AC-TAG-011 |
| DELETE | `api/v1/manager/chapters/{chapter_id}` | `manager.chapters.delete` | チャプターの削除 | AC-AUTHOR-023 |
| PATCH | `api/v1/manager/chapters/{chapter_id}/status` | `manager.chapters.update-status` | チャプターの公開状態の変更 | AC-AUTHOR-022 |
| GET | `api/v1/manager/courses/{course_id}/attendances/{period}` | `manager.courses.attendances.login-rate` | ログイン率 | AC-ANALYTICS-004 |
| GET | `api/v1/manager/courses/{course_id}/attendances/status/{period}` | `manager.courses.attendances.show-status` | 完了件数（絞り込みなし） | AC-ANALYTICS-012 |
| GET | `api/v1/manager/students/index` | `manager.students.index` | 受講生一覧 | AC-AUTHZ-002 |
| GET | `api/v1/manager/notifications/index` | `manager.notifications.index` | お知らせ一覧 | AC-NOTIF-028 |
| PUT | `api/v1/manager/notifications/{notification_id}` | `manager.notifications.put` | お知らせの更新 | AC-NOTIF-008 |
| DELETE | `api/v1/manager/notifications/{notification_id}` | `manager.notifications.delete` | お知らせの削除 | AC-NOTIF-013 |
| PUT | `api/v1/manager/notifications/type/all` | `manager.notifications.update-type-all` | 全お知らせの種類の変更 | AC-NOTIF-011 |
| PUT | `api/v1/manager/notifications/status/all` | `manager.notifications.put-status-all` | 全お知らせの公開状態の変更 | AC-NOTIF-011 |

マネージャー向けには、講師向けに存在する経路のうち一部が用意されていない（レッスンの操作、受講の登録と削除、要フォロー受講生、近日期限切れ、つまずき箇所、タグの作成と更新と削除など）。これらはマネージャーも講師向けの経路を利用する。

## 経路が重複している集計

同じ名前の集計でも、講師向けとマネージャー向けで対象範囲が異なる。

| 集計 | 講師向け | マネージャー向け |
|---|---|---|
| 完了件数 | 公開されているレッスンに絞り、平均進捗率と修了率も返す | 絞り込まず、完了したレッスン件数とチャプター件数のみ返す |
| ログイン率 | 同じ算出 | 同じ算出 |

## 応答の形

| 種別 | 形 |
|---|---|
| 登録・更新・削除 | `result` に真を返す。一括操作では `updated_count` または `deleted_count` を伴う |
| 一覧 | ページネーション情報を伴う一覧。形はエンドポイントによって異なり、リソースクラスの定義が正である |
| 単体取得 | リソースクラスの定義が正である |

応答の項目は `app/Http/Resources` のリソースクラスを正とする。この文書には転記しない。
