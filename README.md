# ErrorStream-Yii
This package is for yii 1.0 integration with [ErrorStream.com](https://errorstream.com/). 


# Installation:

1) Download the zip.

2) Extract the errorstream folder to your extensions directory.

3) Add the ErrorStreamLogger to your log routes in your config/main.php file:


```php

'log'=>array(
  	'class'=>'CLogRouter',
  	'routes'=>array(
        ...
        array(
          'class'         => 'application.extensions.errorstream.ErrorStreamLogger',
          'api_token'     => 'YOUR API TOKEN HERE',
          'project_token' => 'YOUR PROJECT TOKEN HERE',
          'levels'        => 'info, error, warning',
          'enabled'       => true,
        ),
  	),
),

```
