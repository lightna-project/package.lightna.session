<?php

declare(strict_types=1);

namespace Lightna\Session\App\Session;

use Exception;
use Lightna\Engine\App\ObjectA;

class Serializer extends ObjectA
{
    protected string $method;

    /** @noinspection PhpUnused */
    protected function defineMethod(): void
    {
        $this->method = ini_get("session.serialize_handler");
    }

    public function unserialize(string $srz): array
    {
        $data = match ($this->method) {
            'php_serialize' => $this->unserializeNative($srz),
            'php' => $this->unserializePhp($srz),
            default => null,
        };

        if (!is_array($data)) {
            throw new Exception('Unknown session serialization method');
        }

        return $data;
    }

    protected function unserializeNative(string $srz): mixed
    {
        return unserialize($srz, ['allowed_classes' => false]);
    }

    protected function unserializePhp(string $srz): array
    {
        $result = [];
        $offset = 0;
        while ($offset < strlen($srz)) {
            if (!str_contains(substr($srz, $offset), "|")) {
                throw new Exception("Invalid session data");
            }
            $pos = strpos($srz, "|", $offset);
            $num = $pos - $offset;
            $varName = substr($srz, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($srz, $offset), ['allowed_classes' => false]);
            $result[$varName] = $data;
            $offset += strlen(serialize($data));
        }

        return $result;
    }
}
