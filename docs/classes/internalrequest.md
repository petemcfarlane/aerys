---
title: InternalRequest
permalink: /classes/internalrequest
---

* Table of Contents
{:toc}

This is a value class exposing the whole data of the clients request via public properties. It is only accessible from within [`Middleware`s](middleware.md) as well as [`HttpDriver`](httpdriver.md).

Values marked with a <sup>†</sup> **_must_** not be altered in order to not bring the server down.

## `$client`<sup>†</sup>

Holds a reference to the [`Client`](client.md).

## `$responseWriter`

A Generator instance following the [`HttpDriver::writer`](httpdriver.md) protocol.

## `$streamId`<sup>†</sup>

An integer (combined with `$client->id`) providing an unique identifier of a request during its lifetime.

## `$trace`

Literal string trace for HTTP/1, for HTTP/2 an array of [name, value] arrays in the original order.

## `$protocol`

HTTP protocol version string.

## `$method`

HTTP method string.

## `$headers`

Associative array of HTTP headers containing arrays of values. The header field names are always lowercased. (E.g. `["connection" => ["Keep-Alive", "Upgrade"], "host" => ["example.com"]]`)

## `$body`

An instance of [`Message`](https://amphp.org/byte-stream/message).

## `$maxBodySize`

Integer describing the current maximum allowed size.

Altering this value should be followed by a call to `HttpDriver::upgradeBodySize(InternalRequest)`.

## `$uri`

The URI string consisting of the path and query components.

## `$uriScheme`

The scheme [typically `"http"` or `"https"`] (either from target URI or whether the connection is encrypted or not).

## `$uriHost`

The host string (either from target URI or Host header).

## `$uriPort`

Integer accessed port [may vary from `$client->serverPort`, if client explicitly specified it].

## `$uriPath`

String path component of the URI.

## `$uriQuery`

String query component of the URI.

## `$cookies`

Cookies array in form of name => value pairs

## `$time`

Unix time at request initialization.

## `$httpDate`

HTTP compatibly formatted date string at request initialization.

## `$locals`

Array with "local" variables, to be used by [`Middleware`s](middleware.md) in combination with [`Request::getLocalVar($key)` and `Request::setLocalVar($key, $value)`](request.md).
