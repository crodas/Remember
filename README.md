# Remember

Easiest way to remember things across requests in PHP.

## Motivation

I love generating code and I needed a way of rebuilding only if something changed.


## How it works

```
use Remember\Remember;

$function = Remember::wrap('name', function(Array $args) {
    // do some that is expensive
    return $result;
});

// It will calculate once and cache the result
// until __FILE__ changes.
$result = $ $function([__FILE__, 'foobar']);
```

## Low level API

```
use Remember\Remember;

$ns = Remember::ns('foobar');
$result = $ns->get([__FILE__], $isValid);
if (!$isValid) {
    // do something
    $result = ...;
    $ns->store([__FILE__], $result);
}
```
