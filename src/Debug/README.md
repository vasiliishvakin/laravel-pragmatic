# Debug Component

Система отладки с поддержкой режимов работы и множественных драйверов. Более сложная вариация Manager/Driver паттерна с дополнительной логикой включения/выключения.

## Архитектура

### Ключевая концепция: Условная отладка с режимами

**Главная особенность**: Debug Manager поддерживает несколько режимов работы и может динамически включаться/выключаться, возвращая `NullDriver` когда отладка отключена.

### Компоненты

#### 1. Режимы отладки

**`DebugMode`** (Enums/DebugMode.php:5) - Enum с четырьмя режимами:

```php
enum DebugMode: string
{
    case Enabled = 'enabled';   // Отладка включена
    case Disabled = 'disabled'; // Отладка отключена
    case Silent = 'silent';     // Тихий режим (логирует, но не останавливает)
    case Auto = 'auto';         // Автоматическое определение режима
}
```

#### 2. Контракт драйвера

**`DebugDriver`** (Contracts/DebugDriver.php:7) - Пустой маркер-интерфейс.

Драйверы **не имеют фиксированного API**, методы определяются каждым драйвером отдельно:
- `dump(mixed ...$vars)` - выводит переменные
- `dd(mixed ...$vars): never` - выводит и останавливает выполнение
- `var_dump()`, `print_r()` и т.д.

#### 3. Manager

**`DebugManager`** (DebugManager.php:16) - Центральный класс управления отладкой.

**Ключевые отличия от `JsonManager`**:

**Режимы работы** (DebugManager.php:21):
```php
public function __construct(
    private DebugMode $mode,              // ← Текущий режим работы
    private DebugFactoryContainer $factory,
    private ?DebugDriver $defaultDriver = null
) {}
```

**Динамическая активация** (DebugManager.php:69-91):
```php
public function isEnabled(): bool
{
    // Проверка Pennant feature flag (приоритет)
    if (Feature::active('force-debug')) {
        return true;
    }

    // Fallback к конфигурации
    return config('debug.enabled', false);
}
```

**Runtime контроль** (DebugManager.php:96-109):
```php
public function enable(): self {
    $this->mode = DebugMode::Enabled;
    return $this;
}

public function disable(): self {
    $this->mode = DebugMode::Disabled;
    return $this;
}
```

**Выбор драйвера по режиму** (DebugManager.php:133-146):
```php
private function driverByMode(): DebugDriver
{
    $mode = $this->mode;

    if ($mode === DebugMode::Auto) {
        $mode = $this->autoModeToRealMode();  // ← Определяет по APP_ENV/Pennant
    }

    if ($mode === DebugMode::Disabled) {
        return new NullDriver();  // ← Ничего не делает
    }

    return $this->driver();  // ← Возвращает активный драйвер
}
```

**Условное выполнение** (DebugManager.php:148-155):
```php
public function dump(mixed ...$vars): mixed
{
    if ($this->mode === DebugMode::Disabled) {
        return null;  // ← Не выводит ничего
    }

    return $this->driver()->dump(...$vars);
}
```

#### 4. Factory Container

**`DebugFactoryContainer`** (DebugFactoryContainer.php:10) - **НЕ расширяет `FactoryContainer`**.

**Ключевая особенность**: Возвращает `DebugManager` с драйвером внутри (DebugFactoryContainer.php:48):
```php
public function get(string $name): DebugManager
{
    if (isset($this->drivers[$name])) {
        return $this->drivers[$name];
    }

    $config = config("debug.drivers.{$name}");
    $driverClass = $config['driver'];
    $driver = new $driverClass($config['options'] ?? []);

    // ← Возвращает DebugManager с драйвером, а не сам драйвер!
    $this->drivers[$name] = new DebugManager($this, $driver);

    return $this->drivers[$name];
}
```

Это означает паттерн **Manager-as-Proxy**, как и в `JsonManager`!

### Драйверы

#### 1. NullDriver (Silent/Disabled mode)
Drivers/NullDriver.php:9

Используется когда отладка отключена. **Проглатывает все вызовы** через `__call()`:

```php
final class NullDriver implements DebugDriver
{
    public function __call(string $method, array $args): mixed
    {
        // Ничего не делает, любой метод → null
        return null;
    }
}
```

**Применение**: Когда `mode = Disabled`, все вызовы `debug()->dump()`, `debug()->dd()` ничего не делают.

#### 2. LaravelDriver (Laravel dump/dd)
Drivers/LaravelDriver.php:10

Использует стандартные Laravel хелперы `dump()` и `dd()`:

```php
final class LaravelDriver implements DebugDriver
{
    public function dump(mixed ...$vars): mixed
    {
        return dump(...$vars);
    }

    public function dd(mixed ...$vars): never
    {
        dd(...$vars);
    }
}
```

#### 3. PhpDriver (PHP natives)
Drivers/PhpDriver.php:9

Использует нативные PHP функции:

```php
final class PhpDriver implements DebugDriver
{
    public function var_dump(mixed ...$vars): void
    {
        var_dump(...$vars);
    }

    public function print_r(mixed ...$vars): void
    {
        print_r(...$vars);
    }

    public function die(string|int $status = 0): never
    {
        die($status);
    }
}
```

#### 4. LogDriver (Логирование)
Drivers/LogDriver.php:7

Записывает отладочную информацию в Laravel логи:

```php
final class LogDriver implements DebugDriver
{
    public function dump(mixed ...$vars): mixed
    {
        foreach ($vars as $var) {
            \Log::debug(print_r($var, true));
        }
        return null;
    }

    public function dd(mixed ...$vars): never
    {
        $this->dump(...$vars);
        die();
    }
}
```

#### 5. SilentLogDriver (Тихое логирование)
Drivers/SilentLogDriver.php:7

Расширяет `LogDriver`, но `dd()` не останавливает выполнение:

```php
final class SilentLogDriver extends LogDriver
{
    public function dd(mixed ...$vars): never
    {
        $this->dump(...$vars);
        // ⚠️ ПРОБЛЕМА: Не останавливает выполнение, но тип never
    }
}
```

## Конфигурация

config/debug.php:9-111

```php
return [
    // Глобальное включение отладки
    // В production автоматически false, если не указано явно
    'enabled' => env('DEBUG_ENABLED', env('APP_ENV') !== 'production'),

    // Драйвер по умолчанию
    'default' => env('DEBUG_DRIVER', 'core'),

    // Доступные драйверы
    'drivers' => [
        'core' => [
            'driver' => CoreDriver::class,
            'options' => [
                'max_depth' => env('DEBUG_CORE_MAX_DEPTH', 10),
                'max_string_length' => env('DEBUG_CORE_MAX_STRING', 1000),
                'show_resources' => env('DEBUG_CORE_SHOW_RESOURCES', true),
            ],
        ],

        'laradumps' => [
            'driver' => LaradumpsDriver::class,
            'options' => [
                'screen' => env('DEBUG_LARADUMPS_SCREEN', 'Debug'),
                'auto_clear' => env('DEBUG_LARADUMPS_AUTO_CLEAR', false),
            ],
        ],

        'log' => [
            'driver' => LogDriver::class,
            'options' => [
                'channel' => env('DEBUG_LOG_CHANNEL', config('logging.default')),
                'level' => env('DEBUG_LOG_LEVEL', 'debug'),
                'backtrace' => env('DEBUG_LOG_BACKTRACE', true),
                'backtrace_depth' => env('DEBUG_LOG_BACKTRACE_DEPTH', 5),
            ],
        ],
    ],

    // Pennant feature flag для переопределения настроек
    'pennant_feature' => 'force-debug',
];
```

## Artisan Commands

### 1. debug:enable - Включить отладку

Commands/DebugEnableCommand.php:10

```bash
# Runtime (только текущий запрос)
php artisan debug:enable

# Глобально (записывает в .env файл)
php artisan debug:enable --global
```

**Что делает**:
- Runtime: `debug()->enable()` - устанавливает `$mode = DebugMode::Enabled`
- Global: Добавляет/обновляет `DEBUG_ENABLED=true` в `.env`

### 2. debug:disable - Отключить отладку

Commands/DebugDisableCommand.php:10

```bash
# Runtime (только текущий запрос)
php artisan debug:disable

# Глобально (обновляет .env файл)
php artisan debug:disable --global
```

**Что делает**:
- Runtime: `debug()->disable()` - устанавливает `$mode = DebugMode::Disabled`
- Global: Обновляет `DEBUG_ENABLED=false` в `.env`

### 3. debug:driver - Управление драйверами

Commands/DebugDriverCommand.php:10

```bash
# Показать текущий драйвер
php artisan debug:driver --show

# Список доступных драйверов
php artisan debug:driver

# Установить драйвер
php artisan debug:driver log
php artisan debug:driver laradumps
```

**Что делает**: Обновляет `DEBUG_DRIVER` в `.env` файле.

## Использование

### Базовое использование

```php
use Pragmatic\Facades\Debug;

// Helper function
debug()->dump($data);
debug()->dd($data);

// Facade
Debug::dump($data);
Debug::dd($data);
```

### Переключение драйверов

```php
// Логирование в файл
Debug::driver('log')->dump($data);

// Нативный var_dump
Debug::driver('php')->var_dump($data);

// Laravel dump
Debug::driver('laravel')->dump($data);
```

### Runtime контроль

```php
// Включить отладку для текущего запроса
debug()->enable();

// Отключить отладку
debug()->disable();

// Проверка состояния
if (debug()->isEnabled()) {
    debug()->dump($data);
}

// Сброс к конфигурации
debug()->resetMode();
```

### Условная отладка

```php
// Автоматически ничего не делает если mode = Disabled
debug()->dump($user);  // ← Может вернуть null без вывода

// Явная проверка
if (debug()->isEnabled()) {
    debug()->dump($user);
}
```

## Ключевые особенности

### 1. Auto Mode Resolution

DebugManager.php:123-131

```php
private function autoModeToRealMode(): DebugMode
{
    // Определяет режим на основе APP_ENV и Pennant flags
    if ($this->isEnabled()) {
        return DebugMode::Enabled;
    }

    return DebugMode::Disabled;
}
```

**Логика**:
1. Проверяет Pennant feature flag `force-debug`
2. Если активен → `Enabled`
3. Иначе проверяет `config('debug.enabled')`
4. В production по умолчанию `false`

### 2. Pennant Integration (Feature Flags)

DebugManager.php:77-86

```php
if (class_exists(Feature::class)) {
    if (Feature::active('force-debug')) {
        return true;  // ← Разрешает отладку даже в production
    }
}
```

**Применение**: Можно включить отладку для конкретного пользователя в production через Pennant.

### 3. Null Object Pattern

Когда `mode = Disabled`, используется `NullDriver`, который **всегда возвращает null** для любого метода через `__call()`.

**Преимущество**: Не нужны условные проверки в коде:
```php
// Всегда безопасно вызывать
debug()->dump($data);  // ← null если отключено
```

### 4. Режим Silent

```php
debug()->silent();

// Логирует, но dd() не останавливает выполнение
debug()->dd($data);  // ← В silent mode не делает die()
```

### 5. Динамическое переключение драйверов

```php
// В одном запросе разные драйверы
debug()->driver('log')->dump($request);      // → в лог
debug()->driver('laravel')->dump($response); // → на экран
```

## Design Patterns

### 1. Manager-as-Proxy Pattern

```php
DebugManager {
    mode              // ← Текущий режим работы
    factory           // ← Доступ к фабрике драйверов
    defaultDriver     // ← Инкапсулированный драйвер

    + dump()          // ← Проверяет режим, затем driver->dump()
    + dd()            // ← Проверяет режим, затем driver->dd()
    + driver()        // ← Возвращает НОВЫЙ DebugManager с другим драйвером
    + enable()        // ← Runtime контроль режима
    + disable()       // ← Runtime контроль режима
}
```

### 2. Null Object Pattern

`NullDriver` заменяет условные проверки:
```php
// Вместо:
if ($debugEnabled) {
    dump($data);
}

// Просто:
debug()->dump($data);  // ← NullDriver если отключено
```

### 3. State Pattern

`DebugMode` enum управляет поведением:
- `Enabled` → использует настоящий драйвер
- `Disabled` → использует NullDriver
- `Auto` → определяет динамически
- `Silent` → особое поведение (логирует без die)

### 4. Strategy Pattern

Взаимозаменяемые драйверы через единый интерфейс `DebugDriver`.

### 5. Feature Flag Pattern

Интеграция с Laravel Pennant для runtime контроля в production.

## Проблемы в коде

### ⚠️ ВНИМАНИЕ: Код требует доработки

1. **DebugManager.php:31-42** - метод `driver()` не завершен:
```php
if (isset($this->drivers[$name])) {
    return $this->drivers[$name];
}
// ← Отсутствует код создания драйвера
```

2. **DebugManager.php:46** - синтаксическая ошибка:
```php
if ($this)  // ← Неполный if-statement
```

3. **DebugManager.php:72** - `self::$enabled` не объявлено (должно быть `private static ?bool $enabled = null`)

4. **SilentLogDriver.php:14** - метод `dd()` имеет тип `never`, но не останавливает выполнение:
```php
public function dd(mixed ...$vars): never
{
    $this->dump(...$vars);
    // ← Должно быть: return (но never не может return)
    // Возможно silent mode не должен иметь dd()
}
```

5. **config/debug.php:5-7** - ссылается на несуществующие классы:
```php
use Modules\Toolbox\Debug\Drivers\CoreDriver;
use Modules\Toolbox\Debug\Drivers\LaradumpsDriver;
// ← Эти классы не существуют в src/Debug/Drivers/
```

6. **DebugManager.php:111** - метод `silent()` пустой:
```php
public function silent(): self {}  // ← Нет реализации
```

## Преимущества архитектуры

✅ **Безопасность в production** - Автоматически отключается через APP_ENV
✅ **Feature flags** - Pennant integration для выборочной отладки
✅ **Множественные драйверы** - Логи, экран, внешние инструменты
✅ **Runtime контроль** - Включение/выключение без перезапуска
✅ **Null Object Pattern** - Нет необходимости в условных проверках
✅ **CLI управление** - Artisan команды для настройки
✅ **Режимы работы** - Auto, Enabled, Disabled, Silent
✅ **Zero overhead** - NullDriver возвращает null без операций

## Рекомендации по использованию

### В разработке

```php
// .env
DEBUG_ENABLED=true
DEBUG_DRIVER=laravel

// Код
debug()->dump($user);
debug()->dd($query);
```

### В production

```php
// .env
DEBUG_ENABLED=false  # или не указывать

// Код работает безопасно
debug()->dump($data);  // ← Ничего не делает (NullDriver)

// Для конкретного пользователя через Pennant
Feature::define('force-debug', fn (User $user) => $user->isAdmin());
```

### Логирование без остановки

```php
// Использовать LogDriver вместо LaravelDriver
debug()->driver('log')->dump($data);  // → записывает в лог
debug()->driver('log')->dd($data);    // → записывает и die()

// Или установить по умолчанию
DEBUG_DRIVER=log
```

### Временная отладка

```php
// В контроллере или middleware
if (request()->has('debug')) {
    debug()->enable();
}

// Весь код после этого будет отлаживаться
debug()->dump($result);
```
