# CLAUDE.md

受講管理アプリのバックエンド API である。講師が講座を用意して公開し、受講生が講座を受講して学習を進め、講師が受講生の学習状況を把握してフォローする。PHP 8.3 の Laravel 13 で実装し、受講生・講師・マネージャーの3区分に API を提供する。

このリポジトリは、受講生が AI を使った開発を体験する場でもある。受講生は仕様を言葉にして渡し、出てきたプランと結果を判断する。開発の経験が浅い人が多いので、各段の初めに、いまどこにいて次に何が起きるかを1文で伝える。黙って進めない。

このファイルは入口である。書き方の規約は `.ai/rules/`、仕様は `docs/` にあり、ここには進め方と、取り違えると壊れるものだけを置く。

## 進め方

コードに手を入れる依頼は、次の順に進める。起動時はプランモードになっている。

1. プランモードで、依頼の文面をそのまま `planner` エージェントに渡し、調査とプランの作成を任せる。planner はチケットを読めないので、依頼にチケットがあれば本文も一緒に渡す
2. 返ってきたプランをプランファイル（`.claude/plans/`）に書き、承認を求める。承認はこの1回だけである。プランの「承認の前に決めてほしいこと」は、承認と一緒に答えてもらう。差し戻されたら planner に戻す。プランに「既存のテストで確かめる」とあれば、承認を求める前にそのテストが実在するかを確かめる
3. 承認されたら、プランの「タスク」を依存つきでタスクリストに登録し、下の雛形で /goal の達成条件を提案する。受け入れの操作が出ない環境では、利用者に `/goal <条件>` を入力して設定してもらう。これは作業を終える条件の設定であり、プランの承認とは別である
4. タスクの順に実装する。確かめることを API のテストとして先に書き、落ちることを確かめてから実装する。今の振る舞いを守るテストは最初から通るので、実装を一時的に壊して落ちることを確かめてから戻す。プランの対象外に手を出さない。途中で必要だと分かったら、広げずに利用者へ伝える
5. 作業を終える前に、Stop フックが `scripts/dc composer check-all` を流す。落ちたら直す。前回通ったときから作業ツリーが変わっていなければ流さない。テストが落ちたままの途中で利用者に確認を求めて止まると、フックが1回だけ作業を続けさせる。そのときは確認を優先し、検査の結果を添えてもう一度止まってよい
6. 評価する。`security-reviewer`・`performance-reviewer`・`test-coverage-reviewer`・`design-reviewer`・`writing-reviewer` を並列に起動し、比べる基準（切った元のブランチとの merge-base。既定は `git merge-base origin/develop HEAD`）とプランのパスを渡す。作業はコミットしていなくてよい。must の指摘を直し、直した観点だけをもう一度評価させる。評価を3回行っても must が残ったら、止めて残った指摘を利用者に報告する。`writing-reviewer` の指摘は重大度を持たないので、食い違いは直し、冗長と読みにくさは判断して直す。やり直しの判定には含めない
7. 終わったら、PR の説明に書く材料を利用者に渡す。プランの「確かめること」「対象外」、プランから変えたところとその理由、期待値を変えた既存のテストとその理由、評価で「確認が要る」とされたものである。PR は受講生が自分で出す

プランは git の管理に入れない。経緯は PR に残す。

ドキュメントや `.ai/rules/` だけを直すときは、プランを作らずに直してよい。終わったら、変更したファイルを `writing-reviewer` に渡して点検させる。

/goal の達成条件の雛形は次のとおりである。

```
次をすべて満たしている。
- scripts/dc composer check-all が通っている
- プランの「確かめること」の各項目に、AC-ID 付きの API のテストがあり、通っている
- プランの「反映する docs」をすべて反映した
- 5つの評価で must の指摘が残っていない。または、評価を3回行っても残った must を一覧にして利用者に報告した
- プランの「対象外」に挙げたものに手を出していない
```

Boost のガイドラインにある「頼まれたときだけ文書を作る」は、プランの「反映する docs」に挙げた更新には当てはまらない。

## 実装前に必ず押さえる

取り違えると壊れるものだけを挙げる。背景はリンク先を読む。

- レッスンの完了判定は完了日時で行う。表示用の段階では判定しない。完了日時は一度記録したら上書きせず、段階が戻っても取り消さない（BR-SHARED-012・ADR-0002）
- 受講状況は、下書きを除くすべてのレッスンについて未着手の分まで存在する前提である。受講の登録時とレッスンの公開時の両方で作る。例外として、講師が受講生を代理登録する経路では作らない（ADR-0005・Q-011）
- 公開状態は下書きへ戻せない。取り下げるときは非公開にする（BR-SHARED-008・ADR-0004）
- 受講期限は受講ごとに確定した日付を持つ。判定はその日の終わり（23時59分59秒）まで有効とする。期限なしは期限切れにならない（BR-SHARED-006・ADR-0003）
- マネージャーの権限は配下の講師の分を含む（BR-SHARED-003）。認可の判定基準は `docs/specs/features/authorization.md` に集約している
- 受講生に見せてよいのは公開されている講座・チャプター・レッスンだけである（BR-SHARED-009）
- 構成は Laravel 10 の形のまま運用している。ミドルウェアは `app/Http/Kernel.php`、例外処理は `app/Exceptions/Handler.php`、コマンドとスケジュールは `app/Console/Kernel.php` に登録する。公式ドキュメントや Boost が示す `bootstrap/app.php` の `withMiddleware` などは、ここでは効かない

## テストの方針

- 受け入れ基準は API の振る舞いとして確かめる。テストは API のテストを基本にし、サービスやモデルを直接呼ぶテストは新しく書かない。例外は `.ai/rules/tests.md` にある
- 確かめることには AC-ID を付け、仕様（`docs/specs/`）とテストを `grep -rn 'AC-ENROLL-002'` で同時に引けるようにする
- 認可は区分（受講生・講師・マネージャー）ごとに、拒否される場合まで確かめる
- テストの DB は SQLite のインメモリである。開発環境と本番の MySQL と動きが変わらないよう、データベース固有の SQL を書かない

書き方は `.ai/rules/tests.md` にある。

## 用語

`docs/domain/shared/glossary.md` を唯一の正とする。講座・チャプター・レッスン・受講・受講状況の表記を揺らさない。言い換えや併記をしない。

## 仕様が不足しているとき

- まず `docs/domain/open-questions.md` を見る。未確定として記録済みかもしれない
- 記録があるならその暫定方針に従う。暫定で実装するときはコードに `TODO(Q-NNN)` を残す
- 記録がなく判断できないときは、勝手に決めずに確認する。決め切れないまま進めるなら `Q-<連番>` として記録してから実装する

## よく使うコマンド

開発環境は Docker に集約している。ホストに PHP と Composer を入れる前提にしない。`scripts/dc` がコンテナの中でコマンドを実行する。コンテナはセッションの開始時にフックが確かめ、止まっていれば起動する。

```bash
# 作業の終わりと CI で流す検査を全部（整形・規約・ER 図・静的解析・旧い記法・テスト）
scripts/dc composer check-all

# 整形と自動リファクタリング（Rector と Laravel Pint）
scripts/dc composer format

# 規約の検査だけ
scripts/dc composer check-conventions

# スキーマを変えたあとに ER 図を作り直す
scripts/dc php scripts/generate-er.php

# テストを絞って流す
scripts/dc php artisan test --compact --filter=<テスト名>
```

編集の直後には、フックが Pint で整形して規約の検査を流す。違反はその場で返るので、直してから次に進む。

## 索引

| 知りたいこと | 置き場 |
|---|---|
| 書き方の規約（パスごと） | `.ai/rules/index.md` |
| 用語の意味・業務のルール・未確定事項 | `docs/domain/` |
| システムの振る舞い（受け入れ基準） | `docs/specs/` |
| 技術構成・データモデル・エンドポイント | `docs/architecture/` |
| 規約のうち機械で検査するものとレビューで見るもの | `docs/architecture/convention-checks.md` |
| 設計判断の経緯 | `docs/adr/` |
| プランの作成と評価のエージェント | `.claude/agents/` |

全体の入口は `docs/README.md` にある。ID を見かけたら `grep -rn 'BR-SHARED-006' docs/` で引く。

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.3. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

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

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

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

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

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

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
