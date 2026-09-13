<?php

namespace app\components;

use yii\base\Component;
use yii\helpers\StringHelper;

/**
 * Politique unique de masquage pour les modeles, l'authentification et les URL.
 */
class AuditDataRedactor extends Component
{
    public $maximumDepth = 5;
    public $maximumItems = 100;
    public $maximumStringLength = 4000;

    public function redactArray(array $values): array
    {
        return $this->redactLevel($values, 0);
    }

    public function redactUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['query'])) {
            return $this->truncate($url);
        }

        parse_str($parts['query'], $query);
        $query = $this->redactArray($query);
        $path = ($parts['path'] ?? '') . ($query ? '?' . http_build_query($query) : '');

        return $this->truncate($path);
    }

    public function isSensitiveKey($key): bool
    {
        $key = strtolower((string) $key);

        return (bool) preg_match(
            '/(?:password|passwd|pwd|auth[_-]?key|access[_-]?token|refresh[_-]?token|verification[_-]?token|reset[_-]?token|dispatch[_-]?token|(?:^|[_-])token(?:$|[_-])|secret|cookie|authorization[_-]?header|credit[_-]?card|debit[_-]?card|card[_-]?(?:number|holder)|payment[_-]?credential|cvv|cvc)/i',
            $key
        );
    }

    private function redactLevel(array $values, int $depth): array
    {
        if ($depth >= $this->maximumDepth) {
            return ['_truncated' => '[MAX_DEPTH]'];
        }

        $result = [];
        $count = 0;
        foreach ($values as $key => $value) {
            if (++$count > $this->maximumItems) {
                $result['_truncated'] = '[MAX_ITEMS]';
                break;
            }

            if ($this->isSensitiveKey($key)) {
                $result[$key] = '[REDACTED]';
                continue;
            }

            $result[$key] = $this->normalizeValue($value, $depth + 1);
        }

        return $result;
    }

    private function normalizeValue($value, int $depth)
    {
        if (is_array($value)) {
            return $this->redactLevel($value, $depth);
        }
        if ($value instanceof \DateTimeInterface) {
            /* Le clonage evite que l'audit modifie l'objet metier d'origine. */
            $utcValue = clone $value;
            return $utcValue->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
        }
        if (is_object($value)) {
            return '[OBJECT ' . get_class($value) . ']';
        }
        if (is_resource($value)) {
            return '[RESOURCE]';
        }
        if (is_string($value)) {
            return $this->truncate($value);
        }

        return $value;
    }

    private function truncate(string $value): string
    {
        if (StringHelper::byteLength($value) <= $this->maximumStringLength) {
            return $value;
        }

        return StringHelper::byteSubstr($value, 0, $this->maximumStringLength) . '… [TRUNCATED]';
    }
}
