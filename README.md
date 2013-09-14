facebook-analysis
=================

### Example

http://54.250.223.230/facebook-analysis

### 사전준비 사항

* facebook 폴더에 key-info.php 파일 생성
* key-info.php 에 다음과 같이 입력

```php
<?php
    $appId = '';
    $secret = '';
    $redirect_url = 'http://[hostname]/facebook/get-token.php';
    $logout_redirect_url = 'http://[hostname]/index.html';
?>
```



