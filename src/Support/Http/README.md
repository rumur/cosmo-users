# Concurrent HTTP Client

## Introduction

**Concurrent HTTP Client** enables concurrent request dispatching using **PHP Fibers** [Official Documentation](https://www.php.net/manual/en/language.fibers.php), introduced in PHP 8.1.
It allows developers to perform concurrent, non-blocking requests within WordPress, enhancing performance and improving user experience.

### How it works

WordPress has a built-in HTTP API that allows developers to send HTTP requests using the `wp_remote_*` functions.
These functions are sequential; when they are called, they block the execution of the script until the request is completed.

However, if we look inside the WordPress HTTP package, we can find there a very interesting method - `request_multiple`.  
This method allows us to send multiple requests concurrently, but it's not possible to use it with the `wp_remote_*` functions directly.

This is where Fibers come into play.

We know that each `wp_remote_*` function will be called via a filter `pre_http_request`, which can be hijacked if we return anything that is not a `false` from it.
So with that said, we can hijack the `pre_http_request` filter and replace the `wp_remote_*` functions with the `request_multiple` method.

So, when we pass an array of closures to the `resolve` method, it creates a Fiber for each closure,
then it creates an interceptor and hooks via the `pre_http_request` filter; it means that all requests will be passed through this interceptor,
where with the help of Fiber we pause the execution of each request, take its args, and put it into a queue.
Once all requests are in the queue, we take all requests from the queue and send them via the `request_multiple` method concurrently,
once we have responses, we resume the execution of each request and return the response as it would have been returned by the `wp_remote_*` function natively.

## Features

- **Concurrent Request Handling**: Resolves multiple HTTP requests concurrently.
- **Easy Integration**: Seamlessly integrates with WordPress `wp_(safe)_remote_*` functions.
- **Modern PHP**: Leverages PHP 8.1 Fibers for optimal performance.

## Requirements

- **PHP**: Version **8.1** or higher.
- **WordPress**: Version **5.8** or higher.

## Benchmark

When testing a delivery of 40 requests, we found that the concurrent approach is approximately 108% faster than the sequential one.

<details>
  <summary>See Example:</summary>

```php
$create_requests = fn() => array_map(
	fn($id) => fn() => wp_remote_retrieve_body(
		wp_remote_get(
			sprintf('https://jsonplaceholder.typicode.com/todos/%s/?lorem=%s', $id, wp_generate_uuid4())
		)
	), range(1, 40)
);

timer_start();

$concurrent_results = (new \Rumur\WordPress\CosmoUsers\Support\Http\Concurrent())->resolve($create_requests());

$concurrent_time = timer_stop();

timer_start();

$sequential_results = array_map('call_user_func', $create_requests());

$sequential_time = timer_stop();

echo '<pre>';
print_r( [
	'concurrent' => [
		'requests' => count( $concurrent_results ),
		'time'     => $concurrent_time,
	],
	'sequential' => [
		'requests' => count( $sequential_results ),
		'time'     => $sequential_time,
	],
] );
echo '</pre>';
exit;
```
</details>

Result:
```
Array
(
    [concurrent] => Array
        (
            [requests] => 40
            [time] => 1.511
        )

    [sequential] => Array
        (
            [requests] => 40
            [time] => 18.896
        )

)
```

## Usage

You can mix GET and POST requests.

```php
$responses = ( new \Rumur\WordPress\CosmoUsers\Support\Http\Concurrent() )->resolve([
	fn() => wp_remote_get('https://jsonplaceholder.typicode.com/todos/1'),
	fn() => wp_safe_remote_get('https://jsonplaceholder.typicode.com/todos/2'),
	fn() => wp_remote_post('https://jsonplaceholder.typicode.com/posts', [
	   'body' => ['title' => 'foo', 'body' => 'bar', 'userId' => 2024]]
	),
]);


$results = array_map(
	fn($res) => json_decode(wp_remote_retrieve_body($res)),
	array_filter($responses, fn($res) => wp_remote_retrieve_response_code($res) < 300)
);
```

Result:
```
Array
(
    [0] => stdClass Object
        (
            [userId] => 1
            [id] => 1
            [title] => delectus aut autem
            [completed] => 
        )

    [1] => stdClass Object
        (
            [userId] => 1
            [id] => 2
            [title] => quis ut nam facilis et officia qui
            [completed] => 
        )

    [2] => stdClass Object
        (
            [title] => foo
            [body] => bar
            [userId] => 2024
            [id] => 101
        )

)
```

### Back to [Plugin](./../../../README.md)
