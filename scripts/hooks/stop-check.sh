#!/usr/bin/env bash
#
# Claude Code の Stop フック。作業の終わりに CI と同じ検査を流す。
#
# 編集の直後には Pint と規約の検査だけを流している（PostToolUse のフック）。
# テストは、先に書いてから実装するまでの間は落ちているのが正しい状態なので、編集のたびには流さず、
# 作業を終えるときにまとめて流す。全部で30秒ほどで終わる。
# CI で落ちてから直すより、手元で落ちた時点で AI に直させるほうが往復が少ない。
#
# 終了コード
#   0  検査が通った、または検査を流す必要がない
#   2  検査が落ちた。標準エラーの内容が Claude に渡り、作業が続く
#
# 入力の JSON の stop_hook_active が真のときは、このフックで作業を続けさせた直後である。
# そこでまた止めると終わらなくなるので、検査を流さずに終える。
set -uo pipefail

readonly TAIL_LINES="${STOP_CHECK_TAIL_LINES:-80}"

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$root" || exit 0

input="$(cat)"

if [ "$(printf '%s' "$input" | jq -r '.stop_hook_active // false' 2>/dev/null)" = "true" ]; then
    exit 0
fi

# PHP・docs・テストに変更がなければ流さない。調べものだけの作業で30秒待たせないためである。
changed="$(git status --porcelain --untracked-files=all -- '*.php' 'docs/' 'tests/' 2>/dev/null)"
[ -n "$changed" ] || exit 0

# 前回通ったときから作業ツリーが変わっていなければ流さない。評価のエージェントを待つあいだなど、
# 何も直さずにターンを終えるたびに30秒待たせないためである。
# 指紋は、HEAD と、コミットしていない変更と、追跡していないファイルの中身から作る。
fingerprint() {
    {
        git rev-parse HEAD
        git diff HEAD
        git ls-files --others --exclude-standard | while IFS= read -r file; do
            printf '%s %s\n' "$file" "$(git hash-object -- "$file")"
        done
    } 2>/dev/null | git hash-object --stdin
}

state="$(git rev-parse --git-path stop-check-passed)"
current="$(fingerprint)"
if [ -f "$state" ] && [ "$(cat "$state")" = "$current" ]; then
    exit 0
fi

output="$(scripts/dc composer check-all 2>&1)"
status=$?

if [ $status -ne 0 ]; then
    {
        printf '%s\n' "composer check-all が落ちた（終了コード ${status}）。出力の末尾 ${TAIL_LINES} 行を次に示す。"
        printf '%s\n' "原因を直してから、scripts/dc composer check-all で通ることを確かめる。"
        printf '%s\n' "----"
        printf '%s\n' "$output" | tail -n "$TAIL_LINES"
    } >&2
    rm -f "$state"
    exit 2
fi

printf '%s\n' "$current" > "$state"
exit 0
