<?php

function pest(): object
{
    return new class {
        public function extend(string $class): object
        {
            return $this;
        }

        public function in(string ...$directories): object
        {
            return $this;
        }
    };
}

function uses(string ...$traits): object
{
    return new class {
        public function in(string ...$directories): object
        {
            return $this;
        }
    };
}

function beforeEach(callable $closure): void
{
}

function test(string $description, callable $closure): void
{
}

function expect(mixed $value = null): object
{
    return new class {
        public function extend(string $name, callable $closure): object
        {
            return $this;
        }

        public function toContain(mixed ...$values): object
        {
            return $this;
        }

        public function toHaveKey(string $key): object
        {
            return $this;
        }

        public function toBe(mixed $value): object
        {
            return $this;
        }

        public function toBeInstanceOf(string $class): object
        {
            return $this;
        }
    };
}
