---
paths:
  - 'app/Http/Requests/**'
---

# 入力の検証

## 1エンドポイント1ファイル

FormRequest で実装し、1エンドポイント1ファイルとする。複数のエンドポイントで共有しない（CHK-VALID-01）。

## Enum の値は Rule::enum で制御する

Enum を定義している値は `Rule::enum(GenderEnum::class)` で制御し、`in:1,2,9` のようなリテラルの列挙をしない。Enum を持たない単純な選択肢（並び順など）は `in:` で書いてよい（CHK-VALID-02）。
