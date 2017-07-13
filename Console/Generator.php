<?php
namespace Vanilla\Console;

abstract class Generator extends Command {

    protected $stubName = '';

    protected $basePath = '';

    protected function exec($args)
    {
        $name = $this->getNameInput($args);
        $path = $this->buildPath($name);
        if($this->exists($path)) {
            throw new \Exception("{$name} {$this->stubName} already exists!");
        }

        $this->save($path, $this->buildClass($this->getStubContent($this->stubName), $name));

        \WP_CLI::runcommand('flush-rewrites');
    }

    protected function getNameInput($args)
    {
        if(count($args) === 0) {
            throw new \Exception('Name parameter required');
        }

        return ucfirst(camel_case(trim($args[0])));
    }

    protected function getStubContent($stubName)
    {
        $path = __DIR__ . "/../stubs/{$stubName}.stub";
        return file_get_contents($path);
    }

    protected function buildClass($content, $name)
    {
        $prettyName = to_sentence($name);

        $content = str_replace('DummyClassName', $name, $content);
        $content = str_replace('DummyName', snake_case($name), $content);
        $content = str_replace('DummySlug', kebab_case($name), $content);
        $content = str_replace('DummySingular', $prettyName, $content);
        $content = str_replace('DummyPlural', str_plural($prettyName), $content);

        return $content;
    }

    protected function save($path, $content)
    {
        return file_put_contents($path, $content);
    }

    protected function exists($path)
    {
        return file_exists($path);
    }

    protected function setStubName($stubName)
    {
        $this->stubName = $stubName;
        return $this;
    }

    protected function setPath($path)
    {
        $this->basePath = $path;
        return $this;
    }

    protected function buildPath($name)
    {
        return app()->path("app/{$this->basePath}/{$name}.php");
    }
}