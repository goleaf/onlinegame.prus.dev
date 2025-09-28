<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Advanced Session Properties Service
 *
 * Provides advanced session management capabilities for Livewire components
 * including session validation, cleanup, analytics, and cross-component synchronization.
 */
class SessionPropertiesService
{
    protected array $sessionKeys = [];
    protected array $defaultValues = [];
    protected array $validationRules = [];
    protected array $sessionAnalytics = [];

    /**
     * Register session properties for a component
     */
    public function registerComponentProperties(string $componentClass, array $properties): void
    {
        foreach ($properties as $property => $config) {
            $key = $this->generateSessionKey($componentClass, $property);
            $this->sessionKeys[$key] = [
                'component' => $componentClass,
                'property' => $property,
                'default' => $config['default'] ?? null,
                'validation' => $config['validation'] ?? null,
                'ttl' => $config['ttl'] ?? null,
                'encrypted' => $config['encrypted'] ?? false,
                'compressed' => $config['compressed'] ?? false,
            ];

            $this->defaultValues[$key] = $config['default'] ?? null;
            $this->validationRules[$key] = $config['validation'] ?? null;
        }
    }

    /**
     * Set a session property with validation and options
     */
    public function setProperty(string $componentClass, string $property, mixed $value, array $options = []): bool
    {
        $key = $this->generateSessionKey($componentClass, $property);

        // Validate property exists
        if (! isset($this->sessionKeys[$key])) {
            Log::warning("Attempted to set unregistered session property: {$key}");

            return false;
        }

        // Validate value if validation rules exist
        if ($this->validationRules[$key] && ! $this->validateValue($value, $this->validationRules[$key])) {
            Log::warning("Session property validation failed for: {$key}", ['value' => $value]);

            return false;
        }

        // Process value based on options
        $processedValue = $this->processValue($value, $this->sessionKeys[$key], $options);

        // Store in session
        Session::put($key, $processedValue);

        // Store metadata
        $this->storePropertyMetadata($key, $value, $options);

        return true;
    }

    /**
     * Get a session property with fallback to default
     */
    public function getProperty(string $componentClass, string $property, mixed $default = null): mixed
    {
        $key = $this->generateSessionKey($componentClass, $property);

        $value = Session::get($key, $default ?? $this->defaultValues[$key]);

        // Process retrieved value (decompress/decrypt if needed)
        if ($value !== null && isset($this->sessionKeys[$key])) {
            $value = $this->unprocessValue($value, $this->sessionKeys[$key]);
        }

        return $value;
    }

    /**
     * Remove a session property
     */
    public function removeProperty(string $componentClass, string $property): bool
    {
        $key = $this->generateSessionKey($componentClass, $property);

        if (Session::has($key)) {
            Session::forget($key);
            $this->removePropertyMetadata($key);

            return true;
        }

        return false;
    }

    /**
     * Clear all properties for a component
     */
    public function clearComponentProperties(string $componentClass): int
    {
        $cleared = 0;

        foreach ($this->sessionKeys as $key => $config) {
            if ($config['component'] === $componentClass && Session::has($key)) {
                Session::forget($key);
                $this->removePropertyMetadata($key);
                $cleared++;
            }
        }

        return $cleared;
    }

    /**
     * Get all properties for a component
     */
    public function getComponentProperties(string $componentClass): array
    {
        $properties = [];

        foreach ($this->sessionKeys as $key => $config) {
            if ($config['component'] === $componentClass) {
                $properties[$config['property']] = $this->getProperty($componentClass, $config['property']);
            }
        }

        return $properties;
    }

    /**
     * Reset all properties for a component to defaults
     */
    public function resetComponentToDefaults(string $componentClass): int
    {
        $reset = 0;

        foreach ($this->sessionKeys as $key => $config) {
            if ($config['component'] === $componentClass) {
                Session::put($key, $config['default']);
                $reset++;
            }
        }

        return $reset;
    }

    /**
     * Validate session integrity
     */
    public function validateSessionIntegrity(): array
    {
        $issues = [];

        foreach ($this->sessionKeys as $key => $config) {
            if (Session::has($key)) {
                $value = Session::get($key);

                // Check TTL if set
                if ($config['ttl'] && $this->isPropertyExpired($key)) {
                    $issues[] = [
                        'type' => 'expired',
                        'key' => $key,
                        'component' => $config['component'],
                        'property' => $config['property'],
                    ];
                }

                // Validate current value
                if ($config['validation'] && ! $this->validateValue($value, $config['validation'])) {
                    $issues[] = [
                        'type' => 'invalid',
                        'key' => $key,
                        'component' => $config['component'],
                        'property' => $config['property'],
                        'value' => $value,
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Clean up expired and invalid session properties
     */
    public function cleanupSession(): array
    {
        $cleaned = [];
        $issues = $this->validateSessionIntegrity();

        foreach ($issues as $issue) {
            if ($issue['type'] === 'expired' || $issue['type'] === 'invalid') {
                $key = $issue['key'];
                Session::forget($key);
                $this->removePropertyMetadata($key);

                $cleaned[] = $issue;
            }
        }

        return $cleaned;
    }

    /**
     * Get session analytics
     */
    public function getSessionAnalytics(): array
    {
        $analytics = [
            'total_properties' => count($this->sessionKeys),
            'active_properties' => 0,
            'expired_properties' => 0,
            'invalid_properties' => 0,
            'components' => [],
            'storage_size' => 0,
        ];

        foreach ($this->sessionKeys as $key => $config) {
            $component = $config['component'];

            if (! isset($analytics['components'][$component])) {
                $analytics['components'][$component] = [
                    'total' => 0,
                    'active' => 0,
                    'expired' => 0,
                    'invalid' => 0,
                ];
            }

            $analytics['components'][$component]['total']++;

            if (Session::has($key)) {
                $analytics['active_properties']++;
                $analytics['components'][$component]['active']++;

                // Estimate storage size
                $value = Session::get($key);
                $analytics['storage_size'] += strlen(serialize($value));

                // Check for issues
                if ($config['ttl'] && $this->isPropertyExpired($key)) {
                    $analytics['expired_properties']++;
                    $analytics['components'][$component]['expired']++;
                }

                if ($config['validation'] && ! $this->validateValue($value, $config['validation'])) {
                    $analytics['invalid_properties']++;
                    $analytics['components'][$component]['invalid']++;
                }
            }
        }

        return $analytics;
    }

    /**
     * Export session properties for backup
     */
    public function exportSessionProperties(string $componentClass = null): array
    {
        $export = [];

        foreach ($this->sessionKeys as $key => $config) {
            if ($componentClass && $config['component'] !== $componentClass) {
                continue;
            }

            if (Session::has($key)) {
                $export[$key] = [
                    'component' => $config['component'],
                    'property' => $config['property'],
                    'value' => Session::get($key),
                    'metadata' => $this->getPropertyMetadata($key),
                ];
            }
        }

        return $export;
    }

    /**
     * Import session properties from backup
     */
    public function importSessionProperties(array $properties): int
    {
        $imported = 0;

        foreach ($properties as $key => $data) {
            if (isset($data['component'], $data['property'], $data['value'])) {
                Session::put($key, $data['value']);

                if (isset($data['metadata'])) {
                    $this->storePropertyMetadata($key, $data['value'], $data['metadata']);
                }

                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Generate session key for component property
     */
    protected function generateSessionKey(string $componentClass, string $property): string
    {
        $className = class_basename($componentClass);

        return "livewire_session_{$className}_{$property}";
    }

    /**
     * Validate value against rules
     */
    protected function validateValue(mixed $value, array $rules): bool
    {
        foreach ($rules as $rule => $constraint) {
            switch ($rule) {
                case 'type':
                    if (gettype($value) !== $constraint) {
                        return false;
                    }

                    break;

                case 'in':
                    if (! in_array($value, $constraint)) {
                        return false;
                    }

                    break;

                case 'min':
                    if (is_numeric($value) && $value < $constraint) {
                        return false;
                    }

                    break;

                case 'max':
                    if (is_numeric($value) && $value > $constraint) {
                        return false;
                    }

                    break;

                case 'callback':
                    if (is_callable($constraint) && ! $constraint($value)) {
                        return false;
                    }

                    break;
            }
        }

        return true;
    }

    /**
     * Process value before storage
     */
    protected function processValue(mixed $value, array $config, array $options): mixed
    {
        // Compression
        if ($config['compressed'] && is_string($value) && strlen($value) > 100) {
            $value = gzcompress($value);
        }

        // Encryption (placeholder - implement actual encryption)
        if ($config['encrypted']) {
            // $value = encrypt($value);
        }

        return $value;
    }

    /**
     * Unprocess value after retrieval
     */
    protected function unprocessValue(mixed $value, array $config): mixed
    {
        // Decryption (placeholder - implement actual decryption)
        if ($config['encrypted']) {
            // $value = decrypt($value);
        }

        // Decompression
        if ($config['compressed'] && is_string($value)) {
            $decompressed = gzuncompress($value);
            if ($decompressed !== false) {
                $value = $decompressed;
            }
        }

        return $value;
    }

    /**
     * Check if property is expired
     */
    protected function isPropertyExpired(string $key): bool
    {
        $metadata = $this->getPropertyMetadata($key);

        if (isset($metadata['created_at'], $this->sessionKeys[$key]['ttl'])) {
            $expiresAt = $metadata['created_at'] + $this->sessionKeys[$key]['ttl'];

            return time() > $expiresAt;
        }

        return false;
    }

    /**
     * Store property metadata
     */
    protected function storePropertyMetadata(string $key, mixed $value, array $options = []): void
    {
        $metadataKey = "{$key}_metadata";
        $metadata = [
            'created_at' => time(),
            'updated_at' => time(),
            'size' => strlen(serialize($value)),
            'options' => $options,
        ];

        Session::put($metadataKey, $metadata);
    }

    /**
     * Get property metadata
     */
    protected function getPropertyMetadata(string $key): array
    {
        $metadataKey = "{$key}_metadata";

        return Session::get($metadataKey, []);
    }

    /**
     * Remove property metadata
     */
    protected function removePropertyMetadata(string $key): void
    {
        $metadataKey = "{$key}_metadata";
        Session::forget($metadataKey);
    }
}
