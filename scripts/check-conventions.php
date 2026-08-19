<?php

/**
 * 規約の検査。
 *
 * 判定できる規約と、その区分は docs/architecture/convention-checks.md にある。
 * ここで見るのは「本文の走査で判定できて、既存の違反が 0 件のもの」だけである。
 * 整形は Pint、型は PHPStan、旧い記法は Rector が受け持つ。
 *
 * 編集直後のフックと継続的インテグレーションの両方からこのファイルを呼ぶ。
 * 判定を 2 か所に書くと、いずれ食い違う。
 *
 * 使い方
 *   php scripts/check-conventions.php
 *   php scripts/check-conventions.php --quiet   違反だけを出す
 *   php scripts/check-conventions.php --merge   取り込む前にだけ見るものも含める
 *
 * 検査には見る時機が2つある。作業中に見てよいものと、取り込む前にだけ見るものである。
 * 起票の残骸のように、作業中は存在していて当然のものを毎回の編集で咎めると、
 * 手順そのものが進まなくなる。既定は作業中に見るものだけとし、継続的
 * インテグレーションだけが --merge を付ける。呼ぶ回数が多いほうを既定にする。
 */
declare(strict_types=1);

const ROOT = __DIR__.'/../';

$quiet = in_array('--quiet', $argv, true);
$merging = in_array('--merge', $argv, true);

/**
 * 指定したディレクトリ以下のファイルを拡張子で集める。
 *
 * @return list<string> ルートからの相対パス
 */
function files(string $dir, string $extension = 'php'): array
{
    $path = ROOT.$dir;
    if (! is_dir($path)) {
        return [];
    }

    $found = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === $extension) {
            $found[] = str_replace(ROOT, '', $file->getPathname());
        }
    }
    sort($found);

    return $found;
}

function contents(string $file): string
{
    return (string) file_get_contents(ROOT.$file);
}

/**
 * 行を1件ずつ走査する。コールバックが文字列を返した行だけを違反として集める。
 *
 * @param  callable(string, int): (string|null)  $judge
 * @return list<string>
 */
function scanLines(string $file, callable $judge): array
{
    $violations = [];
    foreach (explode("\n", contents($file)) as $index => $line) {
        $detail = $judge($line, $index + 1);
        if ($detail !== null) {
            $violations[] = sprintf('%s:%d  %s', $file, $index + 1, $detail);
        }
    }

    return $violations;
}

/**
 * 地の文だけを走査する。囲みコードの中は対象から外す。
 *
 * 文体の規約は読み手に見せる文章に対するものであり、コード例の中の記号や
 * 識別子まで拾うと誤検出になる。
 *
 * @param  callable(string, int): (string|null)  $judge
 * @return list<string>
 */
function scanProseLines(string $file, callable $judge): array
{
    $inCode = false;

    return scanLines($file, function (string $line, int $number) use ($judge, &$inCode): ?string {
        if (str_starts_with(trim($line), '```')) {
            $inCode = ! $inCode;

            return null;
        }

        return $inCode ? null : $judge($line, $number);
    });
}

/**
 * クラスの公開メソッド名を字句解析で取り出す。
 *
 * 正規表現だと文字列やコメントの中の "public function" を拾ってしまうため、
 * 構造にかかわる判定はここを通す。
 *
 * @return list<array{name: string, line: int}>
 */
function publicMethods(string $file): array
{
    $tokens = token_get_all(contents($file));
    $methods = [];
    $isPublic = true;

    foreach ($tokens as $index => $token) {
        if (! is_array($token)) {
            continue;
        }

        if ($token[0] === T_PRIVATE || $token[0] === T_PROTECTED) {
            $isPublic = false;
        }

        if ($token[0] === T_PUBLIC) {
            $isPublic = true;
        }

        if ($token[0] !== T_FUNCTION) {
            continue;
        }

        // function の直後の名前を探す。無名関数は括弧に当たるので対象から外れる
        for ($next = $index + 1; $next < count($tokens); $next++) {
            $candidate = $tokens[$next];
            if (! is_array($candidate)) {
                if ($candidate === '(') {
                    break;
                }

                continue;
            }
            if ($candidate[0] === T_STRING) {
                if ($isPublic) {
                    $methods[] = ['name' => $candidate[1], 'line' => $token[2]];
                }
                break;
            }
        }

        $isPublic = true;
    }

    return $methods;
}

/**
 * ドキュメントから ID を拾う。数字の後ろに英数字が続くものは雛形の穴埋めなので除く。
 *
 * @return list<string>
 */
function idsIn(string $text, string $pattern): array
{
    // 数字の直後に英数字が続くものは雛形の穴埋めなので、先読みで弾く
    preg_match_all('/'.trim($pattern, '/').'(?![0-9A-Za-z])/', $text, $matches);

    return array_values(array_unique($matches[0]));
}

/** 規約そのものと雛形は、書き方の例として禁止表現を含むため対象から外す */
const DOC_EXCLUDED = ['docs/documentation-rules.md', 'docs/changes/template.md', 'docs/architecture/convention-checks.md'];

/** @return list<string> */
function docs(string $dir = 'docs'): array
{
    return array_values(array_filter(
        files($dir, 'md'),
        fn (string $file): bool => ! in_array($file, DOC_EXCLUDED, true)
    ));
}

/** @var list<array{id: string, description: string, violations: list<string>}> $results */
$results = [];

/**
 * 検査を1件登録する。
 *
 * $stage が 'merge' の検査は取り込む前にだけ見る。作業中は成立していなくて当然の
 * ものがあるため、--merge を付けたときだけ走らせる。
 *
 * @param  callable(): list<string>  $check
 * @param  'always'|'merge'  $stage
 */
function check(string $id, string $description, callable $check, string $stage = 'always'): void
{
    global $results, $merging;
    if ($stage === 'merge' && ! $merging) {
        return;
    }

    $results[] = ['id' => $id, 'description' => $description, 'violations' => $check()];
}

// ---------------------------------------------------------------- コントローラー

check('CHK-CTRL-02', 'コントローラーはサービスをコンストラクタで受け取らない', function (): array {
    $violations = [];
    foreach (files('app/Http/Controllers') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            if (! str_contains($line, 'public function __construct(')) {
                return null;
            }

            return str_contains($line, '__construct()') ? null : 'メソッドインジェクションで受け取る';
        }));
    }

    return $violations;
});

// ------------------------------------------------------------------ バリデーション

check('CHK-VALID-01', 'FormRequest は1エンドポイント1ファイルとする', function (): array {
    $controllers = [];
    foreach (files('app/Http/Controllers') as $file) {
        $controllers[$file] = contents($file);
    }

    $violations = [];
    foreach (files('app/Http/Requests') as $file) {
        preg_match('/^namespace\s+([^;]+);/m', contents($file), $namespace);
        if ($namespace === []) {
            continue;
        }
        $fqcn = $namespace[1].'\\'.basename($file, '.php');

        $users = [];
        foreach ($controllers as $controller => $body) {
            if (str_contains($body, $fqcn)) {
                $users[] = $controller;
            }
        }

        if (count($users) > 1) {
            $violations[] = sprintf('%s  %d 箇所から参照されている (%s)', $file, count($users), implode(', ', $users));
        }
    }

    return $violations;
});

check('CHK-VALID-02', 'Enum を定義している値の制御は Rule::enum を使う', function (): array {
    // Enum ごとに、格納する値の一覧を集める
    $enumValues = [];
    foreach (files('app/Enums') as $file) {
        preg_match_all("/case\s+[A-Z_]+\s*=\s*'([^']+)'/", contents($file), $cases);
        if ($cases[1] !== []) {
            $enumValues[$file] = $cases[1];
        }
    }

    $violations = [];
    foreach (files('app/Http/Requests') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line) use ($enumValues): ?string {
            if (preg_match("/'in:([^']+)'/", $line, $matched) !== 1) {
                return null;
            }
            $listed = explode(',', $matched[1]);

            foreach ($enumValues as $enum => $values) {
                if (array_diff($listed, $values) === []) {
                    return sprintf('%s があるので Rule::enum を使う', $enum);
                }
            }

            return null;
        }));
    }

    return $violations;
});

// ------------------------------------------------------------------------ サービス

check('CHK-SVC-01', 'サービスの公開メソッドは __invoke だけ', function (): array {
    $violations = [];
    foreach (files('app/Services') as $file) {
        foreach (publicMethods($file) as $method) {
            if ($method['name'] === '__invoke' || $method['name'] === '__construct') {
                continue;
            }
            $violations[] = sprintf('%s:%d  %s()', $file, $method['line'], $method['name']);
        }
    }

    return $violations;
});

check('CHK-SVC-03', 'サービスはトランザクションを張らない', function (): array {
    $violations = [];
    foreach (files('app/Services') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            if (str_contains($line, 'DB::transaction') || str_contains($line, 'DB::beginTransaction')) {
                return 'トランザクションはコントローラーの責務である';
            }

            return null;
        }));
    }

    return $violations;
});

// -------------------------------------------------------------------------- モデル

check('CHK-MODEL-02', 'セキュリティ性が高いカラムは $hidden に入れる', function (): array {
    $sensitive = ['password', 'token', 'code'];

    $violations = [];
    foreach (files('app/Model') as $file) {
        $body = contents($file);
        if (preg_match('/protected \$fillable = \[(.*?)\];/s', $body, $fillable) !== 1) {
            continue;
        }
        preg_match('/protected \$hidden = \[(.*?)\];/s', $body, $hidden);
        $hiddenColumns = $hidden[1] ?? '';

        foreach ($sensitive as $column) {
            if (str_contains($fillable[1], "'".$column."'") && ! str_contains($hiddenColumns, "'".$column."'")) {
                $violations[] = sprintf('%s  %s を $hidden に入れる', $file, $column);
            }
        }
    }

    return $violations;
});

// ---------------------------------------------------------- マイグレーション

check('CHK-MIGRATE-03', '外部キーは foreignIdFor と constrained を使う', function (): array {
    $violations = [];
    foreach (files('database/migrations') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            return str_contains($line, '->foreign(') ? 'foreignIdFor と constrained を使う' : null;
        }));
    }

    return $violations;
});

check('CHK-MIGRATE-05', 'timestamps() ヘルパと timestamp 型を使わない', function (): array {
    $violations = [];
    foreach (files('database/migrations') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            if (str_contains($line, '->timestamps()')) {
                return 'created_at と updated_at を dateTime で明示する';
            }
            if (preg_match('/->timestamp\(/', $line) === 1) {
                return '2038年問題を避けるため dateTime を使う';
            }

            return null;
        }));
    }

    return $violations;
});

// ---------------------------------------------------------------------------- Enum

check('CHK-ENUM-01', 'Enum は app/Enums/{コンテキスト}/~Enum.php に置く', function (): array {
    $violations = [];
    foreach (files('app/Enums') as $file) {
        if (! str_ends_with($file, 'Enum.php')) {
            $violations[] = sprintf('%s  ファイル名を ~Enum.php にする', $file);
        }
        if (substr_count($file, '/') !== 3) {
            $violations[] = sprintf('%s  コンテキストのディレクトリに置く', $file);
        }
    }

    return $violations;
});

check('CHK-ENUM-02', '状態と種別を表す Enum は文字列を格納する', function (): array {
    $violations = [];
    foreach (files('app/Enums') as $file) {
        $name = basename($file, '.php');
        if ($name !== 'StatusEnum' && $name !== 'TypeEnum') {
            continue;
        }
        if (preg_match('/enum\s+'.$name.'\s*:\s*string/', contents($file)) !== 1) {
            $violations[] = sprintf('%s  string backed にする', $file);
        }
    }

    return $violations;
});

check('CHK-ENUM-03', '状態と種別をモデルのクラス定数で持たない', function (): array {
    $violations = [];
    foreach (files('app/Model') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            if (preg_match('/^\s+(public |protected |private )?const (STATUS|TYPE)_[A-Z_]+\s*=/', $line) !== 1) {
                return null;
            }

            return 'app/Enums/{コンテキスト}/ に Enum を定義する';
        }));
    }

    return $violations;
});

// ------------------------------------------------------------------------ ルーティング

check('CHK-ROUTE-03', 'ルート定義はロール別のファイルに置く', function (): array {
    $allowed = ['routes/api.php', 'routes/web.php', 'routes/channels.php', 'routes/console.php'];

    $violations = [];
    foreach (files('routes') as $file) {
        if (in_array($file, $allowed, true) || str_starts_with($file, 'routes/api/')) {
            continue;
        }
        $violations[] = sprintf('%s  routes/api/{ロール}.php に置く', $file);
    }

    return $violations;
});

// -------------------------------------------------------------------- 型と PHP 全般

check('CHK-TYPE-02', '日付は CarbonImmutable を使う', function (): array {
    $violations = [];
    foreach (files('app') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            return str_contains($line, 'use Carbon\Carbon;') ? 'CarbonImmutable を使う' : null;
        }));
    }

    return $violations;
});

check('CHK-TYPE-03', 'config 以外で env() を呼ばない', function (): array {
    $violations = [];
    foreach ([...files('app'), ...files('routes'), ...files('database')] as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            if (preg_match('/(^|[^a-zA-Z_>$])env\s*\(/', $line) !== 1) {
                return null;
            }

            return '設定は config 経由で読む';
        }));
    }

    return $violations;
});

check('CHK-TYPE-04', 'empty() を使わない', function (): array {
    $violations = [];
    foreach (files('app') as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            if (preg_match('/(^|[^a-zA-Z_])empty\s*\(/', $line) !== 1) {
                return null;
            }

            return '=== null・=== \'\'・->isEmpty() で明示的に判定する';
        }));
    }

    return $violations;
});

// -------------------------------------------------------------------------- テスト

check('CHK-TEST-02', 'テストに Arrange・Act・Assert を書く', function (): array {
    $violations = [];
    foreach (files('tests') as $file) {
        if (! str_ends_with($file, 'Test.php')) {
            continue;
        }
        $body = contents($file);
        foreach (['Arrange', 'Act', 'Assert'] as $phase) {
            if (! str_contains($body, '// '.$phase)) {
                $violations[] = sprintf('%s  // %s の記載がない', $file, $phase);
            }
        }
    }

    return $violations;
});

check('CHK-TEST-06', 'テストが参照する受け入れ基準が実在する', function (): array {
    $specs = '';
    foreach (docs('docs/specs') as $file) {
        $specs .= contents($file);
    }
    $defined = idsIn($specs, 'AC-[A-Z]+-\\d+');

    $violations = [];
    foreach (files('tests') as $file) {
        if (! str_ends_with($file, 'Test.php')) {
            continue;
        }
        $violations = array_merge($violations, scanLines($file, function (string $line) use ($defined): ?string {
            if (preg_match('/(AC-[A-Z]+-\\d+)(?![0-9A-Za-z])/', $line, $matched) !== 1) {
                return null;
            }
            if (in_array($matched[1], $defined, true)) {
                return null;
            }

            return sprintf('%s が docs/specs/ にない', $matched[1]);
        }));
    }

    return $violations;
});

check('CHK-TEST-03', 'テスト関数名を日本語で書く', function (): array {
    $violations = [];
    foreach (files('tests') as $file) {
        if (! str_ends_with($file, 'Test.php')) {
            continue;
        }
        $violations = array_merge($violations, scanLines($file, function (string $line): ?string {
            if (preg_match('/function (test_[0-9A-Za-z_]+)\s*\(/', $line, $matched) !== 1) {
                return null;
            }

            return sprintf('%s は業務と利用者の視点の日本語にする', $matched[1]);
        }));
    }

    return $violations;
});

// ---------------------------------------------------------------- ドキュメント

check('CHK-DOC-01', '業務知識にシステムを主語にした文を書かない', function (): array {
    $violations = [];
    foreach (docs('docs/domain') as $file) {
        $violations = array_merge($violations, scanProseLines($file, function (string $line): ?string {
            if (preg_match('/システムは.*(しなければ|してはならない)/u', $line) !== 1) {
                return null;
            }

            return 'システムの振る舞いは docs/specs/ に書く';
        }));
    }

    return $violations;
});

check('CHK-DOC-02', '業務知識から仕様と構成を参照しない', function (): array {
    $violations = [];
    foreach (docs('docs/domain') as $file) {
        // 未確定事項は影響範囲を書くために仕様を指してよい。
        // 索引は業務知識そのものではなく、置き場の案内として仕様に言及する。
        if (str_contains($file, 'open-questions') || str_ends_with($file, 'README.md')) {
            continue;
        }
        $body = contents($file);
        // 冒頭の説明文も置き場の案内なので、最初の見出し以降を見る
        $sections = preg_split('/^## /m', $body);
        $violations = array_merge($violations, array_map(
            fn (string $hit): string => sprintf('%s  %s', $file, trim($hit)),
            array_filter(
                array_slice($sections === false ? [] : $sections, 1),
                fn (string $section): bool => str_contains($section, 'docs/specs/') || str_contains($section, 'docs/architecture/')
            )
        ));
    }

    return $violations;
});

check('CHK-DOC-04', '存在しない ID を参照しない', function (): array {
    $all = '';
    foreach (docs() as $file) {
        $all .= contents($file);
    }

    $defined = [
        'BR' => idsIn(contents('docs/domain/shared/business-rules.md'), '/BR-SHARED-\d+/'),
        'Q' => idsIn(contents('docs/domain/open-questions.md'), '/Q-\d+/'),
    ];
    $specs = '';
    foreach (docs('docs/specs') as $file) {
        $specs .= contents($file);
    }
    $defined['AC'] = idsIn($specs, '/AC-[A-Z]+-\d+/');

    $defined['ADR'] = array_map(
        fn (string $file): string => 'ADR-'.substr(basename($file), 0, 4),
        array_values(array_filter(files('docs/adr', 'md'), fn (string $f): bool => preg_match('/\/\d{4}-/', $f) === 1))
    );

    $violations = [];
    foreach (['BR' => '/BR-SHARED-\d+/', 'AC' => '/AC-[A-Z]+-\d+/', 'Q' => '/Q-\d+/', 'ADR' => '/ADR-\d+/'] as $kind => $pattern) {
        foreach (array_diff(idsIn($all, $pattern), $defined[$kind]) as $missing) {
            $violations[] = sprintf('%s  定義が見つからない', $missing);
        }
    }

    return $violations;
});

check('CHK-DOC-05', 'TODO(Q-NNN) の未確定事項が実在する', function (): array {
    $recorded = idsIn(contents('docs/domain/open-questions.md'), '/Q-\d+/');

    $violations = [];
    foreach ([...files('app'), ...files('routes'), ...files('database')] as $file) {
        $violations = array_merge($violations, scanLines($file, function (string $line) use ($recorded): ?string {
            if (preg_match('/TODO\((Q-\d+)\)/', $line, $matched) !== 1) {
                return null;
            }
            if (in_array($matched[1], $recorded, true)) {
                return null;
            }

            return sprintf('%s が docs/domain/open-questions.md にない', $matched[1]);
        }));
    }

    return $violations;
});

check('CHK-DOC-06', '実装が終わった起票を残さない', function (): array {
    return array_map(
        fn (string $file): string => sprintf('%s  実装後は起票ごと削除する', $file),
        array_values(array_filter(
            files('docs/changes', 'md'),
            fn (string $file): bool => $file !== 'docs/changes/template.md'
        ))
    );
}, 'merge');

check('CHK-DOC-07', '太字記法と区切り線を使わない', function (): array {
    $violations = [];
    foreach (docs() as $file) {
        $violations = array_merge($violations, scanProseLines($file, function (string $line): ?string {
            if (preg_match('/\*\*/', $line) === 1) {
                return '強調は見出しと表で表す';
            }
            if (trim($line) === '---') {
                return '見出しの階層で区切る';
            }

            return null;
        }));
    }

    return $violations;
});

check('CHK-DOC-08', '版を分けたファイルを作らない', function (): array {
    return array_map(
        fn (string $file): string => sprintf('%s  上書きし、履歴は Git に任せる', $file),
        array_values(array_filter(
            files('docs', 'md'),
            fn (string $file): bool => preg_match('/(_v\d+|_old|_\d{8})\.md$/', $file) === 1
        ))
    );
});

check('CHK-DOC-09', '用語を揺らさない', function (): array {
    $forbidden = [
        'コース' => '講座',
        'セクション' => 'チャプター',
        '単元' => 'レッスン',
        '学習進捗' => '受講状況',
        '出席状況' => '受講状況',
        '生徒' => '受講生',
        '管理者' => 'マネージャー',
    ];

    $violations = [];
    foreach ([...docs('docs/domain'), ...docs('docs/specs')] as $file) {
        if (str_contains($file, 'glossary')) {
            continue;
        }
        $violations = array_merge($violations, scanProseLines($file, function (string $line) use ($forbidden): ?string {
            foreach ($forbidden as $wrong => $right) {
                if (str_contains($line, $wrong)) {
                    return sprintf('「%s」ではなく「%s」を使う', $wrong, $right);
                }
            }

            return null;
        }));
    }

    return $violations;
});

// -------------------------------------------------------------------------- 出力

$failed = 0;
foreach ($results as $result) {
    $count = count($result['violations']);
    if ($count === 0) {
        if (! $quiet) {
            printf("  ok   %-14s %s\n", $result['id'], $result['description']);
        }

        continue;
    }

    $failed++;
    printf("  NG   %-14s %s\n", $result['id'], $result['description']);
    foreach ($result['violations'] as $violation) {
        printf("         %s\n", $violation);
    }
}

if ($failed === 0) {
    if (! $quiet) {
        printf("\n%d 項目すべて規約どおりである。\n", count($results));
    }
    exit(0);
}

printf("\n%d 項目に違反がある。判定の根拠は docs/architecture/convention-checks.md にある。\n", $failed);
exit(1);
