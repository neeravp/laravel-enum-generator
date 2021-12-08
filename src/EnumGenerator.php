<?php

namespace Studeo\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EnumGenerator
{
    public function __construct(
        public string $name,
        public array $values,
        public string $namespace,
        public string $path,
        public string $interface,
        public ?string $type,
    )
    {
    }

    /**
     * Create a new Enumm instance.
     */
    public static function make(
        string $name,
        array $values,
    ): ?static
    {
        /** Bail out and throw an exception is the supplied array doesn't have valid keys for Enum case name */
        if(!static::hasValidKeys($values)) { static::exception($values);}

        /** Determine if the supplied values array is an associative array with string keys */
        $interface = static::isAssoc($values) ? 'Backed' : 'Unit';
        // var_dump($interface);
        $static = new static(
            name:$name,
            values:$values,
            namespace: rtrim(app()->getNamespace(), '\\'),
            path: app_path('Enums'),
            interface: $interface,
            type: null
        );

        if($interface === 'Backed') {
            $static->type = $static->getType();
        }

        return $static;
    }

    /**
     * Set the data type for the Backed Enum class.
     */
    public function backed(?string $type = null): self
    {
        $this->interface = 'Backed';
        $this->type = $type ?? $this->getType();
        return $this;
    }

    /**
     * Set the namsespace for the Enum class.
     */
    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Set the path where the Enum class file should be stored.
     */
    public function path(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Write the Enum class file to specified path on the disk.
     */
    public function generate(): void
    {
        File::ensureDirectoryExists($this->path, 0755, true, true);
        file_put_contents(
            $this->path . '/' . $this->name . '.php',
            $this->getContents()
        );
    }

    /**
     * Construct the definition of the Enum class.
     */
    protected function getDefinition()
    {
        return rtrim(
            collect($this->values)->reduce(function($carry, $value, $key) {
                if($this->interface === 'Unit') {
                    return $carry .= "case " . Str::camel($value) . ";" . PHP_EOL . "\t";
                } else {
                    return $carry .= "case " . Str::camel($key) . " = " . ($this->type === 'int' ? (int)$value : "'{$value}'") . ";" . PHP_EOL . "\t";
                }
            }),
            PHP_EOL . "\t"
        );
    }

    /**
     * Get the stub from which the file for the Enum will be generated.
     */
    protected function getStub()
    {
        return file_get_contents(__DIR__ . '/../stubs/' . $this->interface . 'Enum.stub');
    }

    /**
     * Get the final contents for the Enum class.
     */
    protected function getContents(): string
    {
        return str_replace(
            [
                '{{ namespace }}',
                '{{ enum_name }}',
                '{{ definition }}',
                '{{ type }}'
            ],
            [
                $this->namespace,
                $this->name,
                $this->getDefinition(),
                $this->type ?? 'string'
            ],
            $this->getStub()
        );
    }

    /**
     * Determine if the supplied values array is an associative array with string keys
     */
    private static function isAssoc(array $values): bool
    {
        /** Bail out and throw an exception is the supplied array doesn't have valid keys for Enum case name */
        // if(!static::hasValidKeys($values)) { static::exception($values);}
        return count(array_filter(array_keys($values), 'is_string')) === count($values);
    }

    /**
     * Determine the data type for the backed enum.
     */
    private function getType(): string
    {
        /** Bail out and throw an exception is the supplied array doesn't have valid keys for Enum case name */
        // if(!static::hasValidKeys($this->values)) { static::exception($this->values);}
        $type = '';
        if(count(array_filter(array_values($this->values), 'is_int')) === count($this->values)) {
            $type = 'int';
        } elseif(count(array_filter(array_values($this->values), 'is_string')) === count($this->values)) {
            $type = 'string';
        } else {
            throw new \Exception('Enum values must be either int or string');
        }
        return $type;
    }

    /**
     * Determine whether the supplied array has valid keys for Enum case name.
     */
    private static function hasValidKeys(array $values): bool
    {
        // var_dump('hasValidKeys');
        $isValid = true;
        $keys = array_keys($values);

        if(
            count(array_filter($keys, 'is_numeric')) === count($values) &&
            $keys[0] === 0 &&  // If the keys are all numeric and start with 0
            max($keys) === count($values) - 1 // and the keys are sequential
        ) {
            return $isValid;
        }
        foreach($values as $key => $value) {
            /**
             * Must not contain any character other than letters and numbers.
             */
            if(preg_match('/[^a-zA-Z0-9]/', $key)) {
                $isValid = false;
            }
            /**
             * Must not start with a number.
             */
            if(preg_match('/^[0-9]/', $key)) {
                // var_dump("$key starts with a number");
                $isValid = false;
            }
        }


        return $isValid;
    }

    /**
     * Throw an exception for the supplied values array with invalid Enum case keys.
     */
    private static function exception(array $values): void
    {
        $message = join(
            PHP_EOL,
            [
                        'Invalid enum keys: ' . join(', ', array_keys($values)),
                        'Enum keys must be strings and start with a letter',
                        'Enum keys must be unique',
                        'Enum keys must not be empty',
                        'Enum keys must not contain spaces',
                        'Enum keys must not start with a number',
                        'Enum keys must not contain special character like: !@#$%^&*()_+=-[]{};\':"\|,.<>/?',
                        'Enum keys must not start with a reserved keyword',
            ]
        );
        throw new \Exception($message);
    }

    /**
     * Hook into the magic method which is called just before the object is destroyed
     * to process the given values array and other inputs to generate the Enum file.
     */
    public function __destruct()
    {
        $this->generate();
    }
}