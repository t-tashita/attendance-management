# coachtech勤怠管理アプリ  

## 環境構築  

1.GitHub からクローンする
```
git clone git@github.com:t-tashita/attendance-management
```  

2.DockerDesktopアプリを立ち上げる  

3.プロジェクト直下で、以下のコマンドを実行する  
```
make init
```

# メール認証  

mailtrapというツールを使用しています。  
以下のリンクから会員登録をしてください。  　
https://mailtrap.io/  

メールボックスのIntegrationsから 「laravel 7.x and 8.x」を選択し、  
.envファイルのMAIL_MAILERからMAIL_ENCRYPTIONまでの項目をコピー＆ペーストしてください。  
MAIL_FROM_ADDRESSは任意のメールアドレスを入力してください。  

# ER図  

# テストアカウント
## 一般ユーザ
name: 山田 太郎  
email: taro.y@coachtech.com  
password: password  
※他5アカウントpassword共通  

## 管理者ユーザ  
name: 管理者  
email: admin@coachtech.com  
password: password  

# URL  
・ユーザログイン画面:http://localhost/login  
・管理者ログイン画面:http://localhost/admin/login  

# PHPUnitを利用したテストに関して  
以下のコマンド:  

//テスト用データベースの作成  
```
docker-compose exec mysql bash  
```
```
mysql -u root -p  
```
//パスワードはrootと入力  
```
create database test_db;  
```
```
docker-compose exec php bash  
```
```
php artisan migrate:fresh --env=testing  
```
```
./vendor/bin/phpunit  
```
