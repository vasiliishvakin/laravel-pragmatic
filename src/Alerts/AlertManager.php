<?php

declare(strict_types=1);

namespace Pragmatic\Alerts;

use Illuminate\Support\Facades\Session;

class AlertManager
{
    private const string SESSION_PREFIX = '__alerts__.';

    private const int MAX_ALERTS_PER_TYPE = 50;

    public function add(AlertData $alert): void
    {
        $type = $alert->type;
        $key = $this->sessionKey($type);
        $data = Session::get($key, []);

        // Check for duplicate messages (not just ID)
        foreach ($data as $existingAlertData) {
            if ($existingAlertData['message'] === $alert->message) {
                return;
            }
        }

        // Enforce max alerts limit
        if (count($data) >= self::MAX_ALERTS_PER_TYPE) {
            array_shift($data); // Remove oldest alert
        }

        // Store as array to avoid serialization issues
        $data[$alert->id] = $alert->toArray();
        Session::put($key, $data);
    }

    public function push(AlertType $type, string $message): void
    {
        $alert = AlertData::make($type, $message);
        $this->add($alert);
    }

    public function success(string $message): void
    {
        $this->push(AlertType::Success, $message);
    }

    public function error(string $message): void
    {
        $this->push(AlertType::Error, $message);
    }

    public function info(string $message): void
    {
        $this->push(AlertType::Info, $message);
    }

    public function warning(string $message): void
    {
        $this->push(AlertType::Warning, $message);
    }

    /**
     * Get alerts of a specific type and remove them from session (destructive read).
     *
     * @return array<string, AlertData>
     */
    public function get(AlertType $type): array
    {
        $data = $this->read($type);
        $this->forget($type);

        return $data;
    }

    /**
     * Get alerts of a specific type without removing them (non-destructive read).
     *
     * @return array<string, AlertData>
     */
    public function peek(AlertType $type): array
    {
        return $this->read($type);
    }

    /**
     * Get all alerts of all types without removing them (non-destructive read).
     *
     * @return iterable<string, AlertData>
     */
    public function peekAll(): iterable
    {
        foreach (AlertType::cases() as $type) {
            $messages = $this->peek($type);
            foreach ($messages as $id => $alert) {
                yield $id => $alert;
            }
        }
    }

    public function forget(AlertType $type): void
    {
        $key = $this->sessionKey($type);
        Session::forget($key);
    }

    /**
     * Get all alerts of all types and remove them from session (destructive read).
     *
     * @return iterable<string, AlertData>
     */
    public function all(): iterable
    {
        foreach (AlertType::cases() as $type) {
            $messages = $this->get($type);
            foreach ($messages as $id => $alert) {
                yield $id => $alert;
            }
        }
    }

    public function clear(): void
    {
        foreach (AlertType::cases() as $type) {
            $this->forget($type);
        }
    }

    /**
     * Check if there are any alerts of a specific type.
     */
    public function has(AlertType $type): bool
    {
        return count($this->peek($type)) > 0;
    }

    /**
     * Check if there are any alerts of any type.
     */
    public function hasAny(): bool
    {
        foreach (AlertType::cases() as $type) {
            if ($this->has($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get count of alerts for a specific type.
     */
    public function count(AlertType $type): int
    {
        return count($this->peek($type));
    }

    /**
     * Read alerts from session and convert arrays back to AlertData objects.
     *
     * @return array<string, AlertData>
     */
    private function read(AlertType $type): array
    {
        $key = $this->sessionKey($type);
        $data = Session::get($key, []);

        // Convert arrays back to AlertData objects
        return array_map(function (array $alertData): AlertData {
            return AlertData::make(
                AlertType::from($alertData['type']),
                $alertData['message'],
                $alertData['id']
            );
        }, $data);
    }

    private function sessionKey(AlertType $type): string
    {
        return self::SESSION_PREFIX.$type->value;
    }
}
