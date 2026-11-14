---
paths:
  - 'database/migrations/**'
---

# マイグレーション

## 外部キー

外部キーは `foreignIdFor` と `constrained` を使い、参照先への制約を必ず張る（CHK-MIGRATE-03）。

```php
$table->foreignIdFor(Instructor::class, 'manager_id')->nullable()->constrained()->comment('講師ID');
```

## 説明と型

- テーブルとカラムには必ず `->comment()` を付ける
- `timestamp` 型は 2038 年問題があるので使わず、`datetime` を使う。`timestamps()` ヘルパも使わない（CHK-MIGRATE-05）

## スキーマを変えたら ER 図を作り直す

マイグレーションでテーブルやカラム、外部キー、一意制約を変えたら、同じ変更のなかで ER 図（`docs/architecture/er.drawio`）を `scripts/dc php scripts/generate-er.php` で作り直す。ER 図は手で直さない。作り直していないと、`composer check-all` の ER 図の検査（CHK-DOC-10）で落ちる。

テーブルを足したときは、`scripts/generate-er.php` の `NAMES` に日本語の名前を、`LAYOUT` に置き場所を足してから作り直す。カラムの説明は `->comment()` から拾うので、マイグレーションに説明を書く。あわせて `docs/architecture/data-model.md` のカラムの意味と関連の図も直す。

## DDL だけにし、既存の create マイグレーションを直す

リリース前で、本番に既存データがない。この間は次のように運用する。リリースの時点で見直す。

- マイグレーションには、既存データを削除・更新する処理を入れない
- UNIQUE 制約やカラムを足すときは、新しいマイグレーションファイルを作らず、既存の create マイグレーションを編集する
- 既存のマイグレーションを書き換えたときは、ローカルのデータベースを `scripts/dc php artisan migrate:fresh --seed` で作り直すよう利用者に伝える
