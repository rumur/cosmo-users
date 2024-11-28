<?php

declare(strict_types=1);

namespace Rumur\WordPress\CosmoUsers\Support\Http;

use Closure;
use Exception;
use Throwable;
use Fiber;
use Rumur\WordPress\CosmoUsers\Client;
use WP_Error;
use Requests as DeprecatedRequests;
use WpOrg\Requests\Requests as WpOrgRequests;

/**
 * WordPress concurrent request client.
 * Leverages PHP 8.1's Fibers to resolve the requests concurrently.
 *
 * @link https://www.php.net/manual/en/class.fiber.php
 *
 * @since 0.1.0
 *
 * @package Rumur\WordPress\CosmoUsers\Support\Http
 */
class Concurrent implements Client
{
    /**
     * Resolves given requests concurrently.
     *
     * Example:
     * ```
     *  Concurrent::resolve([
     *    fn() => wp_remote_get('https://jsonplaceholder.typicode.com/todos/1'),
     *    fn() => wp_safe_remote_get('https://jsonplaceholder.typicode.com/todos/2'),
     *    fn() => wp_remote_post('https://jsonplaceholder.typicode.com/posts', [
     *      body' => [ 'title' => 'foo', 'body' => 'bar', 'userId' => 2323 ]]
     *   ),
     *  ]);
     * ```
     *
     * @param iterable<callable> $requests Collection of request callables.
     *
     * @return array<array> Resolved responses.
     *
     * @throws Throwable
     */
    public function resolve(iterable $requests): array // phpcs:ignore Inpsyde.CodeQuality.NestingLevel.High
    {
        // Create an interceptor to handle the requests.
        $interceptor = $this->createInterceptor();

        // Suspend PHP execution and wait for responses via `WpOrg\Requests\Requests::request_multiple`.
        // Resume Fiber execution after receiving responses, making WordPress unaware of concurrent processing.
        add_filter('pre_http_request', $interceptor, 10, 3);

        // Prepare Fibers for each request.
        $fibers = $this->prepareFibers($requests);

        $queue = [];
        $result = [];
        $resolved = [];

        // Process each request Fiber until all are resolved.
        while ($fibers !== []) {
            foreach ($fibers as $idx => $fiber) {
                try {
                    if (!$fiber->isStarted()) {
                        // Step 1: Start the Fiber and capture arguments from $interceptor closure.
                        $queue[$idx] = $fiber->start();
                    } elseif ($fiber->isSuspended() && isset($resolved[$idx])) {
                        // Step 3: Resume the Fiber with the resolved response.
                        $fiber->resume($resolved[$idx]);
                    }

                    // Handle Fiber termination:
                    // - If it throws an exception.
                    // - If it resolves or doesn't suspend.
                    // Adjust states to prevent infinite loops or unresolved requests.
                    if ($fiber->isTerminated()) {
                        // Step 4: Capture the result when Fiber terminates.
                        $result[$idx] = $fiber->getReturn();

                        // Remove the Fiber and related data from the processing.
                        unset($fibers[$idx], $queue[$idx], $resolved[$idx], $requests[$idx]);
                    }
                } catch (Throwable $exception) {
                    unset($fibers[$idx]);

                    // When the exception is thrown and fiber is not yet suspended,
                    // It means that the issue is occurred before the interception,
                    // So in this case we should bubble up the exception,
                    // as it has to break the whole queue.
                    if (!$fiber->isSuspended()) {
                        throw $exception;
                    }

                    $fiber->throw($exception); // Throw the exception if Fiber terminates with one.
                }
            }

            // Process the entire queue of suspended requests at once.
            if (count($requests) === count($queue)) {
                $this->resolveQueue($queue, $resolved);
                $queue = []; // Clear the queue after processing.
            }
        }

        remove_filter('pre_http_request', $interceptor, 10);

        return $result;
    }

    /**
     * Transform a response before returning it.
     *
     * @param mixed $response The response from dispatch.
     * @param string $url The request URL.
     * @param array $args The request arguments.
     *
     * @return array{
     *     url: string,
     *     body:string,
     *     status: int,
     *     headers:array|\WpOrg\Requests\Utility\CaseInsensitiveDictionary,
     *     cookies: array<\WP_Http_Cookie>,
     *     error: ?WP_Error,
     *     args: array,
     * } Transformed response.
     */
    protected function transform(mixed $response, string $url, array $args): array
    {
        return match (true) {
            is_wp_error($response) => [
                'url' => $url,
                'body' => $response->get_error_message(),
                'status' => (int) $response->get_error_code(),
                'headers' => [],
                'cookies' => [],
                'error' => $response,
                'args' => $args,
            ],

            $response instanceof Exception => [
                'url' => $url,
                'body' => $response->getMessage(),
                'status' => $response->getCode(),
                'headers' => [],
                'cookies' => [],
                'error' => new WP_Error(
                    'http_request_failed_' . esc_attr($response->getCode()),
                    esc_html($response->getMessage())
                ),
                'args' => $args,
            ],

            default => [
                'url' => $url,
                'body' => wp_remote_retrieve_body($response),
                'status' => (int) (wp_remote_retrieve_response_code($response) ?: 200),
                'headers' => wp_remote_retrieve_headers($response),
                'cookies' => wp_remote_retrieve_cookies($response),
                'error' => null,
                'args' => $args,
            ],
        };
    }

    /**
     * Create an interceptor for Fiber-based request handling.
     *
     * @return Closure The interceptor for the 'pre_http_request' filter.
     */
    protected function createInterceptor(): Closure
    {
        /**
         * WordPress filter callback to intercept requests.
         *
         * @param false|array|WP_Error $response Preemptive return value of an HTTP request.
         *                                       Default false.
         * @param array $args HTTP request arguments.
         * @param string $url The request URL.
         *
         * @return mixed Response from the middleware pipeline.
         *
         * @throws \FiberError If the Fiber cannot be started.
         */
        return static function ($response, array $args, string $url): mixed {
            if (false !== $response) {
                return $response; // Return if the request is already resolved (e.g., in unit tests).
            }

            return Fiber::suspend(['args' => $args, 'url' => $url]);
        };
    }

    /**
     * Prepare Fibers for the given request callbacks.
     *
     * @param iterable<callable> $requestCallbacks Collection of request callables.
     *
     * @return array<int, Fiber> Prepared Fibers.
     */
    protected function prepareFibers(iterable $requestCallbacks): array
    {
        $fibers = [];

        foreach ($requestCallbacks as $idx => $requestCallback) {
            if (!is_callable($requestCallback)) {
                $requestCallback = static fn () => $requestCallback;
            }

            $fibers[$idx] = new Fiber($requestCallback);
        }

        return $fibers;
    }

    /**
     * Resolve a queue of requests.
     *
     * @param array $queue The queue of requests.
     * @param array $resolved The resolved responses.
     *
     *
     * @throws FailToResolve If neither `\\WpOrg\\Requests\\Requests` nor `\\Requests` class exists.
     */
    protected function resolveQueue(array $queue, array &$resolved): void
    {
        // Convert wp_remote_* requests to Requests library requests for concurrent dispatching.
        $queueRequests = array_map($this->prepareQueueItemParams(...), $queue);

        // Dispatch the requests concurrently using the appropriate WordPress Requests library.
        $responses = match (true) {
            class_exists(WpOrgRequests::class) => WpOrgRequests::request_multiple($queueRequests),
            class_exists(DeprecatedRequests::class) => DeprecatedRequests::request_multiple($queueRequests),
            default => throw new FailToResolve('Unable to dispatch requests concurrently. Neither `\\WpOrg\\Requests\\Requests` nor `\\Requests` class exists.'), // phpcs:ignore Inpsyde.CodeQuality.LineLength.TooLong
        };

        // Process each response and resolve any exceptions or WP_Errors.
        foreach ($queue as $idx => ['args' => $args, 'url' => $url]) {
            $response = $responses[$idx];

            if ($response instanceof Exception || is_wp_error($response)) {
                $resolved[$idx] = $this->transform($response, $url, $args);
                continue;
            }

            $resolved[$idx] = $this->transform(
                (new \WP_HTTP_Requests_Response($response))->to_array(),
                $url,
                $args
            );
        }
    }

    /**
     * Prepare queue item parameters for a request.
     *
     * @param array{url: string, args: array} $item The queue item.
     *
     * @return array Prepared request parameters.
     */
    protected function prepareQueueItemParams(array $item): array
    {
        ['args' => $args, 'url' => $url] = $item;

        return [
            'url' => $url,
            'data' => $args['body'] ?? '',
            'type' => $args['method'] ?? 'GET',
            'headers' => $args['headers'] ?? [],
            'cookies' => $args['cookies'] ?? [],
        ];
    }
}
