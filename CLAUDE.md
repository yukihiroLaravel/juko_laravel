# CLAUDE.md

このファイルは、このリポジトリのコードを操作する際のClaude Code (claude.ai/code) へのガイダンスを提供します。

## プロジェクト概要

これは受講管理アプリのLaravelベースのeラーニングプラットフォームで、講座、講師、生徒、および学習進捗を管理します。
アプリケーションはPHP 8.3のLaravel 11を使用し、生徒、講師、管理者の3種類のユーザー向けのAPIエンドポイントを含みます。

## 開発コマンド

### コード品質・解析
```bash
# コードフォーマット（Rector + Laravel Pint を順次実行）
composer format

# 静的解析（PHPStan レベル5）
composer analyze

# Rector ドライラン（変更内容の確認のみ）
composer rector-dry-run
```

### テスト
```bash
# Docker経由でテストを実行
docker compose exec app bash -c 'cd laravelapp && php artisan test'
```

## アーキテクチャ

### データモデル
アプリケーションは階層的な講座構造に従います。
- 「講師」が「講座」を作成・管理
- 「講座」は「チャプター」を含む（順序付き）
- 「チャプター」は「レッスン」を含む（順序付き）
- 「生徒」は「受講」を通じて「講座」に登録
- 「レッスン受講」は個別の「レッスン進捗」を追跡

受講（Attendance）とレッスン受講（LessonAttendance）の仕様
- 受講登録時に、講座内の「下書きではない」レッスン（`status` が `public` または `private`）に対して `LessonAttendance` レコードが `status = before_attendance` で一括作成される。下書き（`draft`）のレッスンには作成しない
- レッスンが新規追加された場合も、その講座の全受講生に対して `LessonAttendance` レコードが自動作成される
- 下書きのレッスンが `public` / `private` に切り替わったタイミングで、その時点の既存受講生に対して `LessonAttendance` を作成する設計とする（受講状況のライフサイクルを一貫させるため。なおこの補完処理の実装は別途対応）
- つまり `LessonAttendance` は、下書きを除く公開対象（`public` / `private`）のレッスンについて、未着手のものも含めて全件分のレコードが存在する前提である
- `LessonAttendance` のステータスは `before_attendance` → `in_attendance` → `completed_attendance` の順に遷移する
- レッスン完了の判定は `completed_at` カラム（完了日時）を正とする。`status` はUIでの表示用ステータスであり、完了判定には `completed_at IS NOT NULL` を使用する
- `LessonAttendance.completed_at` は、一度 `status = completed_attendance` で記録されたら、その後ステータスが `in_attendance` や `before_attendance` に戻されても `null` には戻さない。「過去に1度でも完了した事実」を不変な履歴として保持するため、`completed_at` は単調に保たれる（一度設定されたら以降は上書きしない）
- 受講期限（`Attendance.attendance_deadline`）は日付（date）として保存され、判定時はその日の終端（当日 23:59:59）まで有効として扱う。期限切れ判定（`Attendance::isExpired()`）は、現在時刻が「期限日 23:59:59」を過ぎたかで行う
- `Attendance.attendance_deadline` が `null` の場合は期限なし（無期限受講）とみなす

主な関係性
- 「マネージャー-講師階層」（講師は他の講師を管理可能）
- 「分類のための講座タグシステム」
- 「講座固有の告知のための通知システム」
- 「生徒と講師両方の一時登録システム」

### API構造
APIは`/api/v1/`の下に役割ベースのミドルウェアで整理されています：

認証
- API認証にLaravel Sanctumを使用
- ミドルウェア（`student`、`instructor`、`manager`）による役割ベースのアクセス制御

APIエンドポイント
- `/api/v1/student/*` - 生徒操作（`student`ミドルウェアが必要）
- `/api/v1/instructor/*` - 講師操作（`instructor`ミドルウェアが必要）
- `/api/v1/manager/*` - 管理者操作（`manager`ミドルウェアが必要）

### サービス層
ビジネスロジックは`app/Services/`の下のサービスで整理されています：
- `Attendance/` - 受講管理サービス
- `Auth/` - 認証サービス
- `Chapter/` - チャプター管理サービス
- `Course/` - 講座管理サービス
- `Instructor/` - 講師管理サービス
- `Lesson/` - レッスン管理サービス
- `Notification/` - 通知管理サービス
- `Student/` - 生徒関連サービス
- `Tag/` - タグ管理サービス

### 認可（Policy）
`app/Policies/`でモデルごとの認可ロジックを管理：
- `CoursePolicy` - 講座の閲覧・編集・削除権限
- `ChapterPolicy` - チャプターの操作権限
- `LessonPolicy` - レッスンの操作権限
- `AttendancePolicy` - 受講の操作権限
- `NotificationPolicy` - 通知の操作権限
- `InstructorPolicy` - 講師の操作権限
- `StudentPolicy` - 生徒の操作権限
- `TagPolicy` - タグの操作権限

### リクエストバリデーション
フォームリクエストをバリデーションに使用：
- `app/Http/Requests/Auth/` - 認証リクエスト
- `app/Http/Requests/Instructor/` - 講師固有リクエスト
- `app/Http/Requests/Manager/` - 管理者固有リクエスト
- `app/Http/Requests/Student/` - 生徒固有リクエスト

### APIリソース
Eloquentリソースを使用した一貫性のあるAPIレスポンス：
- `app/Http/Resources/Student/` - 生徒APIレスポンス
- `app/Http/Resources/Instructor/` - 講師APIレスポンス
- `app/Http/Resources/Manager/` - 管理者APIレスポンス

## コード品質基準

- 120文字行制限のPSR-12コーディング標準
- PHPStan レベル5静的解析
- コードフォーマットにLaravel Pint
- 自動コードリファクタリングとアップグレードにRector
- ほとんどのモデルで論理削除が有効
- 配列操作よりコレクションメソッドを優先し、宣言的に記述する
- PHPの標準関数`empty()`の利用を避ける（`=== null`、`=== ''`、`->isEmpty()`等で明示的に判定する）
- PHP・Laravelともに最新のメソッドや機能を積極的に利用する

## テスト設定

- テストにSQLiteインメモリデータベースを使用
- 分離されたテストスイート：Unit と Feature
- PHPUnit設定はカバレッジレポートをサポート
- 高速実行のため並列でテストを実行可能
- テストメソッド名にはレスポンスキーや変数名（`average_progress_rate` のような snake_case の英字）をそのまま使わず、対応する日本語名称（`平均進捗率` 等）で命名する

## 主要な依存関係

- dedoc/scramble - APIドキュメント生成
- laravel/sanctum - API認証
- barryvdh/laravel-ide-helper - IDEサポート
- larastan/larastan - Laravel用静的解析
- rector/rector - 自動リファクタリング

## 重要な注意事項

- すべてのタイムスタンプと日付は日本のロケールを考慮すべき
- アプリケーションは論理削除を広く使用
- 講座内容には`storage/app/public/course/`に保存される画像アップロードを含む
- 認証は従来のセッションとSanctum経由のAPIトークンの両方を使用

## コーディング規約

Laravelの汎用的なベストプラクティスは `laravel-best-practices` スキルを参照する。以下は当プロジェクト固有の規約であり、汎用ルールと衝突する場合はこちらを優先する。

既存コードには本規約が策定される前の書き方（複数形テーブル、`id` 主キー、複数アクションのコントローラー等）が残っている。新規実装・修正時に本規約を適用する。

### 配置・命名の共通原則

すべてのレイヤー（Controller / FormRequest / Service / Resource / Enum / Test）は、コンテキストごとのサブディレクトリに配置し、`{行う処理}{接尾辞}.php` で命名する。

### コントローラー

- シングルアクションコントローラー（`__invoke` のみを持つ）で実装する
  - 例: ユーザー登録API → `app/Http/Controllers/Api/User/StoreController.php`
- サービスクラスはメソッドインジェクションで受け取る（コンストラクタインジェクションにしない）
- データベーストランザクションはコントローラーで管理する（`DB::transaction()` でサービス呼び出しを囲む）

### バリデーション

- FormRequestで実装し、1エンドポイント1ファイルとする（複数エンドポイントでの共有を禁止）
- Enumの許可値制御は `Rule::enum(GenderEnum::class)` を使う（`in:1,2,9` のようなリテラル列挙をしない）

### サービス

- 複数のモデルが関与するビジネスロジックを置く（単一モデルで完結するならモデルに置く）
- 公開メソッドは `__invoke` 1つのみとし、フローの可視化を優先して必要なら private 関数で責務を分離する
- 入力値は `FormRequest::validated()` の配列で受け、認証ユーザーなど validated 以外のコンテキストは引数で明示的に受け取る
- サービス内ではトランザクションを張らない（コントローラーの責務）

### モデル

- キャストを必ず定義し、明示的なphpdocを記述する
- セキュリティ性が高いカラム（password、token等）は `$hidden` に設定する
- 識別子カラムに定義したEnumは必ずモデルでキャストする

### マイグレーション・テーブル設計

- テーブル名は単数形にする（例: `user`、`student`）
- プライマリーキーは `{テーブル名}_id` とし、モデルに `protected $primaryKey` を宣言する
- 外部キーは `foreignIdFor` と `constrained` を使う。主キーが `id` ではないため名前推測が効かず、参照先を明示する
  - 例: `foreignIdFor(User::class, 'user_id')->constrained(table: 'user', column: 'user_id')`
- テーブルとカラムには必ず `->comment()` をつける
- `timestamp` 型は2038年問題のため避けて `datetime` を使う。`timestamps()` ヘルパも同様に使わない

### Enum

- テーブルの識別子カラムにはEnumを活用し、`app/Enums/{コンテキスト}/~Enum.php` で定義する
- バッキング型は格納値の性質で選ぶ。状態・種別を表すカラム（`status`、`type` 等）は string backed を既定とする

### ルーティング

- 複数形を利用する（例: ユーザー登録 → `POST users`）
- コンテキストに合わせて `prefix` と `name` を活用し、エンドポイントには必ず `name()` を定義する

### Resource

- APIレスポンスのスキーマに利用し、ビジネスロジックの実装を禁止する（整形・表示のみ）

### 型・PHP全般

- 関数の返り値の型と引数のタイプヒントを必ず定義する
- 日付ライブラリは特に理由がない限り `CarbonImmutable` を利用する
- `config` 以外での `env()` 関数を禁止する

### テスト

- 対象のController / Serviceの配置に対応したサブディレクトリに `{行う処理}Test.php` で作成する
- AAAパターンを利用し、必ずコメントで `Arrange` / `Act` / `Assert` を記述する
- テスト関数名は日本語で書き、システム用語を避けて業務・ユーザー視点の言葉を使う
- 参照データ（マスタ等）はシーダー、それ以外（ユーザー等）はファクトリーで用意する
- 具体例に加えて、入力の全域で成り立つ性質（可逆性・冪等性・不変条件・可換性）に着目したテストを検討する

### レビュー時の重大度

コード差分やPRをレビューする際は、指摘ごとに重大度を付ける。

- `must` … セキュリティ・データ整合性・規約の必須違反（env直接利用、認可漏れ、外部キー未設定、トランザクション欠如）
- `should` … 設計規約からの逸脱、テスト不足（単一アクション化されていない、配置・命名違反、型注釈漏れ、振る舞い変更にテストがない）
- `nits` … 軽微な指摘・好みの範囲
- 判断原則: データ整合性・セキュリティに直結すれば `must`、設計・命名・配置・型の規約逸脱は `should`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v11
- rector/rector (RECTOR) - v2

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the new Laravel structure unless the user explicitly requests it.

## Laravel 10 Structure

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
