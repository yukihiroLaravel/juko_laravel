<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class CompareRoute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:compare-route';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '「openapi.yaml」と「アプリ側」のルーティング比較';

    /**
     * 「文字列長がnより大きく、かつ、先頭が半角スペースn個である、かつ、先頭が半角スペースn個の次の文字が半角スペースでない」かどうかを判定する。
     *
     * @param  string  $string  対象文字列
     * @param  int  $n  半角スペースの数
     * @return bool 判定結果
     */
    private function startsSpacesN(string $string, int $n): bool
    {
        // 文字列長がnより小さい場合はfalse
        if (strlen($string) <= $n) {
            return false;
        }

        // 半角スペースをn個作成
        $spaces = str_repeat(' ', $n);

        // 先頭が半角スペースn個で始まること
        $ret1 = (substr($string, 0, $n) === $spaces);

        // 先頭が半角スペースn個の次の文字が半角スペースでないこと
        $ret2 = (substr($string, $n, 1) !== ' ');

        // 返却値
        $ret = ($ret1 && $ret2);

        return $ret;
    }

    /**
     * 「:」を取り除きtrimする
     *
     * @param  string  $string
     */
    private function trimAndRemoveColon($string): string
    {
        return trim(str_replace(':', '', $string));
    }

    /**
     * 「$argIndexの次のuriのindexまたは、末尾のindex」を取得する。
     *
     * @param  int  $argIndex  指定index
     * @param  array  $uriIndexesEx  uriまたは「末尾に「$lineCount」」のindex
     * @param  int  $uriIndexesExCount  要素数
     * @return int 「$argIndexの次のuriのindexまたは、末尾のindex」
     */
    private function getNextUriIndex(int $argIndex, array $uriIndexesEx, int $uriIndexesExCount): int
    {
        for ($index = 0; $index < $uriIndexesExCount; $index++) {
            $currentIndex = $uriIndexesEx[$index];
            if ($currentIndex > $argIndex) {
                return $currentIndex;
            }
        }
        // $uriIndexesExを作る時に、「  末尾に「$lineCount」を追加  」してるため、あり得ないはず。
        throw new Exception('invalid status. 00300');
    }

    /**
     * methodSortIndexを返す
     *
     * @param  string  $method  HTTPのmethod
     * @return string methodSortIndexを返す
     */
    private function getMethodSortIndex(string $method): string
    {
        /*
            GET
            POST
            PATCH
            PUT
            DELETE
            の順番になるようにするためのmethodSortIndexを返す
        */
        if ($method === 'get') {
            return '0';
        }
        if ($method === 'post') {
            return '1';
        }
        if ($method === 'patch') {
            return '2';
        }
        if ($method === 'put') {
            return '3';
        }
        if ($method === 'delete') {
            return '4';
        }

        // $methodが上記以外となるのは、想定外のため例外を投げる
        throw new Exception('invalid status. 00400');
    }

    private function loadOpenapi()
    {
        /*
            当コマンドを実行するにあたっての前提

            dockerコンテナ内部で当コマンドが実行され、
            ホスト側の「openapi.yaml」を直接的に読み込みにいけないため
            一旦、ホスト側の
            /myDocker/laravel_next_docker/backend/laravelapp
            の場所にて、
            cp ../../api/openapi.yaml .
            で、コピーして
            /myDocker/laravel_next_docker/backend/laravelapp/openapi.yaml
            が存在する状況。
            すなわち、
            /var/www/html/laravelapp/openapi.yaml
            が存在する状況。
            getcwd()
            が「/var/www/html/laravelapp」を返す状況にて
            当コマンドを実行することを前提にしている。
        */

        // カレントディレクトリを取得
        $currentDirectory = getcwd();

        $filePath = $currentDirectory.'/openapi.yaml';

        if (! file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $file = fopen($filePath, 'r');
        if (! $file) {
            throw new Exception("Failed to open file: {$filePath}");
        }

        /** @var string[] $lines */
        $lines = [];

        $initialSkip = true;
        try {
            while (($line = fgets($file)) !== false) {
                // 末尾の改行を取り除く
                $line = rtrim($line, "\n");

                /*
                    *****
                    info:
                      title: 受講管理API
                      version: 1.0.0
                    *****
                    上記の部分で、「urlの次の行がmethodで取得するべき」の前提が崩れるため
                    paths:
                    までの部分を読み捨てることにした
                */
                if ($line === 'paths:') {
                    $initialSkip = false;

                    continue;
                }
                if ($initialSkip) {
                    continue;
                }

                $lines[] = $line;
            }
        } finally {
            fclose($file);
        }

        $lineCount = count($lines);

        /*
            「uriがあった位置のindex」および、末尾に「$lineCount」を追加

            「uriの行index」から「 「次のの行index」の1つ手前の行index  または  「$lineCount」の1つ手前の行index　」
            までの間で、該当のuriに関連するmethodの繰り返しを複数個、拾うための探索処理の範囲の制御を
            するためには、あらかじめ、「uriの行index」および、末尾に「$lineCount」の値を
            $uriIndexesExで保持しておく必要がある
            名前に「Ex」をつけてるのは、末尾に「$lineCount」の値を指定しているため
            ループを継続する判定式では、「<」で判定のため
            「$lineCount」の値をindex指定したアクセスが行われないことを前提にしてる。
        */
        $retUri = false;
        $uriIndexesEx = [];
        for ($index = 0; $index < $lineCount; $index++) {
            $line = $lines[$index];

            $retUri = $this->startsSpacesN($line, 2);
            if ($retUri) {
                // 「uriがあった位置のindex」を追加
                $uriIndexesEx[] = $index;
            }
        }
        // 末尾に「$lineCount」を追加 ( passed the end )
        $uriIndexesEx[] = $lineCount;
        $uriIndexesExCount = count($uriIndexesEx);

        $routeList = [];
        for ($index = 0; $index < $lineCount; $index++) {
            $line = $lines[$index];

            $retUri = false;
            $retMethod = false;

            $uri = '';
            $methods = [];

            $isAddRouteList = false;

            $retUri = $this->startsSpacesN($line, 2);
            if ($retUri) {
                $checkFlag = false;

                $uri = $this->trimAndRemoveColon($line);

                $methodLoopStartIndex = $index;
                $methodLoopEndIndex = $this->getNextUriIndex($methodLoopStartIndex, $uriIndexesEx, $uriIndexesExCount);
                for ($methodSearchIndex = $methodLoopStartIndex; $methodSearchIndex < $methodLoopEndIndex; $methodSearchIndex++) {
                    $methodSearchLine = $lines[$methodSearchIndex];

                    $retMethod = $this->startsSpacesN($methodSearchLine, 4);
                    if ($retMethod) {
                        $methods[] = $this->trimAndRemoveColon($methodSearchLine);

                        $checkFlag = true;
                    }
                }

                if (! $checkFlag) {
                    // 「uri」の次の行としての「method」が取得できない状況は、想定外の状況なので例外を投げる。
                    throw new Exception('invalid status. 00100 lines:'.($index + 1));
                }

                $isAddRouteList = true;
            }

            if (! $isAddRouteList) {
                continue;
            }

            foreach ($methods as $method) {
                // methodSortIndexを返す
                $methodSortIndex = $this->getMethodSortIndex($method);

                $key = $uri.'###'.$methodSortIndex;

                $routeList[] = [
                    'key' => $key,
                    'method' => $method,
                    'uri' => $uri,
                ];
            }
        }

        // $routeListをkeyで昇順ソート
        $this->sortRouteListByKey($routeList);

        return $routeList;
    }

    private function loadRoutes()
    {

        // ルートリストを取得「php artisan route:list」と同じものが取得できるのは検証済
        /** @var \Illuminate\Routing\Route[] $routes */
        $routes = Route::getRoutes();

        $routeList = [];

        foreach ($routes as $route) {
            /*
                openapi.yamlとの比較がしやすいように加工
            */
            $method = implode('|', $route->methods());
            $uri = '/'.$route->uri();

            if ($method == 'GET|HEAD') {
                $method = 'get';
            }

            $method = strtolower($method);

            // methodSortIndexを返す
            $methodSortIndex = $this->getMethodSortIndex($method);

            $key = $uri.'###'.$methodSortIndex;

            $routeList[] = [
                'key' => $key,
                'method' => $method,
                'uri' => $uri,
            ];
        }

        // $routeListをkeyで昇順ソート
        $this->sortRouteListByKey($routeList);

        return $routeList;
    }

    public function sortRouteListByKey(&$routeList)
    {
        // keyで昇順ソート
        usort($routeList, fn ($a, $b) => $a['key'] <=> $b['key']);
    }

    /**
     * 「次ループ処理ありかどうか」を判定する。
     *
     * @param  int  $leftIndex
     * @param  int  $leftLength
     * @param  int  $rightIndex
     * @param  int  $rightLength
     * @return bool 次ループ処理ありかどうか
     */
    public function hasNext($leftIndex, $leftLength, $rightIndex, $rightLength): bool
    {
        $isLeftEnd = ($leftIndex >= $leftLength);
        $isRightEnd = ($rightIndex >= $rightLength);

        $isEnd = ($isLeftEnd && $isRightEnd);

        return ! $isEnd;
    }

    public function compare(
        $leftRouteList,
        $rightRouteList,
        &$leftOnlyRouteList,
        &$matchRouteList,
        &$rightOnlyRouteList
    ) {

        $leftLength = count($leftRouteList);
        $rightLength = count($rightRouteList);

        $leftIndex = 0;
        $rightIndex = 0;

        $bigKey = 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzz';

        while ($this->hasNext($leftIndex, $leftLength, $rightIndex, $rightLength)) {
            $currentLeft = [
                'key' => $bigKey,
                'method' => '',
                'uri' => '',
            ];
            if ($leftIndex < $leftLength) {
                $currentLeft = $leftRouteList[$leftIndex];
            }

            $currentRight = [
                'key' => $bigKey,
                'method' => '',
                'uri' => '',
            ];
            if ($rightIndex < $rightLength) {
                $currentRight = $rightRouteList[$rightIndex];
            }

            if (
                ($currentLeft['key'] === $bigKey)
                &&
                ($currentRight['key'] === $bigKey)
            ) {
                throw new Exception('Invalid status 00200');
            }

            $currentCompare = $currentLeft['key'] <=> $currentRight['key'];

            if ($currentCompare < 0) {
                // left only
                $leftOnlyRouteList[] = $currentLeft;

                $leftIndex++;
            } elseif ($currentCompare === 0) {
                // match
                $matchRouteList[] = $currentLeft;

                $leftIndex++;
                $rightIndex++;
            } else {
                // right only
                $rightOnlyRouteList[] = $currentRight;

                $rightIndex++;
            }
        }
    }

    public function echoOneRoute($route)
    {
        echo 'Method: '.strtoupper((string) $route['method']).', URI: '.$route['uri']."\n";
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        echo '#################################################################################################################'."\n";
        echo '全出力'."\n";
        echo '#################################################################################################################'."\n";

        $openapiRouteList = $this->loadOpenapi();
        echo '#######################################################################'."\n";
        echo '「openapi.yaml」全出力'."\n";
        echo '#######################################################################'."\n";
        foreach ($openapiRouteList as $route) {
            $this->echoOneRoute($route);
        }

        $applicationRouteList = $this->loadRoutes();

        echo '#######################################################################'."\n";
        echo '「アプリ側」全出力'."\n";
        echo '#######################################################################'."\n";
        foreach ($applicationRouteList as $route) {
            $this->echoOneRoute($route);
        }

        $openapiOnlyRouteList = [];
        $matchRouteList = [];
        $applicationOnlyRouteList = [];

        $this->compare(
            $openapiRouteList,
            $applicationRouteList,
            $openapiOnlyRouteList,
            $matchRouteList,
            $applicationOnlyRouteList
        );

        echo '#################################################################################################################'."\n";
        echo '比較結果出力'."\n";
        echo '#################################################################################################################'."\n";

        echo '#######################################################################'."\n";
        echo '「openapi.yaml」のみある分'."\n";
        echo '#######################################################################'."\n";
        foreach ($openapiOnlyRouteList as $route) {
            $this->echoOneRoute($route);
        }

        echo '#######################################################################'."\n";
        echo '両方ある分(keyマッチ分)'."\n";
        echo '#######################################################################'."\n";
        foreach ($matchRouteList as $route) {
            $this->echoOneRoute($route);
        }

        echo '#######################################################################'."\n";
        echo '「アプリ側」のみある分'."\n";
        echo '#######################################################################'."\n";
        foreach ($applicationOnlyRouteList as $route) {
            $this->echoOneRoute($route);
        }

        return true;
    }
}
