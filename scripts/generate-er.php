<?php

/**
 * マイグレーションから ER 図（docs/architecture/er.drawio）を作り直す。
 *
 * スキーマの正はマイグレーションである。ER 図を手で直すと、いずれマイグレーションと食い違う。
 * そのため ER 図は手で直さず、このスクリプトで作り直す。--check を付けると、作り直した内容と
 * 今の ER 図を比べ、違えば終了コード 1 を返す。composer check-all から呼び、スキーマを変えたのに
 * ER 図を作り直していなければ、作業の終わりと継続的インテグレーションで止まる。
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
const OUTPUT = 'docs/architecture/er.drawio';

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

const TABLE_WIDTH = 420;
const COLUMN_GAP = 120;
const ROW_GAP = 60;
const TITLE_HEIGHT = 30;
const ROW_HEIGHT = 24;

$checking = in_array('--check', $argv, true);

$schema = readSchema();
$definitions = readMigrations();
$xml = render($schema, $definitions);

if ($checking) {
    $current = is_file(ROOT.OUTPUT) ? (string) file_get_contents(ROOT.OUTPUT) : '';
    if ($current !== $xml) {
        fwrite(STDERR, OUTPUT." がマイグレーションと食い違っている。scripts/dc php scripts/generate-er.php で作り直す。\n");
        exit(1);
    }
    echo OUTPUT." はマイグレーションと一致している。\n";
    exit(0);
}

file_put_contents(ROOT.OUTPUT, $xml);
echo OUTPUT." を書き出した。\n";

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
 * draw.io の形式で ER 図を組み立てる。同じスキーマからは同じ内容を返す。
 *
 * @param  array<string, array{columns: list<array{name: string, nullable: bool}>, primary: list<string>, unique: list<string>, foreign: array<string, array{table: string, column: string}>}>  $schema
 * @param  array<string, array<string, array{type: string, comment: string}>>  $definitions
 */
function render(array $schema, array $definitions): string
{
    $layout = LAYOUT;
    $placed = array_merge(...$layout);
    $rest = array_values(array_diff(array_keys($schema), $placed));
    if ($rest !== []) {
        $layout[] = $rest;
    }

    $cells = [];
    $x = 40;
    foreach ($layout as $column) {
        $y = 40;
        foreach ($column as $table) {
            if (! isset($schema[$table])) {
                continue;
            }
            $cells[] = tableCells($table, $schema[$table], $definitions[$table] ?? [], $x, $y);
            $y += TITLE_HEIGHT + ROW_HEIGHT * count($schema[$table]['columns']) + ROW_GAP;
        }
        $x += TABLE_WIDTH + COLUMN_GAP;
    }

    foreach ($schema as $table => $structure) {
        foreach ($structure['foreign'] as $column => $target) {
            if (! isset($schema[$target['table']])) {
                continue;
            }
            $nullable = current(array_filter($structure['columns'], fn (array $c): bool => $c['name'] === $column))['nullable'] ?? false;
            $end = $nullable ? 'ERzeroToOne' : 'ERmandOne';
            $cells[] = sprintf(
                '<mxCell id="%s" style="edgeStyle=entityRelationEdgeStyle;html=1;startArrow=ERmany;startFill=0;endArrow=%s;endFill=0;" edge="1" parent="1" source="%s" target="%s"><mxGeometry relative="1" as="geometry" /></mxCell>',
                e("fk-{$table}-{$column}"),
                $end,
                e("{$table}.{$column}"),
                e("{$target['table']}.{$target['column']}")
            );
        }
    }

    return '<mxfile host="generate-er.php">'."\n"
        .'  <diagram id="er" name="ER図">'."\n"
        .'    <mxGraphModel grid="1" gridSize="10" page="0" math="0" shadow="0">'."\n"
        .'      <root>'."\n"
        .'        <mxCell id="0" />'."\n"
        .'        <mxCell id="1" parent="0" />'."\n"
        .implode("\n", array_map(fn (string $cell): string => preg_replace('/^/m', '        ', $cell), $cells))."\n"
        .'      </root>'."\n"
        .'    </mxGraphModel>'."\n"
        .'  </diagram>'."\n"
        .'</mxfile>'."\n";
}

/**
 * テーブル1つ分の枠とカラムの行を組み立てる。
 *
 * @param  array{columns: list<array{name: string, nullable: bool}>, primary: list<string>, unique: list<string>, foreign: array<string, array{table: string, column: string}>}  $structure
 * @param  array<string, array{type: string, comment: string}>  $definitions
 */
function tableCells(string $table, array $structure, array $definitions, int $x, int $y): string
{
    $title = isset(NAMES[$table]) ? $table.'（'.NAMES[$table].'）' : $table;
    $height = TITLE_HEIGHT + ROW_HEIGHT * count($structure['columns']);
    $lines = [sprintf(
        '<mxCell id="%s" value="%s" style="swimlane;fontStyle=1;childLayout=stackLayout;horizontal=1;startSize=%d;horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=0;marginBottom=0;html=1;" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry" /></mxCell>',
        e($table),
        e($title),
        TITLE_HEIGHT,
        $x,
        $y,
        TABLE_WIDTH,
        $height
    )];

    foreach ($structure['columns'] as $index => $column) {
        $name = $column['name'];
        $marks = array_filter([
            in_array($name, $structure['primary'], true) ? 'PK' : null,
            isset($structure['foreign'][$name]) ? 'FK' : null,
            in_array($name, $structure['unique'], true) ? 'UQ' : null,
        ]);
        $definition = $definitions[$name] ?? ['type' => '', 'comment' => ''];
        $label = trim(sprintf(
            '%s%s : %s%s %s',
            $marks === [] ? '' : '['.implode(',', $marks).'] ',
            $name,
            $definition['type'],
            $column['nullable'] ? '（null 可）' : '',
            $definition['comment']
        ));
        $lines[] = sprintf(
            '<mxCell id="%s" value="%s" style="text;strokeColor=none;fillColor=none;align=left;verticalAlign=middle;spacingLeft=6;html=1;" vertex="1" parent="%s"><mxGeometry y="%d" width="%d" height="%d" as="geometry" /></mxCell>',
            e("{$table}.{$name}"),
            e($label),
            e($table),
            TITLE_HEIGHT + ROW_HEIGHT * $index,
            TABLE_WIDTH,
            ROW_HEIGHT
        );
    }

    return implode("\n", $lines);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
