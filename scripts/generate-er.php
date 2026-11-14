<?php

/**
 * マイグレーションから ER 図（docs/architecture/er.drawio と er.svg）を作り直す。
 *
 * スキーマの正はマイグレーションである。ER 図を手で直すと、いずれマイグレーションと食い違う。
 * そのため ER 図は手で直さず、このスクリプトで作り直す。--check を付けると、作り直した内容と
 * 今の ER 図を比べ、違えば終了コード 1 を返す。composer check-all から呼び、スキーマを変えたのに
 * ER 図を作り直していなければ、作業の終わりと継続的インテグレーションで止まる。
 *
 * draw.io の形式は編集して眺めるためのもの、SVG は GitHub や PR でそのまま見るためのものである。
 * 2つは同じ配置から書き出す。
 *
 * 構造（カラムの並び・必須かどうか・主キー・外部キー・一意制約）は、使い捨ての SQLite に
 * マイグレーションを流して取り出す。型の名前と説明は、マイグレーションの記述から拾う。
 *
 * 使い方
 *   php scripts/generate-er.php          ER 図を書き出す
 *   php scripts/generate-er.php --check  ER 図がマイグレーションと食い違っていないかを確かめる
 */
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

const ROOT = __DIR__.'/../';
const OUTPUTS = ['drawio' => 'docs/architecture/er.drawio', 'svg' => 'docs/architecture/er.svg'];

/** フレームワークが用意するテーブルは描かない */
const EXCLUDED = ['migrations', 'failed_jobs', 'password_resets', 'personal_access_tokens', 'users'];

/** テーブルの日本語の名前。docs/architecture/data-model.md の見出しにそろえる */
const NAMES = [
    'instructors' => '講師',
    'manage_instructors' => 'マネージャーと配下の講師',
    'temporary_instructors' => '講師の仮登録',
    'courses' => '講座',
    'course_deadlines' => '講座の受講期限の設定',
    'tags' => 'タグ',
    'course_tag' => '講座とタグの関連',
    'chapters' => 'チャプター',
    'lessons' => 'レッスン',
    'notifications' => 'お知らせ',
    'viewed_once_notifications' => 'お知らせの確認済みの記録',
    'attendances' => '受講',
    'lesson_attendances' => '受講状況',
    'students' => '受講生',
    'student_login_histories' => 'ログイン履歴',
    'temporary_students' => '受講生の仮登録',
];

/** 置き場所。列ごとに上から並べる。ここにないテーブルは右端に列を足して並べる */
const LAYOUT = [
    ['instructors', 'manage_instructors', 'temporary_instructors'],
    ['courses', 'course_deadlines', 'tags', 'course_tag'],
    ['chapters', 'lessons', 'notifications', 'viewed_once_notifications'],
    ['attendances', 'lesson_attendances', 'student_login_histories'],
    ['students', 'temporary_students'],
];

const MARGIN = 40;
const MIN_TABLE_WIDTH = 360;
const COLUMN_GAP = 140;
const ROW_GAP = 60;
const TITLE_HEIGHT = 30;
const ROW_HEIGHT = 24;
const FONT_SIZE = 12;
const PADDING = 8;

$checking = in_array('--check', $argv, true);

$schema = readSchema();
$tables = placeTables($schema, readMigrations());
$relations = relationsOf($schema, $tables);
$contents = [
    'drawio' => renderDrawio($tables, $relations),
    'svg' => renderSvg($tables, $relations),
];

$stale = [];
foreach (OUTPUTS as $kind => $path) {
    if ($checking) {
        $current = is_file(ROOT.$path) ? (string) file_get_contents(ROOT.$path) : '';
        if ($current !== $contents[$kind]) {
            $stale[] = $path;
        }

        continue;
    }

    file_put_contents(ROOT.$path, $contents[$kind]);
    echo $path." を書き出した。\n";
}

if ($stale !== []) {
    fwrite(STDERR, implode('・', $stale)." がマイグレーションと食い違っている。scripts/dc php scripts/generate-er.php で作り直す。\n");
    exit(1);
}

if ($checking) {
    echo implode('・', OUTPUTS)." はマイグレーションと一致している。\n";
}

/**
 * 使い捨ての SQLite にマイグレーションを流し、テーブルの構造を取り出す。
 *
 * @return array<string, array{columns: list<array{name: string, nullable: bool}>, primary: list<string>, unique: list<string>, foreign: array<string, array{table: string, column: string}>}>
 */
function readSchema(): array
{
    $database = sys_get_temp_dir().'/generate-er-'.getmypid().'.sqlite';
    touch($database);

    foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database] as $key => $value) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    require ROOT.'vendor/autoload.php';
    $app = require ROOT.'bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    $kernel->call('migrate', ['--force' => true]);

    $schema = [];
    foreach (Schema::getTableListing(schemaQualified: false) as $table) {
        if (in_array($table, EXCLUDED, true)) {
            continue;
        }

        $primary = [];
        $unique = [];
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['primary']) {
                $primary = $index['columns'];
            } elseif ($index['unique'] && count($index['columns']) === 1) {
                $unique[] = $index['columns'][0];
            }
        }

        $foreign = [];
        foreach (Schema::getForeignKeys($table) as $key) {
            $foreign[$key['columns'][0]] = ['table' => $key['foreign_table'], 'column' => $key['foreign_columns'][0]];
        }

        $schema[$table] = [
            'columns' => array_map(
                fn (array $column): array => ['name' => $column['name'], 'nullable' => $column['nullable']],
                Schema::getColumns($table)
            ),
            'primary' => $primary,
            'unique' => $unique,
            'foreign' => $foreign,
        ];
    }

    unlink($database);
    ksort($schema);

    return $schema;
}

/**
 * マイグレーションの記述から、カラムごとの型の名前と説明を拾う。
 *
 * @return array<string, array<string, array{type: string, comment: string}>>
 */
function readMigrations(): array
{
    $definitions = [];
    foreach (glob(ROOT.'database/migrations/*.php') ?: [] as $file) {
        $source = (string) file_get_contents($file);
        if (preg_match_all("/Schema::create\('(\w+)',\s*function\s*\(Blueprint \\\$table\)\s*\{(.*?)\n\s*\}\);/s", $source, $blocks, PREG_SET_ORDER) === 0) {
            continue;
        }

        foreach ($blocks as [, $table, $body]) {
            foreach (explode(';', $body) as $statement) {
                if (preg_match('/\$table->(\w+)\(([^)]*)\)/', $statement, $call) !== 1) {
                    continue;
                }

                $comment = preg_match("/->comment\('([^']*)'\)/", $statement, $matched) === 1 ? $matched[1] : '';
                foreach (columnsOf($call[1], $call[2]) as $column => $type) {
                    $definitions[$table][$column] = ['type' => $type, 'comment' => $comment];
                }
            }
        }
    }

    return $definitions;
}

/**
 * Blueprint のメソッドと引数から、作られるカラムの名前と型の名前を返す。
 *
 * @return array<string, string> カラム名 => 型の名前
 */
function columnsOf(string $method, string $arguments): array
{
    preg_match_all("/'(\w+)'/", $arguments, $strings);
    preg_match_all('/(?<![\w\'])(\d+)(?![\w\'])/', $arguments, $numbers);
    $name = $strings[1][0] ?? null;

    return match ($method) {
        'id', 'bigIncrements' => [$name ?? 'id' => 'bigIncrements'],
        'foreignIdFor' => [$name ?? Str::snake((string) preg_replace('/^.*?(\w+)::class.*$/', '$1', $arguments)).'_id' => 'foreignId'],
        'softDeletes' => [$name ?? 'deleted_at' => 'softDeletes'],
        'rememberToken' => ['remember_token' => 'rememberToken'],
        'timestamps' => ['created_at' => 'timestamp', 'updated_at' => 'timestamp'],
        'morphs' => [$name.'_type' => 'string', $name.'_id' => 'unsignedBigInteger'],
        'unique', 'index', 'comment' => [],
        default => $name === null ? [] : [$name => ($method === 'datetime' ? 'dateTime' : $method).($numbers[1] === [] ? '' : '('.implode(',', $numbers[1]).')')],
    };
}

/**
 * テーブルごとの見出しと行の文言を決め、置き場所と大きさを割り当てる。
 * 幅は列ごとに、その列で一番長い行に合わせてそろえる。
 *
 * @param  array<string, array{columns: list<array{name: string, nullable: bool}>, primary: list<string>, unique: list<string>, foreign: array<string, array{table: string, column: string}>}>  $schema
 * @param  array<string, array<string, array{type: string, comment: string}>>  $definitions
 * @return array<string, array{title: string, rows: list<array{column: string, label: string}>, x: int, y: int, width: int, height: int}>
 */
function placeTables(array $schema, array $definitions): array
{
    $tables = [];
    foreach ($schema as $table => $structure) {
        $rows = [];
        foreach ($structure['columns'] as $column) {
            $name = $column['name'];
            $marks = array_filter([
                in_array($name, $structure['primary'], true) ? 'PK' : null,
                isset($structure['foreign'][$name]) ? 'FK' : null,
                in_array($name, $structure['unique'], true) ? 'UQ' : null,
            ]);
            $definition = $definitions[$table][$name] ?? ['type' => '', 'comment' => ''];
            $rows[] = ['column' => $name, 'label' => trim(sprintf(
                '%s%s : %s%s %s',
                $marks === [] ? '' : '['.implode(',', $marks).'] ',
                $name,
                $definition['type'],
                $column['nullable'] ? '（null 可）' : '',
                $definition['comment']
            ))];
        }
        $tables[$table] = ['title' => isset(NAMES[$table]) ? $table.'（'.NAMES[$table].'）' : $table, 'rows' => $rows];
    }

    $layout = LAYOUT;
    $rest = array_values(array_diff(array_keys($tables), array_merge(...$layout)));
    if ($rest !== []) {
        $layout[] = $rest;
    }

    $placed = [];
    $x = MARGIN;
    foreach ($layout as $column) {
        $longest = 0.0;
        foreach (array_intersect_key($tables, array_flip($column)) as $table) {
            foreach ([$table['title'], ...array_column($table['rows'], 'label')] as $text) {
                $longest = max($longest, textWidth($text));
            }
        }
        $width = max(MIN_TABLE_WIDTH, (int) ceil(($longest + PADDING * 3) / 10) * 10);

        $y = MARGIN;
        foreach ($column as $table) {
            if (! isset($tables[$table])) {
                continue;
            }
            $height = TITLE_HEIGHT + ROW_HEIGHT * count($tables[$table]['rows']);
            $placed[$table] = $tables[$table] + ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
            $y += $height + ROW_GAP;
        }
        $x += $width + COLUMN_GAP;
    }

    return $placed;
}

/**
 * 外部キーの関係を並べる。参照先が描かれないものは外す。
 *
 * @param  array<string, array{columns: list<array{name: string, nullable: bool}>, primary: list<string>, unique: list<string>, foreign: array<string, array{table: string, column: string}>}>  $schema
 * @param  array<string, array{title: string, rows: list<array{column: string, label: string}>, x: int, y: int, width: int, height: int}>  $tables
 * @return list<array{table: string, column: string, target: string, targetColumn: string, nullable: bool}>
 */
function relationsOf(array $schema, array $tables): array
{
    $relations = [];
    foreach ($schema as $table => $structure) {
        foreach ($structure['foreign'] as $column => $target) {
            if (! isset($tables[$target['table']])) {
                continue;
            }
            $nullable = false;
            foreach ($structure['columns'] as $definition) {
                if ($definition['name'] === $column) {
                    $nullable = $definition['nullable'];
                }
            }
            $relations[] = ['table' => $table, 'column' => $column, 'target' => $target['table'], 'targetColumn' => $target['column'], 'nullable' => $nullable];
        }
    }

    return $relations;
}

/**
 * 文字列の描画幅を見積もる。全角を半角2文字分として数える。
 */
function textWidth(string $text): float
{
    return mb_strwidth($text) * FONT_SIZE * 0.55;
}

/**
 * draw.io の形式で書き出す。同じスキーマからは同じ内容を返す。
 *
 * @param  array<string, array{title: string, rows: list<array{column: string, label: string}>, x: int, y: int, width: int, height: int}>  $tables
 * @param  list<array{table: string, column: string, target: string, targetColumn: string, nullable: bool}>  $relations
 */
function renderDrawio(array $tables, array $relations): string
{
    $cells = [];
    foreach ($tables as $name => $table) {
        $cells[] = sprintf(
            '<mxCell id="%s" value="%s" style="swimlane;fontStyle=1;childLayout=stackLayout;horizontal=1;startSize=%d;horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=0;marginBottom=0;html=1;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry" /></mxCell>',
            e($name),
            e($table['title']),
            TITLE_HEIGHT,
            $table['x'],
            $table['y'],
            $table['width'],
            $table['height']
        );
        foreach ($table['rows'] as $index => $row) {
            $cells[] = sprintf(
                '<mxCell id="%s" value="%s" style="text;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;spacingLeft=6;html=1;" vertex="1" parent="%s"><mxGeometry y="%d" width="%d" height="%d" as="geometry" /></mxCell>',
                e("{$name}.{$row['column']}"),
                e($row['label']),
                e($name),
                TITLE_HEIGHT + ROW_HEIGHT * $index,
                $table['width'],
                ROW_HEIGHT
            );
        }
    }

    foreach ($relations as $relation) {
        $cells[] = sprintf(
            '<mxCell id="%s" style="edgeStyle=entityRelationEdgeStyle;html=1;startArrow=ERmany;startFill=0;endArrow=%s;endFill=0;" edge="1" parent="1" source="%s" target="%s"><mxGeometry relative="1" as="geometry" /></mxCell>',
            e("fk-{$relation['table']}-{$relation['column']}"),
            $relation['nullable'] ? 'ERzeroToOne' : 'ERmandOne',
            e("{$relation['table']}.{$relation['column']}"),
            e("{$relation['target']}.{$relation['targetColumn']}")
        );
    }

    return '<mxfile host="generate-er.php">'."\n"
        .'  <diagram id="er" name="ER図">'."\n"
        .'    <mxGraphModel grid="1" gridSize="10" page="0" math="0" shadow="0">'."\n"
        .'      <root>'."\n"
        .'        <mxCell id="0" />'."\n"
        .'        <mxCell id="1" parent="0" />'."\n"
        .implode("\n", array_map(fn (string $cell): string => '        '.$cell, $cells))."\n"
        .'      </root>'."\n"
        .'    </mxGraphModel>'."\n"
        .'  </diagram>'."\n"
        .'</mxfile>'."\n";
}

/**
 * SVG で書き出す。線はテーブルのあいだの通り道を縦に走らせ、重ならないよう少しずつずらす。
 * 線はテーブルより先に描き、テーブルの下をくぐる部分はテーブルで隠す。
 *
 * @param  array<string, array{title: string, rows: list<array{column: string, label: string}>, x: int, y: int, width: int, height: int}>  $tables
 * @param  list<array{table: string, column: string, target: string, targetColumn: string, nullable: bool}>  $relations
 */
function renderSvg(array $tables, array $relations): string
{
    $right = max(array_map(fn (array $t): int => $t['x'] + $t['width'], $tables)) + MARGIN + 20;
    $bottom = max(array_map(fn (array $t): int => $t['y'] + $t['height'], $tables)) + MARGIN;
    $height = $bottom + 30;

    $lanes = [];
    $lines = [];
    foreach ($relations as $relation) {
        $source = $tables[$relation['table']];
        $target = $tables[$relation['target']];
        $sy = rowCenter($source, $relation['column']);
        $ty = rowCenter($target, $relation['targetColumn']);

        if ($target['x'] > $source['x']) {
            [$sx, $sd, $tx, $td, $base] = [$source['x'] + $source['width'], 1, $target['x'], -1, $source['x'] + $source['width'] + COLUMN_GAP / 2];
        } elseif ($target['x'] < $source['x']) {
            [$sx, $sd, $tx, $td, $base] = [$source['x'], -1, $target['x'] + $target['width'], 1, $source['x'] - COLUMN_GAP / 2];
        } else {
            [$sx, $sd, $tx, $td, $base] = [$source['x'] + $source['width'], 1, $target['x'] + $target['width'], 1, $source['x'] + $source['width'] + 24];
        }
        $lane = $lanes[$base] = ($lanes[$base] ?? -1) + 1;
        $mx = $base + ($target['x'] === $source['x'] ? $lane * 8 : ($lane % 2 === 0 ? 1 : -1) * intdiv($lane + 1, 2) * 8);

        $lines[] = sprintf('<path d="M%d %d H%d V%d H%d" fill="none" stroke="#5f6b7a" stroke-width="1.2" />', $sx + $sd * 12, $sy, $mx, $ty, $tx + $td * 12);
        $lines[] = sprintf(
            '<path d="M%1$d %2$d L%3$d %4$d M%1$d %2$d L%3$d %2$d M%1$d %2$d L%3$d %5$d" fill="none" stroke="#5f6b7a" stroke-width="1.2" />',
            $sx + $sd * 12,
            $sy,
            $sx,
            $sy - 6,
            $sy + 6
        );
        $lines[] = sprintf('<path d="M%d %d H%d M%d %d V%d" fill="none" stroke="#5f6b7a" stroke-width="1.2" />', $tx, $ty, $tx + $td * 12, $tx + $td * 6, $ty - 6, $ty + 6);
        if ($relation['nullable']) {
            $lines[] = sprintf('<circle cx="%d" cy="%d" r="4" fill="#ffffff" stroke="#5f6b7a" stroke-width="1.2" />', $tx + $td * 16, $ty);
        }
    }

    $boxes = [];
    foreach ($tables as $table) {
        $boxes[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" fill="#ffffff" stroke="#5f6b7a" />', $table['x'], $table['y'], $table['width'], $table['height']);
        $boxes[] = sprintf('<rect x="%d" y="%d" width="%d" height="%d" fill="#dae8fc" stroke="#5f6b7a" />', $table['x'], $table['y'], $table['width'], TITLE_HEIGHT);
        $boxes[] = sprintf('<text x="%d" y="%d" font-weight="bold" text-anchor="middle">%s</text>', $table['x'] + intdiv($table['width'], 2), $table['y'] + 20, e($table['title']));
        foreach ($table['rows'] as $index => $row) {
            $top = $table['y'] + TITLE_HEIGHT + ROW_HEIGHT * $index;
            if ($index > 0) {
                $boxes[] = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#e3e7ec" />', $table['x'], $top, $table['x'] + $table['width'], $top);
            }
            $boxes[] = sprintf('<text x="%d" y="%d">%s</text>', $table['x'] + PADDING, $top + 16, e($row['label']));
        }
    }

    $legend = sprintf(
        '<text x="%d" y="%d" fill="#5f6b7a">[PK] 主キー　[FK] 外部キー　[UQ] 一意制約　線の端は、三つ又が多、縦線が1、丸と縦線が0か1を表す。docs/architecture/er.drawio と同じく scripts/generate-er.php で作る。</text>',
        MARGIN,
        $bottom + 10
    );

    return sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d" font-family="sans-serif" font-size="%3$d" fill="#1f2328">', $right, $height, FONT_SIZE)."\n"
        .sprintf('  <rect width="%d" height="%d" fill="#ffffff" />', $right, $height)."\n"
        .implode("\n", array_map(fn (string $line): string => '  '.$line, [...$lines, ...$boxes, $legend]))."\n"
        .'</svg>'."\n";
}

/**
 * テーブルの中で、指定したカラムの行の縦の中心を返す。
 *
 * @param  array{title: string, rows: list<array{column: string, label: string}>, x: int, y: int, width: int, height: int}  $table
 */
function rowCenter(array $table, string $column): int
{
    $index = (int) array_search($column, array_column($table['rows'], 'column'), true);

    return $table['y'] + TITLE_HEIGHT + ROW_HEIGHT * $index + intdiv(ROW_HEIGHT, 2);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
