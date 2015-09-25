# Simple-Curl-Library
Basic how to

```php
echo Curl::request('https://wtfsi.xyz/test/post')
    ->withData([
        'some'=>'data',
        'should'=>'see',
        'this'=>'in',
        'response'
    ])
    ->post()
    ->send()
    ->toJson();
```
