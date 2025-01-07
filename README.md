# 受講管理アプリ バックエンドリポジトリ

## コード整形処理

1. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```shell
composer pint
```

## OpenAPIとルーティングの比較チェック
1. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```shell
cp ../../api/openapi.yaml .
```

2. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```php
php artisan dev:compare-route > compare-route.txt
```

## laravel-ide-helperの生成
1. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```shell
php artisan ide-helper:generate
```

2. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```shell
php artisan ide-helper:models -N
```

3. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```shell
php artisan ide-helper:meta
```

## テスト実行
1. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```shell
php artisan test
```

## 静的解析
1. `laravel_next_docker/backend/laravelapp`ディレクトリにて下記コマンドを実行する。

```shell
composer analyze
```
