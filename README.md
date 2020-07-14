# Drop4IP

[Ban4ip](https://github.com/disco-v8/Ban4ip)を参考に作ってます。Ban4ipを使ったほうがいいのでそちらを利用してください。

***
## 機能
機能的にはBan4ipの縮小版です(FirewallでのIPアクセス制御しかしない)

***
## 技術
無駄に勉強のため[ReactiveX/RxPHP](https://github.com/ReactiveX/RxPHP)を利用してます。理解はしてません。

***
## 必要環境
php >= 7.4

inotify

***
## Drop4IPが使うライブラリインストール
```
composer install
```

***
## ログ
```
mkdir storage
touch storage/ipban.log
chmod 666 storage/ipban.log
```

***
## 実行
```
./drop4ipc start
```

***
## 手動バン追加(サンプル)
```
./drop4ipc --addban --process プロセス名(conf設定) --source IPアドレス --protocol all --port all --rule DROP --effectivesecond 現在からの有効期限(second)
```

***
## 手動バン削除(サンプル)
```
./drop4ipc --removeban --process プロセス名(conf設定) --source IPアドレス --protocol all --port all --rule DROP
```
