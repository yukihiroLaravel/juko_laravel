# 全体構成

技術スタックと実装の構成を記述する。振る舞いは `docs/specs/` に、業務ルールは `docs/domain/` にあり、ここでは「どう組み立てているか」だけを扱う。

この文書と `data-model.md`・`api.md` は開発者向けであり、クラス名・テーブル名・カラム名をそのまま記載する。`docs/domain/` と `docs/specs/` では実装の名前を出さない方針であり、その対応関係はここが受け持つ。

## リポジトリの位置づけ

モノレポの一部であり、このディレクトリはバックエンド API を担当する。

| 場所 | 役割 |
|---|---|
| `backend/laravelapp` | この API サーバー |
| `frontend/juko_next` | 受講生・講師向けの画面（Next.js） |
| `api/` | 外部共有用の OpenAPI 定義 |
| `Docker/` | ローカル実行環境の定義 |

## 技術スタック

| 項目 | 内容 |
|---|---|
| フレームワーク | Laravel 12 |
| PHP | 8.3 以上 |
| データベース | MySQL 5.7（テスト時は SQLite のインメモリ） |
| 認証 | Laravel Sanctum（セッションを用いたステートフル認証） |
| API ドキュメント生成 | dedoc/scramble |
| 静的解析 | PHPStan（larastan・レベル5） |
| コード整形 | Laravel Pint・Rector |
| テスト | PHPUnit 11 |
| タイムゾーン | Asia/Tokyo |

## ローカル実行環境

| コンテナ | 内容 |
|---|---|
| `app` | PHP 8.3 + Apache。ホストの 8080 番で公開 |
| `db` | MySQL 5.7。ホストの 13306 番で公開。タイムゾーンは Asia/Tokyo |
| `adminer` | データベース閲覧用。ホストの 8088 番で公開 |

## 認証と利用者の区分

| 区分 | ガード | 経路の入口 |
|---|---|---|
| 受講生 | `web`（プロバイダは `App\Model\Student`） | `POST /login` |
| 講師・マネージャー | `instructor`（プロバイダは `App\Model\Instructor`） | `POST /login/instructor` |

区分の判定はミドルウェアで行う。

| ミドルウェア | 判定 |
|---|---|
| `EnsureIsStudent` | 受講生として認証済みであり、講師として認証されていないこと |
| `EnsureIsInstructor` | 講師として認証済みであること |
| `EnsureIsManager` | 認証済みの講師の `type` が `manager` であること |

`ForceJsonResponse` がすべての要求に `Accept: application/json` を設定するため、例外は常に JSON で応答する。`CorsMiddleware` が画面側のオリジンからの要求を許可する。

## ルーティングの構成

`routes/api.php` がミドルウェア・接頭辞・名前のネストだけを担い、エンドポイントの定義をロール別のファイルに分割している。

| ファイル | 範囲 |
|---|---|
| `routes/api/student.php` | `auth:sanctum` + `v1` + `student` / 名前は `student.` |
| `routes/api/instructor.php` | `auth:sanctum` + `v1` + `instructor` + 接頭辞 `instructor` / 名前は `instructor.` |
| `routes/api/manager.php` | 上記に加えて `manager` + 接頭辞 `manager` / 名前は `manager.` |
| `routes/api/guest.php` | 認証不要（仮登録と認証コードの検証） |

マネージャー向けの経路は講師認証の内側にネストしている。ログインとログアウトは `routes/web.php` にある。

## レイヤと責務

| レイヤ | 場所 | 責務 |
|---|---|---|
| コントローラー | `app/Http/Controllers/Api/{Student,Instructor,Manager}` | 入力の受け取り、認可の起動、トランザクションの管理、応答の組み立て |
| フォームリクエスト | `app/Http/Requests/{Student,Instructor,Manager}` | 入力値の検証。1エンドポイント1クラス |
| 検証ルール | `app/Rules` | 複数箇所で使う値の検証（並び替え項目・期間・状態など） |
| サービス | `app/Services/{コンテキスト}` | 複数のモデルが関わる処理。公開メソッドは `__invoke` |
| ポリシー | `app/Policies` | 対象ごとの認可判定 |
| モデル | `app/Model` | テーブルとの対応、リレーション、単一モデルで完結する判定 |
| Enum | `app/Enums/{コンテキスト}` | 状態・種別の値 |
| DTO | `app/Dto` | サービスの入出力の受け渡し |
| リソース | `app/Http/Resources` | 応答の整形。ビジネスロジックを持たない |

トランザクションはコントローラーで張る。サービス側で張っているものもあり（受講の登録、受講生の代理登録、定員と期限の一括更新など）、これは占有ロックと組み合わせる必要があるためである。

## 状態と Enum の対応

| Enum | 対応するカラム | 値 |
|---|---|---|
| `App\Enums\Course\StatusEnum` | `courses.status` | `draft` / `public` / `private` |
| `App\Enums\Chapter\StatusEnum` | `chapters.status` | `draft` / `public` / `private` |
| `App\Enums\Lesson\StatusEnum` | `lessons.status` | `draft` / `public` / `private` |
| `App\Enums\Course\DeadlineTypeEnum` | `courses.deadline_type` | `none` / `fixed_date` / `relative_days` |
| `App\Enums\Notification\StatusEnum` | `notifications.status` | `public` / `private` |
| `App\Enums\Notification\TypeEnum` | `notifications.type` | `always` / `once` |
| `App\Enums\Notification\FilterEnum` | 入力値のみ | `read` / `unread` |
| `App\Enums\Student\Gender` | `students.gender` | `unknown` / `man` / `woman` |

`lesson_attendances.status` と `instructors.type` はモデルのクラス定数で管理しており、Enum に移行していない。

| クラス定数 | 対応するカラム | 値 |
|---|---|---|
| `LessonAttendance::STATUS_*` | `lesson_attendances.status` | `before_attendance` / `in_attendance` / `completed_attendance` |
| `Instructor::TYPE_*` | `instructors.type` | `instructor` / `manager` |

## 現行コードと規約の差異

`CLAUDE.md` のコーディング規約は、規約策定後に書くコードへ適用する。既存コードには策定前の書き方が残っており、次の点が規約と異なる。

| 項目 | 規約 | 既存コード |
|---|---|---|
| テーブル名 | 単数形 | 複数形（`courses`・`students` など） |
| 主キー | `{テーブル名}_id` | `id` |
| コントローラー | シングルアクション | 1クラスに複数のアクション |
| 状態値の管理 | Enum | 一部はモデルのクラス定数 |
| タイムスタンプ | `datetime` 型で明示 | 一部のテーブルで `timestamps()` ヘルパを使用 |

## コード品質

| コマンド | 内容 |
|---|---|
| `composer format` | Rector と Pint を順に実行する |
| `composer analyze` | PHPStan（レベル5）を実行する |
| `composer rector-dry-run` | Rector の変更内容だけを確認する |
| `docker compose exec app bash -c 'cd laravelapp && php artisan test'` | テストを実行する |

行の長さは 120 文字を上限とする PSR-12 に従う。

## テスト

| 種別 | 場所 | 方針 |
|---|---|---|
| フィーチャーテスト | `tests/Feature` | エンドポイントとサービスの振る舞いを検証する。対象の配置に対応したサブディレクトリに置く |
| ユニットテスト | `tests/Unit` | 単体で完結する計算を検証する |

テストは SQLite のインメモリデータベースで実行する。テスト関数名は日本語で書き、業務・利用者の視点の言葉を使う。参照データはシーダー、それ以外はファクトリで用意する。

## API ドキュメントの生成

dedoc/scramble が OpenAPI 定義を生成する。リポジトリ直下の `api.json` は生成した時点のもので、ルートの変更に追従していない場合がある。現在のエンドポイントを確認する際は `php artisan route:list --except-vendor` を正とする。`docs/architecture/api.md` はこの一覧をもとに整理したものである。
