#!/usr/bin/env bash
#
# Claude Code の SessionStart フック。作業を始める前に、アプリのコンテナを動かしておく。
#
# 検査（scripts/dc）も laravel-boost の MCP もコンテナの中で動く。
# コンテナが止まったまま作業を始めると、編集の直後の検査が落ち続け、
# MCP のツールも見えないまま進んでしまう。
#
# 標準出力は Claude の文脈に入る。セッションを止めたくないので、何が起きても終了コードは 0 にする。
#
# compose.yml の探し方は scripts/dc に合わせている。
set -uo pipefail

readonly SERVICE="${LARAVEL_SERVICE:-app}"

say() {
    printf '%s\n' "$@"
}

# リポジトリの根から上へ遡って compose.yml を探す
find_compose_file() {
    local dir
    dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

    while [ "$dir" != "/" ]; do
        for name in compose.yml compose.yaml docker-compose.yml docker-compose.yaml; do
            if [ -f "$dir/$name" ]; then
                printf '%s\n' "$dir/$name"
                return 0
            fi
        done
        dir="$(dirname "$dir")"
    done

    return 1
}

mcp_notice() {
    say "laravel-boost の MCP ツール（mcp__laravel-boost__*）が見えないときは、利用者に /mcp で laravel-boost を再接続するよう伝える。"
}

# Laravel Boost の MCP サーバーが起動できるかを、MCP の最初のやり取り（initialize）で確かめる。
# .mcp.json と同じく scripts/dc で起動し、応答が返ればサーバーは起動できる状態である。
# このセッションの Claude Code が実際につながったかは、フックからは見えない。
boost_check() {
    local root request output
    root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
    request='{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"session-start-hook","version":"1.0"}}}'
    output="$(printf '%s\n' "$request" | "$root/scripts/dc" timeout 20 php artisan boost:mcp 2>&1)"

    if printf '%s' "$output" | grep -q '"result"'; then
        say "laravel-boost の MCP サーバーは起動できる（initialize に応答した）。"
        mcp_notice
        return
    fi

    say "laravel-boost の MCP サーバーが起動できなかった。MCP のツールは使えないので、利用者に伝える。出力の末尾を次に示す。" \
        "worktree で作業しているなら、.env と vendor が揃っているかを確かめる（.env がないと Boost が読み込まれない）。" \
        "----"
    printf '%s\n' "$output" | tail -n 10
}

if ! command -v docker >/dev/null 2>&1; then
    say "docker が見つからない。検査と laravel-boost の MCP が使えないので、利用者に Docker Desktop を入れるよう伝える。"
    exit 0
fi

if ! docker info >/dev/null 2>&1; then
    say "Docker が起動していない。検査と laravel-boost の MCP が使えないので、利用者に Docker Desktop を起動するよう伝える。" \
        "起動したら、利用者に /mcp で laravel-boost を再接続するよう伝える。"
    exit 0
fi

if [ -n "${COMPOSE_FILE:-}" ]; then
    if [ ! -f "$COMPOSE_FILE" ]; then
        say "COMPOSE_FILE に指定されたファイルがない: $COMPOSE_FILE"
        exit 0
    fi
    compose_file="$COMPOSE_FILE"
elif ! compose_file="$(find_compose_file)"; then
    say "compose.yml が見つからない。コンテナを確かめられなかったので、利用者に置き場所を確かめるよう伝える。"
    exit 0
fi

is_running() {
    docker compose -f "$compose_file" ps --status running --services 2>/dev/null | grep -qx "$SERVICE"
}

if is_running; then
    say "コンテナ ${SERVICE} は動いている。"
    boost_check
    exit 0
fi

if ! up_output="$(docker compose -f "$compose_file" up -d 2>&1)"; then
    say "コンテナを起動できなかった（docker compose -f ${compose_file} up -d）。出力の末尾を次に示す。利用者に伝える。" \
        "----"
    printf '%s\n' "$up_output" | tail -n 20
    exit 0
fi

if is_running; then
    say "コンテナ ${SERVICE} が止まっていたので、docker compose up -d で起動した。"
    boost_check
else
    say "docker compose up -d は終わったが、コンテナ ${SERVICE} が動いていない。docker compose -f ${compose_file} ps で状態を確かめる。"
    mcp_notice
fi
exit 0
