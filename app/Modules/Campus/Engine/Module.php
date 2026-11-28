<?php

namespace App\Modules\Campus\Engine;

use Illuminate\Support\Collection;

/** One entry of config/campus_modules.php, read through named methods. */
class Module
{
    private function __construct(public readonly string $key, public readonly array $def)
    {
    }

    public static function find(string $key): self
    {
        $def = config("campus_modules.types.{$key}");
        abort_unless($def, 404, 'Unknown module.');

        return new self($key, $def);
    }

    /** @return Collection<string, self> */
    public static function all(): Collection
    {
        return collect(config('campus_modules.types'))->map(fn ($def, $key) => new self($key, $def));
    }

    public function get(string $path, mixed $default = null): mixed
    {
        return data_get($this->def, $path, $default);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->def);
    }

    public function group(): array
    {
        return config('campus_modules.groups.' . $this->def['group']);
    }

    /** @return array<string, array{key:string,label:string,type:string,required:bool,options:array}> */
    public function fields(): array
    {
        $fields = [];
        foreach ($this->def['fields'] as $key => $spec) {
            [$label, $type, $required, $options] = array_pad(explode('|', $spec, 4), 4, '');
            $fields[$key] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'required' => $required === '1',
                'options' => $options === '' ? [] : explode(',', $options),
            ];
        }

        return $fields;
    }

    public function statusLabel(string $status): array
    {
        $entry = $this->def['statuses'][$status] ?? [ucfirst(str_replace('_', ' ', $status)), ''];

        return ['label' => $entry[0], 'tone' => $entry[1]];
    }

    public function initialStatus(): string
    {
        return $this->def['initial'] ?? array_key_first($this->def['statuses']);
    }

    /** Dedicated table used instead of campus_records, for records older screens already read. */
    public function table(): ?string
    {
        return $this->def['table'] ?? null;
    }

    public function subject(): ?string
    {
        return $this->def['subject'] ?? null;
    }
}
