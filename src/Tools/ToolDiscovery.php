<?php

namespace Ginkida\AgentRunner\Tools;

use Ginkida\AgentRunner\Contracts\RemoteToolContract;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Auto-discovers RemoteToolContract implementations from a configured directory.
 */
class ToolDiscovery
{
    public function __construct(
        private readonly string $path,
        private readonly string $namespace,
    ) {}

    /**
     * Discover and instantiate all tool classes.
     *
     * @return RemoteToolContract[]
     */
    public function discover(): array
    {
        if (! is_dir($this->path)) {
            return [];
        }

        $tools = [];

        foreach (File::files($this->path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->namespace . '\\' . $file->getFilenameWithoutExtension();

            if (! class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            if (! $reflection->implementsInterface(RemoteToolContract::class)) {
                continue;
            }

            $tools[] = app($className);
        }

        return $tools;
    }
}
