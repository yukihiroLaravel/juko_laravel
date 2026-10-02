# 意思決定記録

その時点で何を決めたかを記録する。追記のみとし、過去の記録の内容は書き換えない。方針が変わったときは新しい記録を追加し、古い記録のステータスだけを更新する。

## 一覧

| ID | 決定 | ステータス |
|---|---|---|
| [ADR-0001](0001-role-based-route-files.md) | ルート定義をロール別のファイルに分割する | 承認済み (2026-08-17 記録) |
| [ADR-0002](0002-lesson-completion-by-completed-at.md) | レッスンの完了を完了日時で判定し、その値を単調に保つ | 承認済み (2026-08-17 記録) |
| [ADR-0003](0003-copy-attendance-deadline-to-attendance.md) | 受講期限を受講のレコードに複製して保持する | 承認済み (2026-08-17 記録) |
| [ADR-0004](0004-restrict-publication-status-transitions.md) | 公開状態の遷移を制限し、下書きへ戻さない | 承認済み (2026-08-17 記録) |
| [ADR-0005](0005-pre-generate-lesson-attendances.md) | 受講状況を未着手の分まで先に作る | 承認済み (2026-08-17 記録) |
| [ADR-0006](0006-split-docs-into-domain-and-specs.md) | ドキュメントを業務知識とシステム仕様に分けて構成する | 承認済み (2026-08-17) |

ADR-0001 から ADR-0005 は、すでに稼働している実装から設計判断を復元して記録したものである。決定そのものは記録日より前に行われている。今後の決定は判断した時点で記録する。

## 旧構成からの読み替え

2026-08-17 のドキュメント再編（ADR-0006）で、それまで `docs/README.md` にまとめていた内容を分割した。旧構成の章と新しい置き場の対応は次のとおりである。

| 旧 `docs/README.md` の章 | 新しい置き場 |
|---|---|
| プロジェクト概要・技術スタック | `docs/architecture/overview.md` |
| ユーザーロール | `docs/domain/shared/glossary.md`（講師・マネージャー・受講生） |
| 用語集 | `docs/domain/shared/glossary.md` |
| ビジネスロジック仕様・データモデルの階層構造 | `docs/domain/shared/business-rules.md`（BR-SHARED-002） |
| 受講と受講状況 | `docs/specs/features/lesson-progress.md`・`docs/domain/shared/business-rules.md`（BR-SHARED-011・BR-SHARED-012） |
| 主な関係性 | `docs/domain/shared/business-rules.md`（BR-SHARED-003・BR-SHARED-014・BR-SHARED-016・BR-SHARED-017） |
| 受講期限の取り扱い | `docs/specs/features/enrollment.md`・`docs/domain/shared/business-rules.md`（BR-SHARED-005・BR-SHARED-006） |

## 書くとき

- 1つの決定を1つのファイルとし、`NNNN-<内容を表す英小文字とハイフン>.md` で作成する
- 番号は連番で、欠番を埋め直さない
- 雛形は `.claude/skills/documentation-conventions/templates/adr.md` を使う
- 採用しなかった案とその理由を必ず書く。ここが記録の本体である
- 決定によって生じた制約や将来の課題を影響の節に書く
