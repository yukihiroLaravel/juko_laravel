# AGENTS.md

このファイルは、このリポジトリのコードを操作するエージェントへのガイダンスである。Claude Code は `CLAUDE.md` からこのファイルを読み込む。エージェントを問わず同じ内容が効くよう、指示はここに一本化する。

受講管理アプリのバックエンドAPIである。講師が講座を用意して公開し、受講生が講座を受講して学習を進め、講師が受講生の学習状況を把握してフォローする。PHP 8.3 の Laravel 12 で実装し、受講生・講師・マネージャーの3区分にAPIを提供する。

このファイルは入口である。仕様と規約の実体は `docs/` にあり、ここには索引と、取り違えると壊れるものだけを置く。詳細をこのファイルに書き足さない。

## ドキュメントの索引

| 知りたいこと | 置き場 |
|---|---|
| 用語の意味・業務のルール | `docs/domain/` |
| システムの振る舞い（受け入れ基準） | `docs/specs/` |
| 技術構成・データモデル・エンドポイント | `docs/architecture/` |
| コーディング規約 | `docs/architecture/coding-standards.md` |
| 規約の検査手段 | `docs/architecture/convention-checks.md` |
| 設計判断の経緯 | `docs/adr/` |
| 未確定事項 | `docs/domain/open-questions.md` |
| これから作る変更の起票 | `docs/changes/template.md` |
| ドキュメントの書き方 | `docs/documentation-rules.md` |
| 変更を実装するときの手順 | `.claude/skills/change-flow/SKILL.md` |

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

開発環境は Docker に集約している。ホストに PHP と Composer を入れる前提にしない。
`scripts/dc` がコンテナの中でコマンドを実行する。

```bash
# コードフォーマット（Rector + Laravel Pint を順次実行）
scripts/dc composer format

# 規約の検査（何がどこで違反しているかを出す）
scripts/dc composer check-conventions

# 静的解析（PHPStan レベル6・既存の型未宣言は phpstan-baseline.neon に記録済み）
scripts/dc composer analyze

# テスト
scripts/dc php artisan test --compact
```

コンテナが起動していないと動かない。リポジトリの根で `docker compose up -d` を実行する。

規約の検査はファイルを書いた直後にも自動で走る。違反はその場で返るので、
指摘されたら直してから次に進む。判定の一覧は `docs/architecture/convention-checks.md` にある。

## 進め方

コードに手を入れるときは `change-flow` スキルに従う。`/change-flow` でも呼べる。

実装は TDD で進める。勝手に先へ進まず、次の2か所で必ず依頼者の承認を取る。

1. 起票を書く前。変更する振る舞い・変更する範囲・対象外の3点を要点だけ提示する
2. 失敗するテストを書いたあと、実装に入る前。何を確かめるかを業務の言葉で提示する

2番目が仕様の確定点である。ここではテストのコードを見せない。依頼者には開発の
経験が浅い者が多く、コードを見せると判断できないまま承認する形になるためである。

段の全体は `docs/architecture/coding-standards.md` の「実装の進め方」、
関門で提示する形は `.claude/skills/change-flow/references/gates.md` にある。

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
