# API仕様書

本アプリケーションのAPIエンドポイントについて説明する。

## 共通仕様

- ベースURL: `/api/v1/`
- 認証: Laravel Sanctum（`Authorization: Bearer {token}` ヘッダー）
- レスポンス形式: 全てJSON
- 主なエラーコード: 401（未認証）、403（権限不足）、404（未発見）、422（バリデーションエラー）

---

## 認証・仮登録API（認証不要）

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `student/` | 生徒 仮登録 |
| POST | `student/verification/{token}` | 生徒 認証コード検証 |
| POST | `instructor/` | 講師 仮登録 |
| POST | `instructor/verification/{token}` | 講師 認証コード検証 |

---

## 生徒API（`student` ミドルウェア）

### プロフィール・履歴

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `student/` | プロフィール取得 |
| POST | `student/update` | プロフィール更新 |
| GET | `student/login-histories` | ログイン履歴取得 |

### 受講

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `attendance/index` | 受講一覧（検索・タグフィルタ対応） |
| GET | `attendance/{id}` | 受講詳細 |
| GET | `attendance/{id}/progress` | 受講進捗 |
| PUT | `attendance/{id}/complete` | 全チャプター一括完了 |
| PUT | `attendance/{id}/chapter/{chapter_id}/complete` | チャプター内全レッスン一括完了 |

### レッスン受講

| メソッド | パス | 説明 |
|---------|------|------|
| PATCH | `lesson_attendance/{id}` | レッスンステータス更新 |

### お知らせ

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `notification/index` | お知らせ一覧（ページネーション・フィルタ対応） |
| POST | `notification/mark-read` | お知らせ既読マーク |
| GET | `notification/{id}` | お知らせ詳細 |

---

## 講師API（`instructor` ミドルウェア）

### 講師プロフィール

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `instructor/` | プロフィール取得 |
| POST | `instructor/update` | プロフィール更新 |
| GET | `instructor/tag/index` | 自分のタグ一覧 |

### 講座管理

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `instructor/course/index` | 講座一覧（検索・タグフィルタ・ページネーション対応） |
| POST | `instructor/course/` | 講座作成 |
| GET | `instructor/course/{id}` | 講座詳細 |
| POST | `instructor/course/{id}` | 講座更新 |
| DELETE | `instructor/course/{id}` | 講座削除 |
| PUT | `instructor/course/status` | ステータス一括更新 |
| DELETE | `instructor/course/` | 講座一括削除 |
| PATCH | `instructor/course/deadline` | 期限一括更新 |
| GET | `instructor/course/tag/index` | 講座タグ一覧 |

### チャプター管理

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `instructor/course/{id}/chapter/` | チャプター作成 |
| GET | `instructor/course/{id}/chapter/{chapter_id}` | チャプター詳細 |
| PUT | `instructor/course/{id}/chapter/{chapter_id}` | チャプター更新 |
| POST | `instructor/course/{id}/chapter/sort` | チャプター並び替え |
| PUT | `instructor/course/{id}/chapter/status` | ステータス一括更新 |
| PATCH | `instructor/course/{id}/chapter/status` | ステータス個別更新 |
| DELETE | `instructor/course/{id}/chapter/` | チャプター一括削除 |
| DELETE | `instructor/course/{id}/chapter/all` | チャプター全削除 |

### レッスン管理

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `instructor/course/{id}/chapter/{chapter_id}/lesson/` | レッスン作成 |
| PUT | `instructor/course/{id}/chapter/{chapter_id}/lesson/{lesson_id}` | レッスン更新 |
| DELETE | `instructor/course/{id}/chapter/{chapter_id}/lesson/{lesson_id}` | レッスン削除 |
| PATCH | `.../lesson/{lesson_id}/status` | レッスンステータス更新 |
| PATCH | `.../lesson/{lesson_id}/title` | レッスンタイトル更新 |
| POST | `instructor/course/{id}/chapter/{chapter_id}/lesson/sort` | レッスン並び替え |
| PUT | `instructor/course/{id}/chapter/{chapter_id}/lesson/status` | ステータス一括更新 |
| DELETE | `instructor/course/{id}/chapter/{chapter_id}/lesson/` | レッスン一括削除 |
| DELETE | `instructor/course/{id}/chapter/{chapter_id}/lesson/all` | レッスン全削除 |

### タグ管理

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `instructor/tag/` | タグ作成 |
| GET | `instructor/tag/{id}` | タグ詳細 |
| PUT | `instructor/tag/{id}` | タグ更新 |
| DELETE | `instructor/tag/{id}` | タグ削除 |

### 受講管理・分析

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `instructor/attendance/` | 受講登録（生徒を講座に登録） |
| GET | `instructor/attendance/{id}` | 受講詳細 |
| GET | `instructor/attendance/{id}/status` | 生徒の受講状況 |
| DELETE | `instructor/attendance/{id}` | 受講削除 |
| GET | `instructor/course/{id}/attendance/status/{period}` | 受講状況（期間別） |
| GET | `instructor/course/{id}/attendance/{period}` | ログイン率（期間別） |

### 生徒管理

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `instructor/student/index` | 生徒一覧 |
| GET | `instructor/student/{id}` | 生徒詳細 |
| POST | `instructor/student/` | 生徒作成（受講登録付き） |

### お知らせ管理

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `instructor/notification/index` | お知らせ一覧 |
| POST | `instructor/course/{id}/notification/` | お知らせ作成 |
| GET | `instructor/notification/{id}` | お知らせ詳細 |
| PUT | `instructor/notification/{id}` | お知らせ更新 |
| DELETE | `instructor/notification/{id}` | お知らせ削除 |
| PUT | `instructor/notification/type/` | 種別一括更新 |
| PUT | `instructor/notification/type/all` | 種別全更新 |
| PUT | `instructor/notification/status/` | ステータス一括更新 |
| PUT | `instructor/notification/status/all` | ステータス全更新 |
| DELETE | `instructor/notification/` | お知らせ一括削除 |

---

## 管理者API（`manager` ミドルウェア）

管理者は講師の上位ロールであり、自分に加えて配下講師のリソースを横断的に管理できる。

### 講師管理

| メソッド | パス | 説明 |
|---------|------|------|
| POST | `manager/instructor/` | 講師仮登録 |
| GET | `manager/instructor/index` | 講師一覧 |
| GET | `manager/instructor/{id}` | 講師詳細 |
| POST | `manager/instructor/{id}` | 講師更新 |
| GET | `manager/instructor/{id}/course/index` | 講師の講座一覧 |

### 講座管理

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `manager/course/index` | 講座一覧 |
| POST | `manager/course/` | 講座作成 |
| GET | `manager/course/{id}` | 講座詳細 |
| POST | `manager/course/{id}` | 講座更新 |
| PUT | `manager/course/status` | ステータス一括更新 |
| POST | `manager/course/deadline/clear-selected` | 選択講座の期限クリア |
| GET | `manager/course/tag/index` | 講座タグ一覧 |

### チャプター・レッスン管理

| メソッド | パス | 説明 |
|---------|------|------|
| DELETE | `manager/course/{id}/chapter/{chapter_id}` | チャプター削除 |
| PATCH | `manager/course/{id}/chapter/{chapter_id}/status` | チャプターステータス更新 |
| DELETE | `manager/course/{id}/chapter/{chapter_id}/lesson/` | レッスン一括削除 |

### タグ・生徒・受講分析

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `manager/tag/{id}` | タグ詳細 |
| DELETE | `manager/tag/{id}` | タグ削除 |
| GET | `manager/student/index` | 生徒一覧 |
| GET | `manager/course/{id}/attendance/status/{period}` | 受講状況（期間別） |
| GET | `manager/course/{id}/attendance/{period}` | ログイン率（期間別） |

### お知らせ管理

| メソッド | パス | 説明 |
|---------|------|------|
| GET | `manager/notification/index` | お知らせ一覧 |
| PUT | `manager/notification/type/all` | 種別全更新 |
| PUT | `manager/notification/status/all` | ステータス全更新 |
| PUT | `manager/notification/{id}` | お知らせ更新 |
| DELETE | `manager/notification/{id}` | お知らせ削除 |
