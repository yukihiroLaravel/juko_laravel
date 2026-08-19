# 受講管理アプリ ドキュメント

講師が講座を用意して公開し、受講生が講座を受講して学習を進め、講師が受講生の学習状況を把握してフォローするためのシステムである。このディレクトリはバックエンド API の業務知識・システム仕様・システム構成・意思決定の記録を扱う。画面の仕様は扱わない。

## 索引

| 置き場 | 内容 |
|---|---|
| [documentation-rules.md](documentation-rules.md) | このプロジェクトでのドキュメント運用ルール |
| [domain/](domain/README.md) | 業務知識。用語集・業務ルール・未確定事項 |
| [specs/](specs/README.md) | システム仕様。共通仕様と機能ごとの受け入れ基準 |
| [architecture/overview.md](architecture/overview.md) | 技術スタックと実装の構成 |
| [architecture/data-model.md](architecture/data-model.md) | テーブルの役割とカラムの意味 |
| [architecture/api.md](architecture/api.md) | エンドポイントの一覧と仕様の対応 |
| [architecture/coding-standards.md](architecture/coding-standards.md) | このプロジェクト固有のコーディング規約 |
| [adr/](adr/README.md) | 意思決定の記録 |
| [changes/template.md](changes/template.md) | 変更を起票するときの雛形 |

## 読む順序

初めて読む場合は次の順に読むと、業務からシステムへ降りていける。

1. [domain/shared/glossary.md](domain/shared/glossary.md) — 講座・チャプター・レッスン・受講状況などの言葉の意味
2. [domain/shared/business-rules.md](domain/shared/business-rules.md) — 業務としてどうなっているか
3. [specs/conventions.md](specs/conventions.md) — 全エンドポイントに共通する振る舞い
4. [specs/features/](specs/README.md) — 関わる機能の受け入れ基準
5. [architecture/overview.md](architecture/overview.md) — それをどう実装しているか

実装に着手する前に [domain/open-questions.md](domain/open-questions.md) を読む。未確定のまま動いている箇所があり、勝手に埋めると確定後に矛盾する。

## ID の引き方

| 見かけた ID | 引く先 |
|---|---|
| `BR-SHARED-006` | [domain/shared/business-rules.md](domain/shared/business-rules.md) |
| `AC-ENROLL-010` | [specs/features/enrollment.md](specs/features/enrollment.md)（接頭辞と機能の対応は [specs/README.md](specs/README.md)） |
| `Q-007` | [domain/open-questions.md](domain/open-questions.md) |
| `ADR-0003` | [adr/README.md](adr/README.md) |

```bash
grep -rn 'BR-SHARED-006' docs/
```

## この文書群の前提

稼働中の実装を読み直して整理したものである（整理日 2026-08-17）。業務側の確認を経ていないため、実装が業務判断とずれている可能性がある。ずれの疑いがあるものは未確定事項として切り出している。

実装を変えたときは、同じ変更のなかで該当する文書を上書き更新する。手順は [documentation-rules.md](documentation-rules.md) にある。
