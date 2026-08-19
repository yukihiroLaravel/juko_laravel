# CLAUDE.md

このファイルは、このリポジトリのコードを操作する際のClaude Code (claude.ai/code) へのガイダンスを提供します。

受講管理アプリのバックエンドAPIである。講師が講座を用意して公開し、受講生が講座を受講して学習を進め、講師が受講生の学習状況を把握してフォローする。PHP 8.3 の Laravel 12 で実装し、受講生・講師・マネージャーの3区分にAPIを提供する。

このファイルは入口である。仕様と規約の実体は `docs/` にあり、ここには索引と、取り違えると壊れるものだけを置く。詳細をこのファイルに書き足さない。

## ドキュメントの索引

| 知りたいこと | 置き場 |
|---|---|
| 用語の意味・業務のルール | `docs/domain/` |
| システムの振る舞い（受け入れ基準） | `docs/specs/` |
| 技術構成・データモデル・エンドポイント | `docs/architecture/` |
| コーディング規約 | `docs/architecture/coding-standards.md` |
| 設計判断の経緯 | `docs/adr/` |
| 未確定事項 | `docs/domain/open-questions.md` |
| これから作る変更の起票 | `docs/changes/template.md` |
| ドキュメントの書き方 | `docs/documentation-rules.md` |

全体の入口は `docs/README.md` にある。ID を見かけたら `grep -rn 'BR-SHARED-006' docs/` で引く。

## 実装前に必ず押さえる

取り違えると壊れるものだけを挙げる。背景はリンク先を読む。

- レッスンの完了判定は完了日時で行う。表示用の段階では判定しない。完了日時は一度記録したら上書きせず、段階が戻っても取り消さない（BR-SHARED-012・ADR-0002）
- 受講状況は、下書きを除くすべてのレッスンについて未着手の分まで存在する前提である。受講の登録時とレッスンの公開時の両方で作る（ADR-0005）
- 公開状態は下書きへ戻せない。取り下げるときは非公開にする（BR-SHARED-008・ADR-0004）
- 受講期限は受講ごとに確定した日付を持つ。判定はその日の終わり（23時59分59秒）まで有効とする。期限なしは期限切れにならない（BR-SHARED-006・ADR-0003）
- 講師の権限はマネージャーの配下を含む。認可の判定基準は `docs/specs/features/authorization.md` に集約している
- 受講生に見せてよいのは公開されている講座・チャプター・レッスンだけである（BR-SHARED-009）

## 用語

`docs/domain/shared/glossary.md` を唯一の正とする。講座・チャプター・レッスン・受講・受講状況の表記を揺らさない。言い換えや併記をしない。

## よく使うコマンド

```bash
# コードフォーマット（Rector + Laravel Pint を順次実行）
composer format

# 静的解析（PHPStan レベル5）
composer analyze

# テスト
docker compose exec app bash -c 'cd laravelapp && php artisan test'
```

## 実装のルール

- コーディング規約は `docs/architecture/coding-standards.md` に従う。Laravelの汎用的なベストプラクティスは `laravel-best-practices` スキルを参照し、衝突する場合はプロジェクトの規約を優先する
- ドキュメントを書くときは `documentation-conventions` スキルと `docs/documentation-rules.md` に従う

禁止事項。

- `config` 以外で `env()` を呼ばない
- 状態や種別の値をクラス定数で増やさない。`app/Enums/{コンテキスト}/` にEnumを定義する
- 認可の判定をコントローラーに散らさない。Policyに置く
- サービス内でトランザクションを張らない。トランザクションはコントローラーの責務である
- 変更の起票にテストの計画を書かない

## 実装が終わったら

同じ変更のなかでドキュメントを更新する。更新先の対応表は `docs/documentation-rules.md` にある。

- 振る舞いを変えた → `docs/specs/` の該当ファイルをIDを維持して上書きする
- データ構造やエンドポイントを変えた → `docs/architecture/`
- 業務ルールが判明・変更された → `docs/domain/shared/business-rules.md`
- 技術選定の判断をした → `docs/adr/` に新しい記録を追加する

文書を増やさず、既存の記述を上書きする。同じ主題が既に書かれていないかを `grep -rn` で確かめてから書く。

## 仕様が不足しているとき

- まず `docs/domain/open-questions.md` を見る。未確定として記録済みかもしれない
- 記録があるならその暫定方針に従う。暫定で実装するときはコードに `TODO(Q-NNN)` を残す
- 記録がなく判断できないときは、勝手に決めずに確認する。決め切れないまま進めるなら `Q-<連番>` として起票してから実装する

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
