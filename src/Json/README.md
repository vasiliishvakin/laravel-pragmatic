# JSON Component

Multi-driver JSON handling system built on the Manager/Driver pattern.

## Architecture

### Key Concept: Manager-as-Proxy

**The critical feature**: `driver()` returns **a new `JsonManager` instance with the driver embedded**, not the raw driver itself.

```php
// JsonFactoryContainer creates JsonManager instances with embedded drivers
$this->singleton($name, fn () => $this->app->make(
    JsonManagerInstance::class,  // ← Returns JsonManager
    [
        'factoryContainer' => $this,
        'driver' => $this->createDriver($settings),  // ← Driver injected into manager
    ]
));
```

This enables fluent API chaining without losing manager context:

```php
Json::driver('pretty')->encode($data);
//   ↓ JsonManager      ↓ Proxied to driver via __call()
//   with driver inside

// Switch drivers on-the-fly
$json1 = Json::driver('default')->encode($data);
$json2 = Json::driver('pretty')->encode($data);
$collection = Json::driver('collection')->decode($json1);

// Manager methods work with any driver
Json::driver('pretty')->hash($data);
//                      ↑ JsonManager method that uses embedded driver
```

### Components

#### 1. Contracts

**`JsonDriverContract`** (JsonDriverContract.php:7) - Driver interface:
- `encode()` / `tryEncode()` - Encode to JSON
- `decode()` / `tryDecode()` - Decode from JSON
- `validate()` - Validate JSON string

**`JsonManagerInstance`** (JsonManagerInstance.php:7) - Marker interface for type hinting

#### 2. Manager

**`JsonManager`** (JsonManager.php:11) - Central orchestrator:

**Key Methods**:
- `driver(?string $name)` (JsonManager.php:30) - Returns new JsonManager with specified driver
- `instance()` (JsonManager.php:51) - Returns self if driver is set, otherwise creates default
- `__call()` (JsonManager.php:19) - Proxies unknown methods to embedded driver
- `hash()` (JsonManager.php:81) - Manager-level functionality using embedded driver

**How Proxying Works**:
```php
public function __call(string $method, array $args): mixed
{
    $driver = $this->instance()->rawDriver();  // ← Get embedded driver
    return $driver->$method(...$args);          // ← Delegate to driver
}
```

#### 3. Factory Container

**`JsonFactoryContainer`** (JsonFactoryContainer.php:11) - Extends `FactoryContainer`:
- Reads configuration from `config/pragmatic.php` (JsonFactoryContainer.php:22)
- Registers drivers as singletons (JsonFactoryContainer.php:25)
- Creates driver instances via Laravel Container (JsonFactoryContainer.php:35)

### Drivers

#### JsonDriver (Base)
JsonDriver.php:10

Standard implementation using `json_encode()`/`json_decode()`:
- Configurable flags (encode/decode/validate)
- Always uses `JSON_THROW_ON_ERROR` (JsonDriver.php:12)
- Try-methods catch `JsonException` (JsonDriver.php:34)

#### CollectionJsonDriver
CollectionJsonDriver.php:7

Specialized for Laravel Collections:
- `encode()` - Auto-converts Collection to array (CollectionJsonDriver.php:11)
- `decode()` - Returns `Collection` instead of array (CollectionJsonDriver.php:22)

#### JsJsonDriver
JsJsonDriver.php:9

Uses `Illuminate\Support\Js` for safe HTML output:
- `encode()` → `Js::from()->toHtml()` (JsJsonDriver.php:18)
- Escapes data for safe `<script>` tag injection

## Configuration

config/pragmatic.php:49-81

```php
'json' => [
    'default' => 'default',
    'drivers' => [
        'default' => [
            'driver' => JsonDriver::class,
            'params' => ['encodeFlags' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES],
        ],
        'pretty' => [
            'driver' => JsonDriver::class,
            'params' => ['encodeFlags' => JSON_PRETTY_PRINT | ...],
        ],
        'collection' => ['driver' => CollectionJsonDriver::class],
        'js' => ['driver' => JsJsonDriver::class],
    ],
]
```

## Usage

```php
use Pragmatic\Facades\Json;

// Basic operations
$json = Json::encode(['key' => 'value']);
$data = Json::decode($json);

// Try-versions (no exceptions)
$json = Json::tryEncode($invalid) ?? '{}';
$data = Json::tryDecode($broken, default: []);

// Validation
if (Json::validate($string)) { ... }

// Hash JSON (encode + FastHasher)
$hash = Json::hash($data);

// Switch drivers
$pretty = Json::driver('pretty')->encode($data);
$collection = Json::driver('collection')->decode($json); // → Collection

// For JavaScript (in Blade)
<script>
  const config = {!! Json::driver('js')->encode($config) !!};
</script>
```

## Design Patterns

### Manager as Proxy Wrapper

```php
JsonManager {
    factoryContainer  // ← Access to driver factory
    hasher           // ← Additional functionality (hash)
    driver           // ← Encapsulated driver instance

    + encode()       // ← Proxies to driver->encode()
    + decode()       // ← Proxies to driver->decode()
    + driver()       // ← Returns NEW JsonManager with different driver
    + hash()         // ← Manager method available to all drivers
}
```

**Benefits**:
- ✅ Unified interface (`JsonManager`) for all operations
- ✅ Switch drivers without changing object type
- ✅ Manager-level logic (e.g., `hash()`) available to all drivers
- ✅ Fluent API: `Json::driver('x')->method()->driver('y')->method()`

### Implementation Details

1. **Lazy Initialization** - Drivers created only on first access
2. **Singleton Pattern** - Each driver created once (JsonFactoryContainer.php:25)
3. **Facade Pattern** - `Json::` proxies to `JsonManager` (Facades/Json.php:10)
4. **Strategy Pattern** - Interchangeable drivers through unified interface
5. **Laravel Container Integration** - Full DI support for drivers

## Features

- **Flexibility** - Easy to add custom drivers
- **Type Safety** - Strict typing with `declare(strict_types=1)`
- **Error Handling** - Try-methods for graceful degradation
- **Specialization** - Drivers for different scenarios (Collections, JS, pretty-print)
- **Hashing** - Built-in integration with `FastHasher` (JsonManager.php:81)
