<h1>Evolv Php Sdk</h1>

<h2>Install Php-Sdk through composer:</h2>


```php
  composer require evolv/php-sdk
```

<h2>Install</h2>

```php
  composer install
```
<h2>Start Eхample</h2>

```php
  composer start
```

<h2>Generate Documentation</h2>

```php
  composer docs
```

<h2>Run Tests</h2>

```php
  composer test
```


<h2>Package Usage</h2>

```php
  <?php

  declare (strict_types=1);

  require_once __DIR__ . '/vendor/autoload.php';
  use Evolv\EvolvClient;
```

<h2>Client Initialization</h2>

```php
  <?php

  $client = new EvolvClient($environment, $endpoint);
  $client->initialize($uid);
```

<h2>About Evolv Product</h2>

Evolv Delivers Autonomous Optimization Across Web & Mobile.

You can find out more by visiting: <a href="https://www.evolv.ai/">https://www.evolv.ai/</a>
